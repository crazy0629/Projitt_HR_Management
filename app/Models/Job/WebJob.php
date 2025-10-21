<?php

namespace App\Models\Job;

use App\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebJob extends Model
{
    use SoftDeletes;

    protected $table = 'web_jobs';

    protected $fillable = [
        'full_name',
        'email',
        'job_id',
        'linkdin_profile_link',
        'resume_media_id',
        'cover_media_id',
    ];

    protected $casts = [
        'full_name'            => 'string',
        'email'                => 'string',
        'linkdin_profile_link' => 'string',
    ];

    /**
     * Relationships
     */
    public function resumeMedia()
    {
        return $this->belongsTo(Media::class, 'resume_media_id');
    }

    public function coverMedia()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }
}
