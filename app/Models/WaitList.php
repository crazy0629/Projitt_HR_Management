<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitList extends Model {
    
    protected $table = 'wait_list';

    protected $fillable = [
        'name',
        'email',
        'company_name',
        'company_email',
        'created_at',
    ];
}
