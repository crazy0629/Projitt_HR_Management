<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Support extends Model
{
    use SoftDeletes;

    protected $table = 'supports';

    protected $fillable = [
        'full_name',
        'email',
        'company_name',
        'question_category_id',
        'question',
        'preferred_response_method',
        'media_id',
    ];

    protected $casts = [
        'preferred_response_method' => 'integer',
        'question_category_id'      => 'integer',
        'media_id'                  => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
}
