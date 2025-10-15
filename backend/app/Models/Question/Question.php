<?php

namespace App\Models\Question;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    /**
     * Table name for the model.
     *
     * @var string
     */
    protected $table = 'questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question_name',
        'answer_type',
        'options',
        'tags',
        'is_required',
        'correct_answer',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'tags' => 'array',
        'is_required' => 'boolean',
    ];

    /**
     * The attributes that should be hidden in arrays or JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    /**
     * Retrieve a single question by ID.
     */
    public static function singleObject(int $id): ?self
    {
        return self::find($id);
    }

    /**
     * Apply filters to the Question model based on request input.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function filterData($request)
    {
        $filteredData = self::query();

        if (! empty($request->input('name'))) {
            $filteredData->where('question_name', 'LIKE', '%'.$request->input('name').'%');
        }

        if (! empty($request->input('answer_type'))) {
            $filteredData->where('answer_type', $request->input('answer_type'));
        }

        return $filteredData;
    }

    /**
     * Perform a limited search for intellisense/autocomplete functionality.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public static function intellisenseSearch($request)
    {
        $query = self::select('id', 'question_name', 'tags');

        if (! empty($request->input('name'))) {
            $query->where('question_name', 'LIKE', '%'.$request->input('name').'%');
        }

        if (! empty($request->input('tags'))) {
            foreach ($request->input('tags') as $questionId) {
                $query->whereJsonContains('tags', $questionId);
            }
        }

        return $query->limit(50)->get();
    }
}
