<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\AddWebJobRequest;
use App\Models\Job\WebJob;
use Illuminate\Http\JsonResponse;

class WebJobController extends Controller
{
    public function add(AddWebJobRequest $request): JsonResponse
    {
        $data = $request->validated();
    
        $job = new WebJob();
        $job->full_name            = $data['full_name'] ?? null;
        $job->email                = $data['email'] ?? null;
        $job->linkdin_profile_link = $data['linkdin_profile_link'] ?? null;
        $job->job_id               = $data['job_id'] ?? null;
        $job->resume_media_id      = $data['resume_media_id'] ?? null;
        $job->cover_media_id       = $data['cover_media_id'] ?? null;
        $job->save();
    
        $job->load(['resumeMedia', 'coverMedia'])->refresh();
    
        return $this->sendSuccess(config('messages.success'), $job);
    }
    
}
