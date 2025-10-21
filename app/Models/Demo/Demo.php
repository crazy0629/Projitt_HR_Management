<?php

namespace App\Models\Demo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Demo extends Model
{
    use SoftDeletes;

    protected $table = 'demos';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'contact_code',
        'contact_no',
        'company',
        'company_size',
        'industry',
        'how_hear_bout_us',
        'service',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'service' => 'array',
    ];

}
