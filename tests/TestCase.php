<?php

namespace SmartExam\SsoClient\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use SmartExam\SsoClient\SmartExamSsoServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SmartExamSsoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.url', 'https://mail.example.com');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('smartexam-sso.issuer', 'https://ums.example.com');
        $app['config']->set('smartexam-sso.client_secret', 'test-client-secret');
        $app['config']->set('smartexam-sso.client_key', 'test-client-key');
        $app['config']->set('smartexam-sso.audience', 'https://mail.example.com');
        $app['config']->set('smartexam-sso.callback_url', 'https://mail.example.com/sso/callback');
        $app['config']->set('smartexam-sso.after_login_redirect', '/dashboard');
        $app['config']->set('smartexam-sso.require_state', false);
        $app['config']->set('smartexam-sso.user_model', Fixtures\User::class);
        $app['config']->set('auth.providers.users.model', Fixtures\User::class);
        $app['config']->set('auth.guards.web.driver', 'session');
        $app['config']->set('auth.guards.web.provider', 'users');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('smartexam_id')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function makeToken(array $overrides = []): string
    {
        $payload = array_merge([
            'iss' => 'https://ums.example.com',
            'aud' => 'https://mail.example.com',
            'sub' => 42,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'iat' => time(),
            'exp' => time() + 120,
            'session_id' => 'sess-123',
        ], $overrides);

        $encodedPayload = base64_encode(json_encode($payload));

        return $encodedPayload.'.'.hash_hmac('sha256', $encodedPayload, 'test-client-secret');
    }
}
