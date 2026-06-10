<?php

namespace SmartExam\SsoClient\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;

class DefaultSsoUserProvisioner implements SsoUserProvisioner
{
    public function fromPayload(array $payload): Authenticatable
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable> $model */
        $model = config('smartexam-sso.user_model', 'App\\Models\\User');

        return $model::query()->updateOrCreate(
            ['email' => $payload['email']],
            [
                'name' => $payload['name'],
                'smartexam_id' => $payload['sub'],
            ]
        );
    }
}
