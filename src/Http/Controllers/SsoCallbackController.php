<?php

namespace SmartExam\SsoClient\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;
use SmartExam\SsoClient\Services\SmartExamSsoVerifier;

class SsoCallbackController extends Controller
{
    public function __construct(
        protected SmartExamSsoVerifier $verifier,
        protected SsoUserProvisioner $provisioner,
    ) {}

    /**
     * SmartExam redirects here after the user approves SSO.
     *
     * GET /sso/callback?token=...&expires_at=...&state=...
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $token = $request->query('token');
        $expiresAt = (int) $request->query('expires_at');
        $state = $request->query('state');

        if (! is_string($token) || $token === '') {
            abort(400, 'Missing SSO token.');
        }

        if ($expiresAt > 0 && $expiresAt < time()) {
            abort(401, 'SSO token has expired.');
        }

        try {
            $this->verifier->verifyState($state, session(config('smartexam-sso.state_session_key')));
            $payload = $this->verifier->verify($token);
        } catch (InvalidArgumentException $exception) {
            Log::warning('SmartExam SSO callback rejected', ['reason' => $exception->getMessage()]);
            abort(401, $exception->getMessage());
        }

        session()->forget(config('smartexam-sso.state_session_key'));

        $user = $this->provisioner->fromPayload($payload);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(config('smartexam-sso.after_login_redirect', '/'));
    }
}
