<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CookiesVisit extends Model
{
    use SoftDeletes;

    protected $table = 'cookies_visits';

    protected $fillable = [
        'session_id',
        'page_url',
        'page_title',
        'referrer',
        'user_agent',
        'screen_resolution',
        'viewport_size',
        'language',
        'timezone',
        'page_type',
        'is_first_visit',
        'ip_address',
    ];

    protected $casts = [
        'is_first_visit' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
