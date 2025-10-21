<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model {

    use SoftDeletes;

    protected $table = 'assessments';

    protected $fillable = [
        'name',
        'description',
        'time_duration',
        'type_id',
        'points',
        'created_by',
        'updated_by',
        'deleted_by',
        'status'
    ];

    protected $casts = [
        'type_id' => 'integer',
        'time_duration' => 'integer',
        'points' => 'integer',
    ];
    
    public static function filterData($request){

        $filteredData = self::query();
        if (!empty($request->input('name'))) {
            $filteredData = $filteredData->Where('name', 'LIKE', '%' . $request->input('name') . '%');
        }
        return $filteredData;
    }

}
