<?php

namespace App\Http\Controllers\Media;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\AddMediaRequest;
use App\Http\Requests\Media\DeleteMediaRequest;
use App\Http\Requests\Media\GetAllMedia;
use App\Models\Media\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

ini_set('upload_max_filesize', '64M');
ini_set('post_max_size', '64M');

class MediaController extends Controller
{
    public function add(AddMediaRequest $request): JsonResponse
    {
        try {
            ini_set('upload_max_filesize', '64M');
            ini_set('post_max_size', '64M');

            $media = $request->file('media');
            $extension = strtolower($media->getClientOriginalExtension());
            $folderPath = '';

            // Upload real/original file
            $mediaUniqueName = Helper::randomNumber().'_real_size.'.$extension;
            $media->storeAs($folderPath, $mediaUniqueName, 's3');

            $mediaUniqueSmallName = null;
            $mediaUniqueMediumName = null;

            // If not a video, generate small and medium versions
            if ($extension !== 'mp4') {
                $mediaUniqueSmallName = Helper::randomNumber().'_small_size.'.$extension;
                $media->storeAs($folderPath, $mediaUniqueSmallName, 's3');

                $mediaUniqueMediumName = Helper::randomNumber().'_medium_size.'.$extension;
                $media->storeAs($folderPath, $mediaUniqueMediumName, 's3');
            }

            $newMedia = new Media;
            $newMedia->unique_name = $mediaUniqueName;
            $newMedia->thumb_size = $mediaUniqueSmallName;
            $newMedia->medium_size = $mediaUniqueMediumName;
            $newMedia->folder_path = $folderPath;
            $newMedia->base_url = rtrim(Storage::disk('s3')->url($mediaUniqueName), $mediaUniqueName);
            $newMedia->extension = $extension;
            $newMedia->size = Helper::getFileSize($media->getSize());
            $newMedia->alt_tag = $media->getClientOriginalName();
            $newMedia->original_name = $media->getClientOriginalName();
            $newMedia->title = $request->input('title');
            $newMedia->batch_no = $request->input('batch_no');
            $newMedia->description = $request->input('description');
            $newMedia->created_by = Auth::id();
            $newMedia->save();

            return $this->sendSuccess([$newMedia], config('messages.success'));

        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }

    public function single($id): JsonResponse
    {

        $object = Media::find($id);

        return successResponse(config('messages.success'), $object, 200);

    }

    public function delete(DeleteMediaRequest $request): JsonResponse
    {

        $object = Media::whereIn('id', $request->input('ids'))->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);

        return successResponse(config('messages.success'), $object, 200);
    }


    public function listAllWithFilters(GetAllMedia $request): JsonResponse {

        $data = Media::filterData($request);
        $data = getData($data, $request->input('pagination'), $request->input('per_page'), $request->input('page'));
        return successResponse(config('messages.success'), $data, 200);
    }

}
