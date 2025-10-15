<?php

namespace App\Http\Controllers\Question;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AddCodingQuestionRequest;
use App\Http\Requests\Question\DeleteCodingQuestionRequest;
use App\Http\Requests\Question\EditCodingQuestionRequest;
use App\Http\Requests\Question\ListWithFiltersCodingQuestionRequest;
use App\Models\Question\CodingQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CodingQuestionController extends Controller
{
    /**
     * Add a new coding question
     */
    public function add(AddCodingQuestionRequest $request): JsonResponse
    {
        $object = new CodingQuestion;
        $object->title = $request->input('title');
        $object->description = $request->input('description');
        $object->language = $request->input('language', []);
        $object->total_point = $request->input('total_point');
        $object->time_limit = $request->input('time_limit');
        $object->created_by = Auth::id();
        $object->save();

        $object = CodingQuestion::find($object->id);

        return $this->sendSuccess(config('messages.success'), $object, 200);
    }

    /**
     * Edit an existing coding question
     */
    public function edit(EditCodingQuestionRequest $request): JsonResponse
    {
        $object = CodingQuestion::find($request->input('id'));
        $object->title = $request->filled('title') ? $request->input('title') : $object->title;
        $object->description = $request->filled('description') ? $request->input('description') : $object->description;
        $object->language = $request->filled('language') ? $request->input('language') : $object->language;
        $object->total_point = $request->filled('total_point') ? $request->input('total_point') : $object->total_point;
        $object->time_limit = $request->filled('time_limit') ? $request->input('time_limit') : $object->time_limit;
        $object->updated_by = Auth::id();
        $object->save();

        $object = CodingQuestion::find($request->input('id'));

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * Delete one or more coding questions
     */
    public function delete(DeleteCodingQuestionRequest $request): JsonResponse
    {
        $object = CodingQuestion::whereIn('id', $request->input('ids'))->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * Get a single coding question
     */
    public function single($id): JsonResponse
    {
        $object = CodingQuestion::select('id', 'title', 'description', 'language', 'total_point', 'time_limit')
            ->where('id', $id)
            ->first();

        return successResponse(config('messages.success'), $object, 200);
    }

    /**
     * List all coding questions with filters and pagination
     */
    public function listAllWithFilters(ListWithFiltersCodingQuestionRequest $request): JsonResponse
    {
        $object = CodingQuestion::filterData($request);
        $object = getData(
            $object,
            $request->input('pagination'),
            $request->input('per_page'),
            $request->input('page')
        );

        return successResponse(config('messages.success'), $object, 200);
    }
}
