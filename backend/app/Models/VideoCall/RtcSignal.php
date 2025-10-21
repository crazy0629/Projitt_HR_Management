<?php

namespace App\Models\VideoCall;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtcSignal extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'from_user_id',
        'to_user_id',
        'type',
        'payload',
        'acknowledged_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'acknowledged_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
