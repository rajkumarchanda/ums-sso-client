<?php

namespace SmartExam\SsoClient;

use Illuminate\Support\ServiceProvider;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;
use SmartExam\SsoClient\Services\DefaultSsoUserProvisioner;
use SmartExam\SsoClient\Services\SmartExamSsoVerifier;
use SmartExam\SsoClient\Services\SsoAuthenticationService;

class SmartExamSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/smartexam-sso.php',
            'smartexam-sso'
        );

        $this->app->singleton(SmartExamSsoVerifier::class);
        $this->app->singleton(SsoAuthenticationService::class);

        $this->app->bind(SsoUserProvisioner::class, function ($app) {
            $custom = config('smartexam-sso.user_provisioner');

            if (is_string($custom) && class_exists($custom)) {
                return $app->make($custom);
            }

            return $app->make(DefaultSsoUserProvisioner::class);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'smartexam-sso');

        if (config('smartexam-sso.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/sso.php');
        }

        $this->warnWhenMisconfigured();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/smartexam-sso.php' => config_path('smartexam-sso.php'),
            ], 'smartexam-sso-config');

            $this->publishes([
                __DIR__.'/../database/migrations/add_smartexam_id_to_users_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_smartexam_id_to_users_table.php'),
            ], 'smartexam-sso-migrations');

            $this->publishes([
                __DIR__.'/../resources/views/login-script.blade.php' => resource_path('views/vendor/smartexam-sso/login-script.blade.php'),
            ], 'smartexam-sso-views');
        }
    }

    protected function warnWhenMisconfigured(): void
    {
        if ($this->app->environment('production')) {
            return;
        }

        foreach (['issuer', 'client_key', 'client_secret', 'callback_url'] as $key) {
            if (blank(config("smartexam-sso.{$key}"))) {
                logger()->warning("SmartExam SSO: config smartexam-sso.{$key} is not set.");
            }
        }
    }
}
