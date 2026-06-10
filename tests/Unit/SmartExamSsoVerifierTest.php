<?php

namespace SmartExam\SsoClient\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SmartExam\SsoClient\Services\SmartExamSsoVerifier;

class SmartExamSsoVerifierTest extends TestCase
{
    public function test_it_verifies_a_valid_token(): void
    {
        $secret = 'test-client-secret';
        $issuer = 'https://ums.example.com';
        $audience = 'https://mail.example.com';

        $encodedPayload = base64_encode(json_encode([
            'iss' => $issuer,
            'aud' => $audience,
            'sub' => 42,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'iat' => time(),
            'exp' => time() + 120,
            'session_id' => 'sess-123',
        ]));

        $token = $encodedPayload.'.'.hash_hmac('sha256', $encodedPayload, $secret);

        $verifier = new SmartExamSsoVerifier($secret, $issuer, $audience);
        $payload = $verifier->verify($token);

        $this->assertSame(42, $payload['sub']);
        $this->assertSame('jane@example.com', $payload['email']);
    }

    public function test_it_rejects_an_invalid_signature(): void
    {
        $encodedPayload = base64_encode(json_encode([
            'iss' => 'https://ums.example.com',
            'aud' => 'https://mail.example.com',
            'sub' => 1,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'iat' => time(),
            'exp' => time() + 120,
            'session_id' => 'sess',
        ]));

        $token = $encodedPayload.'.invalid-signature';

        $verifier = new SmartExamSsoVerifier(
            'secret',
            'https://ums.example.com',
            'https://mail.example.com'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SSO token signature.');

        $verifier->verify($token);
    }

    public function test_it_rejects_expired_tokens(): void
    {
        $secret = 'secret';
        $encodedPayload = base64_encode(json_encode([
            'iss' => 'https://ums.example.com',
            'aud' => 'https://mail.example.com',
            'sub' => 1,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'iat' => time() - 300,
            'exp' => time() - 60,
            'session_id' => 'sess',
        ]));

        $token = $encodedPayload.'.'.hash_hmac('sha256', $encodedPayload, $secret);

        $verifier = new SmartExamSsoVerifier(
            $secret,
            'https://ums.example.com',
            'https://mail.example.com'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SSO token has expired.');

        $verifier->verify($token);
    }
}
