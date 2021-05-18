<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function successReponse($message)
    {
        return response()->json([
            'success' => [
                'message' => $message,
            ]
        ]);
    }

    public function successReponseWithData($message, $data)
    {
        return response()->json([
            'success' => [
                'message' => $message,
                'data' => $data,
            ]
        ]);
    }

    public function errorReponse($message)
    {
        return response()->json([
            'error' => [
                'message' => $message,
            ]
        ]);
    }

    public function errorReponseWithErrors($message, $errors)
    {
        return response()->json([
            'error' => [
                'message' => $message,
                'errors' => $errors,
            ]
        ]);
    }
}
