<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\AddJobApplicantQuestionAnswerRequest;
use App\Models\Job\JobApplicantQuestionAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicationQuestionAnswerController extends Controller
{
    /**
     * Get a single job applicant.
     */
    public function single(Request $request): JsonResponse
    {

        $object = JobApplicantQuestionAnswer::singleObject($request->input('job_id'), $request->input('applicant_id'));

        return $this->sendSuccess($object, config('messages.success'));
    }

    public function submitApplicantAnswer(AddJobApplicantQuestionAnswerRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Ensure 'answer' is always stored as JSON string
            $rawAnswer = $validated['answer'];
            $validated['answer'] = is_array($rawAnswer) ? json_encode($rawAnswer) : json_encode([$rawAnswer]);

            // Check if answer already exists
            $answer = JobApplicantQuestionAnswer::where([
                'job_id' => $validated['job_id'],
                'applicant_id' => $validated['applicant_id'],
                'question_id' => $validated['question_id'],
            ])->first();

            if ($answer) {
                $answer->answer = $validated['answer'];
                $answer->updated_by = auth()->id();
                $answer->save();
            } else {
                $answer = new JobApplicantQuestionAnswer;
                $answer->fill($validated);
                $answer->created_by = auth()->id();
                $answer->updated_by = auth()->id();
                $answer->save();
            }

            return $this->sendSuccess($answer, 'Answer saved successfully.');
        } catch (\Exception $e) {
            return $this->sendError(config('messages.error'), $e->getMessage());
        }
    }
}
