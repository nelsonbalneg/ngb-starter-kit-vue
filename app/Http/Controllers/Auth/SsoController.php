<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SsoAuthenticationService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SsoController extends Controller
{
    public function __construct(private readonly SsoAuthenticationService $service) {}

    public function redirect(Request $request): Response
    {
        if (! config('sso.enabled')) {
            return to_route('login')
                ->with('status', 'SSO login is not enabled.');
        }

        $state = $this->makeState();
        $request->session()->put('sso_state', $state);
        Cache::put($this->stateCacheKey($state), true, now()->addMinutes(10));

        Log::info('SSO redirect started.', [
            'host' => $request->getHost(),
            'session_id' => $request->session()->getId(),
            'state_hash' => $this->stateHash($state),
            'state_length' => strlen($state),
            'state_validation' => config('sso.state_validation'),
            'authorize_url' => config('sso.authorize_url'),
            'redirect_uri' => config('sso.redirect_uri'),
        ]);

        $authorizationUrl = $this->service->authorizationUrl($state);

        if ($request->header('X-Inertia')) {
            return Inertia::location($authorizationUrl);
        }

        return redirect()->away($authorizationUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! config('sso.enabled')) {
            Log::warning('SSO callback rejected because SSO is disabled.', [
                'host' => $request->getHost(),
            ]);

            return to_route('login')
                ->with('status', 'SSO login is not enabled.');
        }

        if ($request->filled('error')) {
            Log::warning('SSO callback returned provider error.', [
                'host' => $request->getHost(),
                'error' => $request->string('error')->toString(),
                'error_description_present' => $request->filled('error_description'),
            ]);

            return to_route('login')
                ->with('status', 'SSO login was cancelled or denied.');
        }

        if (! $this->hasValidState($request)) {
            Log::warning('SSO callback rejected because state is invalid.', [
                'host' => $request->getHost(),
                'full_url_without_query' => $request->url(),
                'session_id' => $request->session()->getId(),
                'has_code' => $request->filled('code'),
                'has_state' => $request->filled('state'),
                'state_length' => strlen($request->string('state')->toString()),
                'state_hash' => $this->stateHash($request->string('state')->toString()),
            ]);

            return to_route('login')
                ->with('status', 'Invalid SSO session state. Please try again.');
        }

        try {
            Log::info('SSO callback state validated; exchanging authorization code.', [
                'host' => $request->getHost(),
                'session_id' => $request->session()->getId(),
                'state_hash' => $this->stateHash($request->string('state')->toString()),
            ]);

            $token = $this->service->token($request->string('code')->toString());
            $profile = $this->service->user($token);
            $user = $this->service->resolveUser($profile);
        } catch (Throwable $exception) {
            Log::error('SSO login failed after state validation.', [
                'host' => $request->getHost(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return to_route('login')
                ->with('status', 'Unable to complete SSO login. Please try again.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        Log::info('SSO login completed.', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'host' => $request->getHost(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function hasValidState(Request $request): bool
    {
        $state = $request->string('state')->toString();

        if ($state === '') {
            Log::warning('SSO state validation failed: missing state.', [
                'host' => $request->getHost(),
                'session_id' => $request->session()->getId(),
            ]);

            return false;
        }

        $sessionState = (string) $request->session()->pull('sso_state');
        $hasSessionState = $sessionState !== '' && hash_equals($sessionState, $state);
        $hasCachedState = Cache::pull($this->stateCacheKey($state), false) === true;
        $hasEncryptedState = strlen($state) > 80 && $this->hasValidEncryptedState($state);
        $hasProviderManagedState = $this->hasProviderManagedState($request, $state);

        Log::info('SSO state validation checked.', [
            'host' => $request->getHost(),
            'session_id' => $request->session()->getId(),
            'state_hash' => $this->stateHash($state),
            'callback_state_length' => strlen($state),
            'state_validation' => config('sso.state_validation'),
            'session_state_present' => $sessionState !== '',
            'session_state_match' => $hasSessionState,
            'cache_state_match' => $hasCachedState,
            'encrypted_state_valid' => $hasEncryptedState,
            'provider_managed_state_valid' => $hasProviderManagedState,
        ]);

        return $hasSessionState || $hasCachedState || $hasEncryptedState || $hasProviderManagedState;
    }

    private function stateCacheKey(string $state): string
    {
        return 'sso:state:'.$state;
    }

    private function makeState(): string
    {
        return Str::random(40);
    }

    private function hasProviderManagedState(Request $request, string $state): bool
    {
        $mode = strtolower((string) config('sso.state_validation', 'auto'));

        if (! $request->filled('code')) {
            return false;
        }

        if ($mode === 'relaxed') {
            Log::warning('SSO state validation accepted in relaxed mode.', [
                'host' => $request->getHost(),
                'session_id' => $request->session()->getId(),
                'state_hash' => $this->stateHash($state),
                'state_length' => strlen($state),
            ]);

            return true;
        }

        if ($mode === 'auto' && strlen($state) === 40) {
            Log::warning('SSO state validation accepted provider-managed 40-character state in auto mode.', [
                'host' => $request->getHost(),
                'session_id' => $request->session()->getId(),
                'state_hash' => $this->stateHash($state),
                'state_length' => strlen($state),
            ]);

            return true;
        }

        return false;
    }

    private function hasValidEncryptedState(string $state): bool
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            Log::warning('SSO encrypted state validation failed.', [
                'state_hash' => $this->stateHash($state),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        $isValid = is_array($payload)
            && isset($payload['nonce'], $payload['expires_at'])
            && is_string($payload['nonce'])
            && is_int($payload['expires_at'])
            && $payload['expires_at'] >= now()->timestamp;

        if (! $isValid) {
            Log::warning('SSO encrypted state payload is invalid or expired.', [
                'state_hash' => $this->stateHash($state),
                'payload_keys' => is_array($payload) ? array_keys($payload) : [],
                'expires_at' => is_array($payload) ? ($payload['expires_at'] ?? null) : null,
                'now' => now()->timestamp,
            ]);
        }

        return $isValid;
    }

    private function stateHash(string $state): ?string
    {
        return $state === '' ? null : hash('sha256', $state);
    }
}
