<?php

namespace SmartExam\SsoClient\Services;

use InvalidArgumentException;
use RuntimeException;

class SmartExamSsoVerifier
{
    /** @var list<string> */
    protected array $requiredClaims = ['sub', 'email', 'name'];

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

        $this->validateRequiredClaims($payload);

        return $payload;
    }

    public function verifyState(?string $received, ?string $expected): void
    {
        $requireState = (bool) config('smartexam-sso.require_state', true);

        if ($expected === null || $expected === '') {
            if ($requireState) {
                throw new InvalidArgumentException('Missing SSO state in session.');
            }

            return;
        }

        if (! is_string($received) || ! hash_equals($expected, $received)) {
            throw new InvalidArgumentException('Invalid SSO state parameter.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function validateRequiredClaims(array $payload): void
    {
        foreach ($this->requiredClaims as $claim) {
            if (! isset($payload[$claim]) || $payload[$claim] === '') {
                throw new InvalidArgumentException("Missing required SSO claim: {$claim}.");
            }
        }

        if (! is_string($payload['email']) || ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email claim in SSO token.');
        }
    }
}
