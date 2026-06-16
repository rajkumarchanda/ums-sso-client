<?php

namespace SmartExam\SsoClient\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use SmartExam\SsoClient\Services\SsoAuthenticationService;
use Symfony\Component\HttpFoundation\Response;

class SsoExchangeController extends Controller
{
    public function __construct(
        protected SsoAuthenticationService $authentication,
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

        try {
            $this->authentication->assertNotExpired($data['expiresAt'] ?? null);
            $payload = $this->authentication->verifyToken($data['token'], $data['state'] ?? null);
            $user = $this->authentication->loginFromPayload($request, $payload);
        } catch (InvalidArgumentException $exception) {
            Log::warning('SmartExam SSO exchange rejected', ['reason' => $exception->getMessage()]);

            return response()->json(['message' => $exception->getMessage()], 401);
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'not configured')) {
                Log::error('SmartExam SSO exchange misconfigured', ['reason' => $exception->getMessage()]);

                return response()->json(['message' => $exception->getMessage()], 503);
            }

            Log::error('SmartExam SSO exchange failed', ['reason' => $exception->getMessage()]);

            return response()->json(['message' => 'SSO login failed.'], 500);
        }

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
