<?php

namespace SmartExam\SsoClient\Support;

class SsoUrl
{
    public static function issuer(): string
    {
        return rtrim((string) config('smartexam-sso.issuer', ''), '/');
    }

    public static function overlayScript(): string
    {
        return self::issuer().'/js/sso-overlay.js';
    }

    /**
     * Build the SmartExam /sso/connect URL to start an SSO flow.
     */
    public static function connect(?string $state = null, ?string $redirectUrl = null): string
    {
        $query = array_filter([
            'client' => config('smartexam-sso.client_key'),
            'redirect' => $redirectUrl ?? config('smartexam-sso.callback_url'),
            'state' => $state,
        ], fn ($value) => $value !== null && $value !== '');

        return self::issuer().'/sso/connect?'.http_build_query($query);
    }

    /**
     * Redirect users here to sign out of SmartExam (issuer session).
     */
    public static function issuerSignIn(?string $returnUrl = null): string
    {
        $url = self::issuer().'/sign-in';

        if ($returnUrl !== null && $returnUrl !== '') {
            $url .= '?'.http_build_query(['redirect' => $returnUrl]);
        }

        return $url;
    }
}
