<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Master extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'masters';

    protected $fillable = ['name', 'slug', 'description', 'type_id', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['updated_at', 'updated_by', 'deleted_by', 'deleted_at'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = Str::random(5).'-'.Str::slug($model->name);
        });
    }

    public static function filterData($request)
    {

        $filteredData = self::where('type_id', $request->input('type_id'))->select('id', 'name', 'type_id', 'slug', 'description');
        if (! empty($request->input('name'))) {
            $filteredData = $filteredData->Where('name', 'LIKE', '%'.$request->input('name').'%');
        }

        return $filteredData;
    }

    public static function searchData($request)
    {

        $filteredData = self::where('type_id', $request->input('type_id'))->select('id', 'name', 'slug');
        if (! empty($request->input('name'))) {
            $filteredData = $filteredData->Where('name', 'LIKE', '%'.$request->input('name').'%');
        }

        return $filteredData->limit(30)->get();
    }
}
