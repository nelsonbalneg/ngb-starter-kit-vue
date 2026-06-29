<?php

$baseUrl = rtrim((string) env('SSO_BASE_URL', ''), '/');

return [
    'enabled' => env('SSO_ENABLED', false),
    'base_url' => $baseUrl,
    'authorize_url' => env('SSO_AUTHORIZE_URL', $baseUrl.'/oauth/authorize'),
    'token_url' => env('SSO_TOKEN_URL', $baseUrl.'/oauth/token'),
    'user_url' => env('SSO_USER_URL', $baseUrl.'/api/user'),
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri' => env('SSO_REDIRECT_URI'),
    'logout_redirect_uri' => env('SSO_LOGOUT_REDIRECT_URI', env('APP_URL').'/dashboard'),
    'scopes' => env('SSO_SCOPES', 'openid profile email'),
    'state_validation' => env('SSO_STATE_VALIDATION', 'auto'),
    'default_role' => env('SSO_DEFAULT_ROLE', 'employee'),
    'default_organization_slug' => env('SSO_DEFAULT_ORGANIZATION_SLUG', 'main'),
];
