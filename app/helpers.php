<?php
/**
 * Created by PhpStorm.
 * User: leoliang
 * Date: 2020/4/6
 * Time: 12:33
 * @param $data
 * @param int $code
 * @param string $message
 * @return \Illuminate\Http\JsonResponse
 */

function success($data=[], $code=0, $message='') {
    $result = [
        'code' => $code,
        'message' => $message,
        'data' => $data
    ];

    return response()->json($result);
}

function error($code=-1, $message='error') {
    $result = [
        'code' => $code,
        'message' => $message,
        'data' => []
    ];

    return response()->json($result);
}
