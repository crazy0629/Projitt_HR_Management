<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Role extends Model {
    
    use SoftDeletes, HasFactory;

    protected $table = 'roles';

    protected $fillable = [ 'name', 'slug', 'description', 'guard_name', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = [ 'updated_at', 'updated_by', 'deleted_by', 'deleted_at'];

    public static function boot(){
        parent::boot();
        static::creating(function ($model) {
            $model->slug = Str::random(5) . '-' . Str::slug($model->name);
        });
    }

    public static function filterData($request){

        $filteredData = self::query();
        if (!empty($request->input('name'))) {
            $filteredData = $filteredData->Where('name', 'LIKE', '%' . $request->input('name') . '%');
        }
        return $filteredData;
    }

    public static function searchData($request){

        $filteredData = self::query();
        if (!empty($request->input('name'))) {
            $filteredData = $filteredData->Where('name', 'LIKE', '%' . $request->input('name') . '%');
        }
        return $filteredData->limit(30)->get();
    }

}
