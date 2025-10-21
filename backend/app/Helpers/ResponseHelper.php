<?php

if (! function_exists('jsonResponse')) {
    /**
     * Generate a JSON response.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function jsonResponse(bool $status, string $message, $data, int $statusCode)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}

if (! function_exists('jsonResponseWithCounter')) {
    /**
     * Generate a JSON response.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function jsonResponseWithCounter(bool $status, string $message, $data, $counter, int $statusCode)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}

if (! function_exists('errorResponse')) {
    /**
     * Generate an error JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function errorResponse(string $message, \Exception $exception, int $statusCode)
    {
        return jsonResponse(false, $message, $exception->getMessage(), $statusCode);
    }
}

if (! function_exists('validationResponse')) {
    /**
     * Generate a validation error JSON response.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return \Illuminate\Http\JsonResponse
     */
    function validationResponse(string $message, $validator, int $statusCode)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $validator->errors(),
        ], $statusCode);
    }
}

if (! function_exists('validationResponseSingle')) {
    /**
     * Generate a validation error JSON response.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return \Illuminate\Http\JsonResponse
     */
    function validationResponseSingle(string $message, int $statusCode)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $statusCode);
    }
}

if (! function_exists('successResponse')) {
    /**
     * Generate a success JSON response.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function successResponse(string $message, $data, int $statusCode)
    {
        return jsonResponse(true, $message, $data, $statusCode);
    }
}

if (! function_exists('successResponseWithCounter')) {
    /**
     * Generate a success JSON response.
     *
     * @param  mixed  $data
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    function successResponseWithCounter(string $message, $data, $counter, $statusCode)
    {
        return jsonResponseWithCounter(true, $message, $data, $counter, $statusCode);
    }
}

if (! function_exists('invalidCredentials')) {
    /**
     * Generate an invalid credentials JSON response.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function invalidCredentials(string $message, $data, int $statusCode)
    {
        return jsonResponse(false, $message, $data, $statusCode);
    }
}

if (! function_exists('AlreadyVerified')) {
    /**
     * Generate an invalid credentials JSON response.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function AlreadyVerified(string $message, $data, int $statusCode)
    {
        return jsonResponse(false, $message, $data, $statusCode);
    }
}

if (! function_exists('InvalidInfo')) {
    /**
     * Generate an invalid credentials JSON response.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function InvalidInfo(string $message, $data, int $statusCode)
    {
        return jsonResponse(false, $message, $data, $statusCode);
    }
}

if (! function_exists('getData')) {
    /**
     * Generate an invalid credentials JSON response.
     *
     * @param  int  $pagination
     * @param  int  $perPage
     * @param  int  $page
     * @param  mixed  $object
     * @return \Illuminate\Http\JsonResponse
     */
    function getData($object = null, $pagination = null, $perPage = null, $page = null)
    {
        if ($pagination) {
            return $object->paginate($perPage);
        } else {
            return $object->get();
        }

    }
}
