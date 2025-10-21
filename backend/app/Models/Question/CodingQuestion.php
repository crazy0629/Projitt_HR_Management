<?php

namespace App\Models\Question;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CodingQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'coding_questions';

    protected $fillable = [
        'title',
        'description',
        'language',
        'total_point',
        'time_limit',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'language' => 'array',
        'total_point' => 'integer',
        'time_limit' => 'integer',
    ];

    public static function filterData($request)
    {

        $filteredData = self::query();
        if (! empty($request->input('name'))) {
            $filteredData = $filteredData->Where('name', 'LIKE', '%'.$request->input('name').'%');
        }

        return $filteredData;
    }
}
