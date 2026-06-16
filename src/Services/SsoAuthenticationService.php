<?php

namespace SmartExam\SsoClient\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;
use SmartExam\SsoClient\Events\SmartExamSsoAuthenticated;

class SsoAuthenticationService
{
    public function __construct(
        protected SmartExamSsoVerifier $verifier,
        protected SsoUserProvisioner $provisioner,
    ) {}

    /**
     * @return array{sub: int|string, email: string, name: string, iss?: string, aud?: string, exp?: int, iat?: int, session_id?: string}
     */
    public function verifyToken(string $token, ?string $state): array
    {
        $this->verifier->verifyState($state, session(config('smartexam-sso.state_session_key')));

        return $this->verifier->verify($token);
    }

    public function assertNotExpired(?int $expiresAt): void
    {
        if ($expiresAt !== null && $expiresAt > 0 && $expiresAt < time()) {
            throw new InvalidArgumentException('SSO token has expired.');
        }
    }

    /**
     * @param  array{sub: int|string, email: string, name: string, iss?: string, aud?: string, exp?: int, iat?: int, session_id?: string}  $payload
     */
    public function loginFromPayload(Request $request, array $payload): Authenticatable
    {
        session()->forget(config('smartexam-sso.state_session_key'));

        $user = $this->provisioner->fromPayload($payload);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        SmartExamSsoAuthenticated::dispatch($user, $payload);

        return $user;
    }

    public function isOverlayCallback(Request $request): bool
    {
        if ($request->query('display') === 'overlay') {
            return true;
        }

        return strtolower((string) $request->header('Sec-Fetch-Dest', '')) === 'iframe';
    }
}
