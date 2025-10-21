<?php

namespace App\Models\Country;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsStates extends Model {
    
        
    use HasFactory, SoftDeletes;

    protected $table = 'us_states';

    protected $fillable = [
        'name',
        'description',
        'abbreviation',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
