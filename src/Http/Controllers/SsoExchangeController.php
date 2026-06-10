<?php

namespace SmartExam\SsoClient\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;
use SmartExam\SsoClient\Services\SmartExamSsoVerifier;

class SsoExchangeController extends Controller
{
    public function __construct(
        protected SmartExamSsoVerifier $verifier,
        protected SsoUserProvisioner $provisioner,
    ) {}

    /**
     * Used by popup / iframe JS helpers after reading token from callback URL.
     *
     * POST /api/sso/exchange
     * Body: { "token": "...", "expiresAt": 1717939200, "state": "..." }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'expiresAt' => 'nullable|integer',
            'state' => 'nullable|string|max:255',
        ]);

        if (($data['expiresAt'] ?? 0) > 0 && $data['expiresAt'] < time()) {
            return response()->json(['message' => 'SSO token has expired.'], 401);
        }

        try {
            $this->verifier->verifyState($data['state'] ?? null, session(config('smartexam-sso.state_session_key')));
            $payload = $this->verifier->verify($data['token']);
        } catch (InvalidArgumentException $exception) {
            Log::warning('SmartExam SSO exchange rejected', ['reason' => $exception->getMessage()]);

            return response()->json(['message' => $exception->getMessage()], 401);
        }

        session()->forget(config('smartexam-sso.state_session_key'));

        $user = $this->provisioner->fromPayload($payload);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Authenticated',
            'user' => [
                'id' => $user->getAuthIdentifier(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ],
        ]);
    }
}
