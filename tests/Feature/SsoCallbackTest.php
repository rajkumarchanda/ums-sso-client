<?php

namespace SmartExam\SsoClient\Tests\Feature;

use SmartExam\SsoClient\Tests\TestCase;

class SsoCallbackTest extends TestCase
{
    public function test_it_logs_in_via_redirect_callback(): void
    {
        $token = $this->makeToken();

        $response = $this->get(route('smartexam-sso.callback', [
            'token' => $token,
            'expires_at' => time() + 120,
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_it_returns_overlay_view_for_iframe_requests(): void
    {
        $token = $this->makeToken();

        $response = $this->withHeaders([
            'Sec-Fetch-Dest' => 'iframe',
        ])->get(route('smartexam-sso.callback', [
            'token' => $token,
            'expires_at' => time() + 120,
        ]));

        $response->assertOk();
        $response->assertSee('ums-sso-complete', false);
        $this->assertGuest();
    }

    public function test_it_returns_overlay_view_when_display_query_is_set(): void
    {
        $token = $this->makeToken();

        $response = $this->get(route('smartexam-sso.callback', [
            'token' => $token,
            'expires_at' => time() + 120,
            'display' => 'overlay',
        ]));

        $response->assertOk();
        $response->assertSee('ums-sso-complete', false);
        $this->assertGuest();
    }

    public function test_it_rejects_missing_token(): void
    {
        $response = $this->get(route('smartexam-sso.callback'));

        $response->assertStatus(400);
    }
}
