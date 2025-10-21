<?php

namespace App\Models\Country;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model {
    
    use HasFactory, SoftDeletes;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'iso',
        'iso3',
        'dial_code',
        'contact_code',
        'flag_svg',
        'currency_sign',
        'timezone',
        'language',
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
