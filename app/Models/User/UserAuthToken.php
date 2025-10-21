<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\PersonalAccessToken;

class UserAuthToken extends PersonalAccessToken
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_auth_tokens'; // Use custom table

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'actual_token',  // Custom field (plain token)
        'ip',
        'agent',
        'user_id',
        'expires_at',
    ];

    protected $hidden = [
        'token', 
        'actual_token',
    ];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at',
        'expires_at',
    ];
}
