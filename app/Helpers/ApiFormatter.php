<?php

namespace App\Helpers;

class ApiFormatter
{
    public static function sendResponse($status = NULL, $success = false, $message = NULL, $data = [])
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
}

?>