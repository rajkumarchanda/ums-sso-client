<?php

namespace SmartExam\SsoClient;

use Illuminate\Support\ServiceProvider;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;
use SmartExam\SsoClient\Services\DefaultSsoUserProvisioner;
use SmartExam\SsoClient\Services\SmartExamSsoVerifier;

class SmartExamSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/smartexam-sso.php',
            'smartexam-sso'
        );

        $this->app->singleton(SmartExamSsoVerifier::class);

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
        if (config('smartexam-sso.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/sso.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/smartexam-sso.php' => config_path('smartexam-sso.php'),
            ], 'smartexam-sso-config');

            $this->publishes([
                __DIR__.'/../database/migrations/add_smartexam_id_to_users_table.php.stub' => database_path('migrations/add_smartexam_id_to_users_table.php'),
            ], 'smartexam-sso-migrations');
        }
    }
}
