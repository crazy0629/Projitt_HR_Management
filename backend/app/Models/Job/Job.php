<?php

namespace App\Models\Job;

use App\Models\Country\Country;
use App\Models\Master\Master;
use App\Models\Media\Media;
use App\Models\Question\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'no_of_job_opening',
        'department_id',
        'employment_type_id',
        'location_type_id',
        'question_ids',
        'skill_ids',
        'media_ids',
        'state',
        'country_id',
        'salary_from',
        'salary_to',
        'deadline',
        'status',
    ];

    protected $casts = [
        'skill_ids' => 'array',
        'question_ids' => 'array',
        'media_ids' => 'array',
        'salary_from' => 'decimal:2',
        'salary_to' => 'decimal:2',
        'deadline' => 'date',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'skill_ids',
        'media_ids',
        'question_ids',
    ];

    protected $appends = [
        'skills',
        'media',
        'questions',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'department_id');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'employment_type_id');
    }

    public function locationType(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'location_type_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // Accessors for computed relations
    public function getSkillsAttribute()
    {
        return Master::whereIn('id', $this->skill_ids ?? [])->get();
    }

    public function getMediaAttribute()
    {
        return Media::whereIn('id', $this->media_ids ?? [])->get();
    }

    public function getQuestionsAttribute()
    {
        return Question::whereIn('id', $this->question_ids ?? [])->get();
    }

    /**
     * Retrieve a single job record with all related data including resolved skills.
     */
    public static function singleObject(int $jobId): ?self
    {
        return self::with([
            'department',
            'employmentType',
            'country',
            'locationType',
        ])->find($jobId);
        // skills, media, questions are automatically appended via accessors
    }

    /**
     * Apply filters to the Job model based on request input.
     */
    public static function filterData($request)
    {
        $filteredData = self::query();

        if (! empty($request->input('title'))) {
            $filteredData->where('title', 'LIKE', '%'.$request->input('title').'%');
        }

        if (! empty($request->input('department_ids'))) {
            $filteredData->whereIn('department_id', $request->input('department_ids'));
        }

        if (! empty($request->input('employment_type_ids'))) {
            $filteredData->whereIn('employment_type_id', $request->input('employment_type_ids'));
        }

        if (! empty($request->input('location_type_ids'))) {
            $filteredData->whereIn('location_type_id', $request->input('location_type_ids'));
        }

        if (! empty($request->input('country_ids'))) {
            $filteredData->whereIn('country_id', $request->input('country_ids'));
        }

        if (! empty($request->input('state'))) {
            $filteredData->where('state', $request->input('state'));
        }

        if (! empty($request->input('salary_from'))) {
            $filteredData->where('salary_from', '>=', $request->input('salary_from'));
        }

        if (! empty($request->input('salary_to'))) {
            $filteredData->where('salary_to', '<=', $request->input('salary_to'));
        }

        if (! empty($request->input('deadline_before'))) {
            $filteredData->whereDate('deadline', '<=', $request->input('deadline_before'));
        }

        if (! empty($request->input('deadline_after'))) {
            $filteredData->whereDate('deadline', '>=', $request->input('deadline_after'));
        }

        if (! empty($request->input('skill_ids'))) {
            foreach ($request->input('skill_ids') as $skillId) {
                $filteredData->whereJsonContains('skill_ids', $skillId);
            }
        }

        if (! empty($request->input('question_ids'))) {
            foreach ($request->input('question_ids') as $questionId) {
                $filteredData->whereJsonContains('question_ids', $questionId);
            }
        }

        return $filteredData->with([
            'department',
            'employmentType',
            'country',
            'locationType',
        ]);
    }

    /**
     * Perform a limited search for intellisense/autocomplete functionality.
     */
    public static function intellisenseSearch($request)
    {
        $query = self::select('id', 'title');

        if (! empty($request->input('title'))) {
            $query->where('title', 'LIKE', '%'.$request->input('title').'%');
        }

        return $query->limit(50)->get()->makeHidden(['skills', 'media', 'questions']);
    }
}
