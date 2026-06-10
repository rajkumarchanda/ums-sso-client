<?php

namespace SmartExam\SsoClient\Support;

use Illuminate\Support\Str;

class SsoState
{
    /**
     * Store an opaque state value in session before opening the SmartExam SSO prompt.
     * Pass the returned value as `state` when calling launchSmartExamSsoOverlay().
     */
    public static function remember(?string $state = null): string
    {
        $state = $state ?? (string) Str::uuid();

        session([config('smartexam-sso.state_session_key') => $state]);

        return $state;
    }
}
