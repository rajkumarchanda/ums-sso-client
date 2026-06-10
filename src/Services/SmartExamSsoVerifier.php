<?php

namespace SmartExam\SsoClient\Services;

use InvalidArgumentException;
use RuntimeException;

class SmartExamSsoVerifier
{
    public function __construct(
        protected ?string $secret = null,
        protected ?string $expectedIssuer = null,
        protected ?string $expectedAudience = null,
    ) {}

    /**
     * @return array{iss: string, aud: string, sub: int|string, name: string, email: string, iat: int, exp: int, session_id: string}
     */
    public function verify(string $token): array
    {
        $secret = $this->secret ?? config('smartexam-sso.client_secret');
        $expectedIssuer = rtrim($this->expectedIssuer ?? config('smartexam-sso.issuer', ''), '/');
        $expectedAudience = rtrim($this->expectedAudience ?? config('smartexam-sso.audience', ''), '/');

        if (blank($secret)) {
            throw new RuntimeException('SSO client secret is not configured.');
        }

        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Malformed SSO token.');
        }

        [$encodedPayload, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $encodedPayload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new InvalidArgumentException('Invalid SSO token signature.');
        }

        $payload = json_decode(base64_decode($encodedPayload, true), true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Invalid SSO token payload.');
        }

        if (rtrim($payload['iss'] ?? '', '/') !== $expectedIssuer) {
            throw new InvalidArgumentException('Unexpected token issuer.');
        }

        if (rtrim($payload['aud'] ?? '', '/') !== $expectedAudience) {
            throw new InvalidArgumentException('Unexpected token audience.');
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new InvalidArgumentException('SSO token has expired.');
        }

        return $payload;
    }

    public function verifyState(?string $received, ?string $expected): void
    {
        if ($expected === null || $expected === '') {
            return;
        }

        if (! is_string($received) || ! hash_equals($expected, $received)) {
            throw new InvalidArgumentException('Invalid SSO state parameter.');
        }
    }
}
