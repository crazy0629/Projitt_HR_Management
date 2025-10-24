<?php

namespace App\Services\Psychometric;

use App\Models\Psychometric\PsychometricDimension;
use App\Models\Psychometric\PsychometricQuestion;
use App\Models\Psychometric\PsychometricQuestionOption;
use App\Models\Psychometric\PsychometricTest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PsychometricTestService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PsychometricTest>|LengthAwarePaginator<PsychometricTest>
     */
    public function list(array $filters = [], bool $paginate = true, int $perPage = 15)
    {
        $query = PsychometricTest::query()
            ->with(['dimensions', 'questions' => function ($q) {
                $q->with('options');
            }])
            ->orderByDesc('created_at');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['published'])) {
            $query->where('is_published', (bool) $filters['published']);
        }

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function create(array $data): PsychometricTest
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
            }

            $dimensions = $data['dimensions'] ?? [];
            $questions = $data['questions'] ?? [];
            unset($data['dimensions'], $data['questions']);

            $test = PsychometricTest::create(array_merge($data, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            $dimensionMap = $this->syncDimensions($test, $dimensions);
            $this->syncQuestions($test, $questions, $dimensionMap);

            return $test->fresh(['dimensions', 'questions.options']);
        });
    }

    public function update(PsychometricTest $test, array $data): PsychometricTest
    {
        return DB::transaction(function () use ($test, $data) {
            $userId = Auth::id();
            $dimensions = $data['dimensions'] ?? null;
            $questions = $data['questions'] ?? null;
            unset($data['dimensions'], $data['questions']);

            if (! empty($data)) {
                $test->fill($data);
                $test->updated_by = $userId;
                $test->save();
            }

            $dimensionMap = [];
            if (is_array($dimensions)) {
                $dimensionMap = $this->syncDimensions($test, $dimensions);
            } else {
                $dimensionMap = $test->dimensions()->get()->keyBy('key')->map(fn ($dim) => (int) $dim->id)->toArray();
            }

            if (is_array($questions)) {
                $this->syncQuestions($test, $questions, $dimensionMap);
            }

            return $test->fresh(['dimensions', 'questions.options']);
        });
    }

    public function delete(PsychometricTest $test): void
    {
        $test->deleted_by = Auth::id();
        $test->save();
        $test->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $dimensions
     * @return array<string, int>
     */
    protected function syncDimensions(PsychometricTest $test, array $dimensions): array
    {
        $map = [];

        foreach ($dimensions as $dimension) {
            $action = $dimension['_action'] ?? (isset($dimension['id']) ? 'update' : 'create');

            if ($action === 'delete' && isset($dimension['id'])) {
                PsychometricDimension::where('psychometric_test_id', $test->id)
                    ->where('id', $dimension['id'])
                    ->delete();
                continue;
            }

            if ($action === 'update' && isset($dimension['id'])) {
                $model = PsychometricDimension::where('psychometric_test_id', $test->id)
                    ->where('id', $dimension['id'])
                    ->first();

                if ($model) {
                    $model->update([
                        'key' => $dimension['key'] ?? $model->key,
                        'name' => $dimension['name'] ?? $model->name,
                        'description' => $dimension['description'] ?? $model->description,
                        'weight' => $dimension['weight'] ?? $model->weight,
                        'metadata' => $dimension['metadata'] ?? $model->metadata,
                    ]);
                    $map[$model->key] = $model->id;
                }

                continue;
            }

            $model = PsychometricDimension::create([
                'psychometric_test_id' => $test->id,
                'key' => $dimension['key'],
                'name' => $dimension['name'],
                'description' => $dimension['description'] ?? null,
                'weight' => $dimension['weight'] ?? 1,
                'metadata' => $dimension['metadata'] ?? null,
            ]);

            $map[$model->key] = $model->id;
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @param  array<string, int>  $dimensionMap
     */
    protected function syncQuestions(PsychometricTest $test, array $questions, array $dimensionMap): void
    {
        foreach ($questions as $question) {
            $action = $question['_action'] ?? (isset($question['id']) ? 'update' : 'create');

            if ($action === 'delete' && isset($question['id'])) {
                PsychometricQuestion::where('psychometric_test_id', $test->id)
                    ->where('id', $question['id'])
                    ->delete();
                continue;
            }

            $dimensionId = null;
            if (! empty($question['dimension_id'])) {
                $dimensionId = (int) $question['dimension_id'];
            } elseif (! empty($question['dimension_key']) && isset($dimensionMap[$question['dimension_key']])) {
                $dimensionId = $dimensionMap[$question['dimension_key']];
            }

            if ($action === 'update' && isset($question['id'])) {
                $model = PsychometricQuestion::where('psychometric_test_id', $test->id)
                    ->where('id', $question['id'])
                    ->first();

                if (! $model) {
                    continue;
                }

                $model->update([
                    'dimension_id' => $dimensionId,
                    'reference_code' => $question['reference_code'] ?? $model->reference_code,
                    'question_text' => $question['question_text'] ?? $model->question_text,
                    'question_type' => $question['question_type'] ?? $model->question_type,
                    'weight' => $question['weight'] ?? $model->weight,
                    'is_required' => $question['is_required'] ?? $model->is_required,
                    'randomize_options' => $question['randomize_options'] ?? $model->randomize_options,
                    'base_order' => $question['base_order'] ?? $model->base_order,
                    'metadata' => $question['metadata'] ?? $model->metadata,
                ]);

                $this->syncOptions($model, $question['options'] ?? null);
                continue;
            }

            $model = PsychometricQuestion::create([
                'psychometric_test_id' => $test->id,
                'dimension_id' => $dimensionId,
                'reference_code' => $question['reference_code'] ?? null,
                'question_text' => $question['question_text'],
                'question_type' => $question['question_type'],
                'weight' => $question['weight'] ?? 1,
                'is_required' => $question['is_required'] ?? true,
                'randomize_options' => $question['randomize_options'] ?? false,
                'base_order' => $question['base_order'] ?? null,
                'metadata' => $question['metadata'] ?? null,
            ]);

            $this->syncOptions($model, $question['options'] ?? null);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $options
     */
    protected function syncOptions(PsychometricQuestion $question, ?array $options): void
    {
        if ($options === null) {
            return;
        }

        foreach ($options as $option) {
            $action = $option['_action'] ?? (isset($option['id']) ? 'update' : 'create');

            if ($action === 'delete' && isset($option['id'])) {
                PsychometricQuestionOption::where('question_id', $question->id)
                    ->where('id', $option['id'])
                    ->delete();
                continue;
            }

            if ($action === 'update' && isset($option['id'])) {
                $model = PsychometricQuestionOption::where('question_id', $question->id)
                    ->where('id', $option['id'])
                    ->first();

                if ($model) {
                    $model->update([
                        'label' => $option['label'] ?? $model->label,
                        'value' => $option['value'] ?? $model->value,
                        'score' => $option['score'] ?? $model->score,
                        'weight' => $option['weight'] ?? $model->weight,
                        'position' => $option['position'] ?? $model->position,
                        'metadata' => $option['metadata'] ?? $model->metadata,
                    ]);
                }

                continue;
            }

            PsychometricQuestionOption::create([
                'question_id' => $question->id,
                'label' => $option['label'],
                'value' => $option['value'] ?? null,
                'score' => $option['score'] ?? 0,
                'weight' => $option['weight'] ?? 1,
                'position' => $option['position'] ?? null,
                'metadata' => $option['metadata'] ?? null,
            ]);
        }
    }
}
