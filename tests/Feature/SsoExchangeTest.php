<?php

namespace SmartExam\SsoClient\Tests\Feature;

use Illuminate\Support\Facades\Event;
use SmartExam\SsoClient\Events\SmartExamSsoAuthenticated;
use SmartExam\SsoClient\Tests\TestCase;

class SsoExchangeTest extends TestCase
{
    public function test_it_exchanges_a_valid_token_and_logs_the_user_in(): void
    {
        Event::fake([SmartExamSsoAuthenticated::class]);

        $token = $this->makeToken();

        $response = $this->postJson(route('smartexam-sso.exchange'), [
            'token' => $token,
            'expiresAt' => time() + 120,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Authenticated')
            ->assertJsonPath('user.email', 'jane@example.com');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'smartexam_id' => 42,
        ]);

        Event::assertDispatched(SmartExamSsoAuthenticated::class);
    }

    public function test_it_rejects_an_invalid_signature(): void
    {
        $response = $this->postJson(route('smartexam-sso.exchange'), [
            'token' => 'invalid.token',
            'expiresAt' => time() + 120,
        ]);

        $response->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_it_rejects_expired_expires_at(): void
    {
        $response = $this->postJson(route('smartexam-sso.exchange'), [
            'token' => $this->makeToken(),
            'expiresAt' => time() - 60,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'SSO token has expired.');
    }

    public function test_it_rejects_missing_state_when_required(): void
    {
        config(['smartexam-sso.require_state' => true]);

        $response = $this->postJson(route('smartexam-sso.exchange'), [
            'token' => $this->makeToken(),
            'expiresAt' => time() + 120,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Missing SSO state in session.');
    }

    public function test_it_accepts_matching_state(): void
    {
        config(['smartexam-sso.require_state' => true]);

        $response = $this->withSession([
            config('smartexam-sso.state_session_key') => 'test-state-123',
        ])->postJson(route('smartexam-sso.exchange'), [
            'token' => $this->makeToken(),
            'expiresAt' => time() + 120,
            'state' => 'test-state-123',
        ]);

        $response->assertOk();
        $this->assertAuthenticated();
    }

    public function test_it_returns_service_unavailable_when_secret_missing(): void
    {
        config(['smartexam-sso.client_secret' => null]);

        $response = $this->postJson(route('smartexam-sso.exchange'), [
            'token' => $this->makeToken(),
            'expiresAt' => time() + 120,
        ]);

        $response->assertStatus(503);
    }

    public function test_it_rejects_tokens_missing_required_claims(): void
    {
        $response = $this->postJson(route('smartexam-sso.exchange'), [
            'token' => $this->makeToken(['email' => '']),
            'expiresAt' => time() + 120,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Missing required SSO claim: email.');
    }
}
