<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookiesTracking extends Model
{
    protected $table = 'cookies_trackings';

    protected $fillable = [
        'session_id',
        'consent_status',      // 'accepted' | 'rejected'
        'consent_timestamp',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'consent_timestamp' => 'datetime',
    ];

    // Optional: handy constants
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
}
