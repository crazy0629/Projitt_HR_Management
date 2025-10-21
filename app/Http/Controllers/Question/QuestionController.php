<?php

namespace App\Http\Controllers\Question;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AddQuestionRequest;
use App\Http\Requests\Question\EditQuestionRequest;
use App\Http\Requests\Question\DeleteQuestionRequest;
use App\Http\Requests\Question\ListWithFiltersQuestionRequest;
use App\Models\Question\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    /**
     * Store a new question.
     */
    public function add(AddQuestionRequest $request): JsonResponse
    {
        $question = new Question();

        $question->question_name = $request->input('question_name');
        $question->answer_type = $request->input('answer_type');
        $question->options = $request->input('options');
        $question->is_required = $request->input('is_required', false);
        $question->tags = $request->input('tags');

        $question->created_by = Auth::id();
        $question->save();

        $question = Question::singleObject($question->id);

        return $this->sendSuccess($question, config('messages.success'));
    }

    /**
     * Update an existing question.
     */
    public function edit(EditQuestionRequest $request): JsonResponse
    {
        $question = Question::findOrFail($request->input('id'));

        $question->question_name = $request->input('question_name');
        $question->answer_type = $request->input('answer_type');
        $question->options = $request->input('options');
        $question->is_required = $request->input('is_required', false);
        $question->tags = $request->input('tags');

        $question->updated_by = Auth::id();
        $question->save();

        $question = Question::singleObject($question->id);

        return $this->sendSuccess($question, config('messages.success'));
    }

    /**
     * Soft delete one or more questions.
     */
    public function delete(DeleteQuestionRequest $request): JsonResponse
    {
        $deleted = Question::whereIn('id', $request->input('ids'))->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);

        return $this->sendSuccess($deleted, config('messages.success'));
    }

    /**
     * Get a single question.
     */
    public function single($id): JsonResponse
    {
        $question = Question::singleObject($id);
        return $this->sendSuccess($question, config('messages.success'));
    }

    /**
     * List questions with filters and optional pagination.
     */
    public function listAllWithFilters(ListWithFiltersQuestionRequest $request): JsonResponse
    {
        $query = Question::filterData($request);
        $data = $this->getData(
            $query,
            $request->input('pagination'),
            $request->input('per_page'),
            $request->input('page')
        );

        return $this->sendSuccess($data, config('messages.success'));
    }

    /**
     * Intellisense search on questions.
     */
    public function intellisenseSearch(Request $request): JsonResponse
    {
        $results = Question::intellisenseSearch($request);
        return $this->sendSuccess($results, config('messages.success'));
    }
}
