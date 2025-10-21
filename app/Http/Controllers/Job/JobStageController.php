<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Job\JobStage;
use App\Http\Requests\Job\AddJobStage;
use App\Http\Requests\Job\EditJobStage;
use App\Http\Requests\Job\DeleteJobStage;
use App\Http\Requests\JobStage\ChangeJobStageOrderRequest;
use Illuminate\Support\Facades\Auth;

class JobStageController extends Controller
{
    /**
     * Add a new job stage.
     */
    public function add(AddJobStage $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $stage = new JobStage();
            $stage->fill($data);
            $stage->created_by = auth()->id();
            $stage->updated_by = auth()->id();
            $stage->save();

            $object = JobStage::with(['type', 'subType'])->where('id', $stage->id)->first();
            return $this->sendSuccess($object, 'Job stage added successfully.');
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    /**
     * Edit an existing job stage.
     */
    public function edit(EditJobStage $request): JsonResponse
    {
        $data = $request->validated();

        $stage = JobStage::findOrFail($data['id']);
        $stage->fill($data);
        $stage->updated_by = auth()->id();
        $stage->save();

        $object = JobStage::with(['type', 'subType'])->where('id', $stage->id)->first();
        return $this->sendSuccess($object, 'Job stage updated successfully.');
    }

    /**
     * Get a single job stage by ID.
     */
    public function single($id): JsonResponse
    {
        $object = JobStage::with(['type', 'subType'])
            ->findOrFail($id);
    
        return successResponse(config('messages.success'), $object, 200);
    }
    

    /**
     * List stages for a job (ordered by `order` ASC).
     * Optional filters: type_id, sub_type_id.
     */
    public function listByJob(Request $request): JsonResponse
    {
        $jobId     = $request->input('job_id');
        $typeId    = $request->input('type_id');
        $subTypeId = $request->input('sub_type_id');
    
        $stages = JobStage::with(['type', 'subType']) // add 'job' if you want: ->with(['type','subType','job'])
            ->when($jobId,     fn ($q) => $q->where('job_id', $jobId))
            ->when($typeId,    fn ($q) => $q->where('type_id', $typeId))
            ->when($subTypeId, fn ($q) => $q->where('sub_type_id', $subTypeId))
            ->orderBy('order', 'asc')
            ->get();
    
        return successResponse(config('messages.success'), $stages, 200);
    }
    

    /**
     * Delete one or more job stages (soft delete) and mark deleted_by.
     */
    public function delete(DeleteJobStage $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            JobStage::whereIn('id', $ids)->update(['deleted_by' => auth()->id()]);
            JobStage::whereIn('id', $ids)->delete();
        }

        return $this->sendSuccess([], 'Job stage(s) deleted successfully.');
    }

    /**
     * (Optional) Bulk reorder stages.
     * Expects payload like: [{ "id": 10, "order": 1 }, { "id": 12, "order": 2 }]
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $items = $request->input('items', []);
            foreach ($items as $item) {
                if (!empty($item['id'])) {
                    JobStage::where('id', $item['id'])->update([
                        'order'      => (int)($item['order'] ?? 0),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            return $this->sendSuccess([], 'Job stages reordered successfully.');
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }


    public function changeJobStageOrder(ChangeJobStageOrderRequest $request): JsonResponse {

        $data        = $request->validated();
        $jobId       = (int) $data['job_id'];
        $items       = $data['order']; // [ {"id": X, "order": Y}, ... ]

        $updated = 0;

        foreach ($items as $row) {
            $updated += JobStage::where('id', (int) $row['id'])
                ->where('job_id', $jobId)
                ->whereNull('deleted_at')
                ->update([
                    'order'      => (int) $row['order'],
                    'updated_by' => Auth::id(),
                ]);
        }

        return $this->sendSuccess('Job stage order updated successfully.', [
            'job_id'        => $jobId,
            'updated_rows'  => $updated,
        ]);
    }

}
