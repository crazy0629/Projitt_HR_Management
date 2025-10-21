<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AddMasterRequest;
use App\Http\Requests\Master\DeleteMasterRequest;
use App\Http\Requests\Master\EditMasterRequest;
use App\Http\Requests\Master\ListWithFiltersMasterRequest;
use App\Models\Master\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterController extends Controller {

    public function add(AddMasterRequest $request): JsonResponse {

        $object = new Master();
        $object->name = $request->filled('name') ? $request->input('name') : null;
        $object->description = $request->filled('description') ? $request->input('description') : null;
        $object->type_id = $request->filled('type_id') ? $request->input('type_id') : null;
        $object->parent_id = $request->filled('parent_id') ? $request->input('parent_id') : null;
        $object->created_by = Auth::id();
        $object->save();

        $object = Master::find($object->id);
        return $this->sendSuccess(config('messages.success'), $object, 200);

    }

    public function edit(EditMasterRequest $request): JsonResponse {

        $object = Master::find($request->input('id'));
        $object->name = $request->filled('name') ? $request->input('name') : $object->name;
        $object->description = $request->filled('description') ? $request->input('description') : $object->description;
        $object->parent_id = $request->filled('parent_id') ? $request->input('parent_id') : null;
        $object->save();

        $object = Master::find($request->input('id'));
        return successResponse(config('messages.success'), $object, 200);

    }

    public function delete(DeleteMasterRequest $request): JsonResponse {

        $object = Master::whereIn('id', $request->input('ids'))->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);

        return successResponse(config('messages.success'), $object, 200);
    }

    public function single($id): JsonResponse {

        $object = Master::select('id', 'name', 'description', 'type_id')->where('id', $id)->first();
        return successResponse(config('messages.success'), $object, 200);
    }

    public function listAllWithFilters(ListWithFiltersMasterRequest $request): JsonResponse {

        $object = Master::filterData($request);
        $object = getData($object, $request->input('pagination'), $request->input('per_page'), $request->input('page'));
        return successResponse(config('messages.success'), $object, 200);
    }

    public function intellisenseSearch(Request $request): JsonResponse {
        return successResponse(config('messages.success'), Master::searchData($request), 200);
    }
}
