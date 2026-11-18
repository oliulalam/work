<?php
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('vary: Origin');

date_default_timezone_set("Asia/Kolkata");
$shnunc = date("Y-m-d H:i:s");

$input = file_get_contents('php://input');
$payload = json_decode($input, true);

$response = [
    "code" => 1,
    "msg" => "Invalid Request",
    "data" => []
];

if (isset($payload['type'])) {
    $type = intval($payload['type']);

    $fileMap = [
        2 => './data/cq9.json',
        6 => './data/jdb.json',
        18 => './data/jili.json',
        5 => './data/pgsoft.json',
        9 => './data/km.json',
        57 => './data/eg.json',
        101 => './data/idg.json',
        21 => './data/v8.json',
        4 => './data/mg.json',
        37 => './data/mg_fish.json',
        17 => './data/evolive.json',
        41 => './data/g9.json',
        14 => './data/saba.json',
        19 => './data/card365.json',
        7 => './data/PRAGMATIC.json',
        16 => './data/PRAGMATIC.json',
        38 => './data/PRAGMATIC.json',
    ];

    if (array_key_exists($type, $fileMap)) {
        $filePath = __DIR__ . '/' . $fileMap[$type];

        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            $response = [
                "code" => 0,
                "msg" => "Succeed",
                "msgCode" => 0,
                "serviceNowTime" => $shnunc,
                "data" => json_decode($jsonData, true)
            ];
        } else {
            $response["msg"] = "File not found for type $type";
        }
    } else {
        $response["msg"] = "Invalid type provided";
    }
} else {
    $response["msg"] = "Type not specified in request";
}

$response["code"] = 0;
$response["msg"] = "Succeed";
$response["msgCode"] = 0;
$response["serviceNowTime"] = $shnunc;
echo json_encode($response, JSON_UNESCAPED_SLASHES);
