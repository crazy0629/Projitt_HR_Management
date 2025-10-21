<?php

namespace App\Models\Team;

use Illuminate\Database\Eloquent\Model;

class TeamUser extends Model
{
    protected $table = 'team_users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'team_id',
        'user_id',
    ];

    /**
     * Disable timestamps if you don’t need them.
     * (Remove this if you want created_at/updated_at tracked.)
     */
    public $timestamps = true;
}
