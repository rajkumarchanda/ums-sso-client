<?php

namespace SmartExam\SsoClient\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface SsoUserProvisioner
{
    /**
     * Create or update a local user from verified SmartExam token claims.
     *
     * @param  array{sub: int|string, email: string, name: string, iss?: string, aud?: string, exp?: int, iat?: int, session_id?: string}  $payload
     */
    public function fromPayload(array $payload): Authenticatable;
}
