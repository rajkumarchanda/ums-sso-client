<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SmartExam (issuer) URL
    |--------------------------------------------------------------------------
    |
    | Base URL of the SmartExam / UMS server that issues SSO tokens.
    | Must match the `iss` claim inside the token payload.
    |
    */
    'issuer' => rtrim(env('SMARTEXAM_URL', ''), '/'),

    /*
    |--------------------------------------------------------------------------
    | Client credentials
    |--------------------------------------------------------------------------
    |
    | Generated in SmartExam Admin → SSO → Applications.
    |
    */
    'client_key' => env('SSO_CLIENT_KEY'),

    'client_secret' => env('SSO_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | Registered callback URL for this consumer app. Used for documentation
    | and optional validation. Must start with your app base URL.
    |
    */
    'callback_url' => env('SSO_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Audience
    |--------------------------------------------------------------------------
    |
    | Must match the `aud` claim in the token (your consumer app base URL).
    | Defaults to the origin of SSO_CALLBACK_URL, then APP_URL.
    |
    */
    'audience' => rtrim(
        env('SSO_AUDIENCE') ?: (
            ($scheme = parse_url((string) env('SSO_CALLBACK_URL', ''), PHP_URL_SCHEME))
            && ($host = parse_url((string) env('SSO_CALLBACK_URL', ''), PHP_URL_HOST))
                ? $scheme.'://'.$host
                : (string) env('APP_URL', '')
        ),
        '/'
    ),

    /*
    |--------------------------------------------------------------------------
    | User provisioning
    |--------------------------------------------------------------------------
    |
    | Bind a custom class implementing SmartExam\SsoClient\Contracts\SsoUserProvisioner
    | or use the default provisioner with `user_model` below.
    |
    */
    'user_provisioner' => null,

    'user_model' => env('AUTH_MODEL', App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => env('SMARTEXAM_SSO_ROUTES', true),
        'prefix' => env('SMARTEXAM_SSO_ROUTE_PREFIX', ''),
        'middleware' => ['web'],
        'callback' => 'sso/callback',
        'exchange' => 'api/sso/exchange',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session / redirects
    |--------------------------------------------------------------------------
    */
    'state_session_key' => 'smartexam_sso.state',

    'after_login_redirect' => env('SSO_AFTER_LOGIN_REDIRECT', '/'),

];
