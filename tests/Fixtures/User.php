<?php

namespace SmartExam\SsoClient\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'smartexam_id',
    ];
}
