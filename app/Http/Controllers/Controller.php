<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller {

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function sendSuccess($result, $message){
        $response = [ 'status' => true, 'message' => $message, 'data'=> $result ];
        return response()->json($response, 200);
    }

    public function sendSuccessWithCounters($result, $message, $counter){
        $response = [ 'status' => true, 'message' => $message, 'data'=> $result, 'counters'=> $counter ];
        return response()->json($response, 200);
    }

    public function sendError($error, $errorMessages = []){
        $response = [ 'status' => false, 'message' => $error, 'error'=> $errorMessages, 'data' => null ];
        return response()->json($response, 500);
    }

    public function sendValidation($error, $errorMessages = []){
        $response = [ 'status' => false, 'message' => $errorMessages, 'error'=> $error, 'data' => null ];
        return response()->json($response, 200);
    }

    public static function getData($object = null, $pagination = null, $perPage = null, $page = null) {
        if ($pagination){
            return $object->paginate($perPage);
        }else{
            return $object->get();
        }

    }
}
