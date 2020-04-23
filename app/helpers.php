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

function getDistance($srcLatitude, $srcLongitude, $desLatitude, $desLongitude)
{
    $radLat1 = deg2rad($srcLatitude);//deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($desLatitude);
    $radLng1 = deg2rad($srcLongitude);
    $radLng2 = deg2rad($desLongitude);
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt((sin($a / 2) ** 2) + cos($radLat1) * cos($radLat2) * (sin($b / 2) ** 2))) * 6378.137;
    $s *= 1000;

    return round($s, 2);//返回距离米
}

function packageData($page, $pageSize, $totalCount, $list)
{
    return [
        'page' => [
            'page' => (int)$page,
            'page_size' => (int)$pageSize,
            'page_count' => ceil($totalCount / $pageSize),
            'total_count' => $totalCount
        ],
        'list' => array_values($list)
    ];
}
