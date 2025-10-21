<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\AddAssessmentRequest;
use App\Http\Requests\Assessment\DeleteAssessmentRequest;
use App\Http\Requests\Assessment\EditAssessmentRequest;
use App\Http\Requests\Assessment\ListWithFilterAssessmentRequest;
use App\Models\Assessment\Assessment;
use App\Models\Assessment\AssessmentQuestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * Add a new assessment
     */
    public function add(AddAssessmentRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $object = new Assessment;
            $object->name = $request->filled('name') ? $request->input('name') : null;
            $object->description = $request->filled('description') ? $request->input('description') : null;
            $object->time_duration = $request->filled('time_duration') ? $request->input('time_duration') : null;
            $object->type_id = $request->filled('type_id') ? $request->input('type_id') : null;
            $object->points = $request->filled('points') ? $request->input('points') : 0;
            $object->created_by = Auth::id();
            $object->save();

            if ($request->filled('questions')) {
                foreach ($request->input('questions') as $q) {
                    AssessmentQuestions::create([
                        'assessment_id' => $object->id,
                        'question_id' => $q['question_id'],
                        'point' => $q['point'],
                    ]);
                }
            }

            DB::commit();

            $object->questions = AssessmentQuestions::getAssessmentQuestions($object->id);

            return $this->sendSuccess(config('messages.success'), $object, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Failed to add assessment.', $e->getMessage(), 500);
        }
    }

    /**
     * Edit an existing assessment
     */
    public function edit(EditAssessmentRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $object = Assessment::find($request->input('id'));
            $object->name = $request->filled('name') ? $request->input('name') : $object->name;
            $object->description = $request->filled('description') ? $request->input('description') : $object->description;
            $object->time_duration = $request->filled('time_duration') ? $request->input('time_duration') : $object->time_duration;
            $object->type_id = $request->filled('type_id') ? $request->input('type_id') : $object->type_id;
            $object->points = $request->filled('points') ? $request->input('points') : $object->points;
            $object->updated_by = Auth::id();
            $object->save();

            if ($request->filled('questions')) {
                AssessmentQuestions::where('assessment_id', $object->id)->delete();

                foreach ($request->input('questions') as $q) {
                    AssessmentQuestions::create([
                        'assessment_id' => $object->id,
                        'question_id' => $q['question_id'],
                        'point' => $q['point'],
                    ]);
                }
            }

            DB::commit();

            $object->questions = AssessmentQuestions::getAssessmentQuestions($object->id);

            return successResponse(config('messages.success'), $object, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Failed to update assessment.', $e->getMessage(), 500);
        }
    }

    /**
     * Delete one or more assessments
     */
    public function delete(DeleteAssessmentRequest $request): JsonResponse
    {
        $object = Assessment::whereIn('id', $request->input('ids'))->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * Get a single assessment with its questions
     */
    public function single($id): JsonResponse
    {
        $object = Assessment::find($id);
        $object->questions = AssessmentQuestions::getAssessmentQuestions($object->id);

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * List assessments with filters and pagination
     */
    public function listAllWithFilters(ListWithFilterAssessmentRequest $request): JsonResponse
    {
        $object = Assessment::filterData($request);
        $object = getData($object, $request->input('pagination'), $request->input('per_page'), $request->input('page'));

        return successResponse(config('messages.success'), $object, 200);
    }
}
