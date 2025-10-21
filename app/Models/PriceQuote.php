<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PriceQuote extends Model
{
    use SoftDeletes;

    protected $table = 'price_quotes';

    protected $fillable = [
        'first_name',
        'last_name',
        'contact_code',
        'contact_no',
        'company_name',
        'no_of_employee',
        'email',
        'contact_email',
        'service',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'service' => 'array',
    ];

    protected $dates = ['deleted_at'];

}
