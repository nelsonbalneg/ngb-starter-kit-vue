<?php

namespace App\Services\SiteAdministration;

use App\Models\User;
use App\Models\UserImpersonationLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserImpersonationService
{
    public const SESSION_ADMIN_ID = 'impersonation.admin_user_id';

    public const SESSION_IMPERSONATED_ID = 'impersonation.impersonated_user_id';

    public const SESSION_LOG_ID = 'impersonation.log_id';

    public const SESSION_REFERENCE = 'impersonation.reference_number';

    public function __construct(private readonly ModuleActivityLogger $activity) {}

    /**
     * @param  array{reference_number: string, reason: string}  $data
     */
    public function start(User $admin, User $target, array $data, Request $request): void
    {
        if (! $admin->can('users.impersonate')) {
            throw new AuthorizationException;
        }

        $this->validateStart($admin, $target, $request);

        DB::transaction(function () use ($admin, $target, $data, $request): void {
            $log = UserImpersonationLog::create([
                'admin_user_id' => $admin->id,
                'impersonated_user_id' => $target->id,
                'reference_number' => $data['reference_number'],
                'reason' => $data['reason'],
                'started_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1024),
                'status' => UserImpersonationLog::STATUS_ACTIVE,
            ]);

            $this->activity->record(
                'authentication',
                'user.impersonation.started',
                "Started impersonating {$target->name}.",
                $target,
                [
                    'admin_user_id' => $admin->id,
                    'reference_number' => $data['reference_number'],
                ],
            );

            Auth::login($target);
            $request->session()->regenerate();

            $request->session()->put([
                self::SESSION_ADMIN_ID => $admin->id,
                self::SESSION_IMPERSONATED_ID => $target->id,
                self::SESSION_LOG_ID => $log->id,
                self::SESSION_REFERENCE => $data['reference_number'],
            ]);
        });
    }

    public function stop(Request $request): void
    {
        if (! $this->isImpersonating($request)) {
            throw ValidationException::withMessages([
                'impersonation' => 'There is no active impersonation session to stop.',
            ]);
        }

        $adminId = (int) $request->session()->get(self::SESSION_ADMIN_ID);
        $impersonatedId = (int) $request->session()->get(self::SESSION_IMPERSONATED_ID);
        $logId = (int) $request->session()->get(self::SESSION_LOG_ID);

        DB::transaction(function () use ($adminId, $impersonatedId, $logId, $request): void {
            UserImpersonationLog::query()
                ->whereKey($logId)
                ->where('status', UserImpersonationLog::STATUS_ACTIVE)
                ->update([
                    'ended_at' => now(),
                    'status' => UserImpersonationLog::STATUS_ENDED,
                    'updated_at' => now(),
                ]);

            $admin = User::query()->findOrFail($adminId);
            $target = User::query()->find($impersonatedId);

            Auth::login($admin);
            $request->session()->regenerate();
            $request->session()->forget([
                self::SESSION_ADMIN_ID,
                self::SESSION_IMPERSONATED_ID,
                self::SESSION_LOG_ID,
                self::SESSION_REFERENCE,
            ]);

            $this->activity->record(
                'authentication',
                'user.impersonation.ended',
                'Stopped impersonation session.',
                $target,
                [
                    'admin_user_id' => $admin->id,
                    'impersonated_user_id' => $impersonatedId,
                    'impersonation_log_id' => $logId,
                ],
            );
        });
    }

    public function isImpersonating(Request $request): bool
    {
        return $request->session()->has(self::SESSION_ADMIN_ID)
            && $request->session()->has(self::SESSION_IMPERSONATED_ID)
            && $request->session()->has(self::SESSION_LOG_ID);
    }

    private function validateStart(User $admin, User $target, Request $request): void
    {
        if ($this->isImpersonating($request)) {
            throw ValidationException::withMessages([
                'impersonation' => 'Stop the current impersonation session before starting another one.',
            ]);
        }

        if ($admin->is($target)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot impersonate your own account.',
            ]);
        }

        if (! $target->is_active || $target->locked_at !== null) {
            throw ValidationException::withMessages([
                'user' => 'Inactive or locked accounts cannot be impersonated.',
            ]);
        }

        if ($target->can('users.impersonate') && ! $admin->can('users.impersonate.privileged')) {
            throw ValidationException::withMessages([
                'user' => 'Privileged accounts cannot be impersonated with your current permission level.',
            ]);
        }
    }
}
