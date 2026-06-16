<?php

namespace SmartExam\SsoClient\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmartExamSsoAuthenticated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{sub: int|string, email: string, name: string, iss?: string, aud?: string, exp?: int, iat?: int, session_id?: string}  $payload
     */
    public function __construct(
        public Authenticatable $user,
        public array $payload,
    ) {}
}
