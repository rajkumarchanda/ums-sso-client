<?php

namespace SmartExam\SsoClient\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use SmartExam\SsoClient\Services\SsoAuthenticationService;
use Symfony\Component\HttpFoundation\Response;

class SsoCallbackController extends Controller
{
    public function __construct(
        protected SsoAuthenticationService $authentication,
    ) {}

    /**
     * SmartExam redirects here after the user approves SSO.
     *
     * GET /sso/callback?token=...&expires_at=...&state=...
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $token = $request->query('token');
        $expiresAt = (int) $request->query('expires_at');
        $state = $request->query('state');

        if (! is_string($token) || $token === '') {
            abort(400, 'Missing SSO token.');
        }

        try {
            $this->authentication->assertNotExpired($expiresAt > 0 ? $expiresAt : null);

            if ($this->authentication->isOverlayCallback($request)) {
                $this->authentication->verifyToken($token, is_string($state) ? $state : null);

                return response()->view('smartexam-sso::callback-overlay', [
                    'token' => $token,
                    'expiresAt' => $expiresAt,
                    'state' => $state,
                    'redirectUrl' => url(config('smartexam-sso.after_login_redirect', '/')),
                ]);
            }

            $payload = $this->authentication->verifyToken($token, is_string($state) ? $state : null);
            $this->authentication->loginFromPayload($request, $payload);
        } catch (InvalidArgumentException $exception) {
            Log::warning('SmartExam SSO callback rejected', ['reason' => $exception->getMessage()]);
            abort(401, $exception->getMessage());
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'not configured')) {
                Log::error('SmartExam SSO callback misconfigured', ['reason' => $exception->getMessage()]);
                abort(503, $exception->getMessage());
            }

            Log::error('SmartExam SSO callback failed', ['reason' => $exception->getMessage()]);
            abort(500, 'SSO login failed.');
        }

        return redirect()->intended(config('smartexam-sso.after_login_redirect', '/'));
    }
}
