<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$dataDir = __DIR__ . '/data';

$vendorMap = [
    'jili'    => ['type' => 18, 'name' => 'JILI',     'category' => 'Slots'],
    'pgsoft'  => ['type' => 5,  'name' => 'PG',       'category' => 'Slots'],
    'cq9'     => ['type' => 2,  'name' => 'CQ9',      'category' => 'Slots'],
    'jdb'     => ['type' => 6,  'name' => 'JDB',      'category' => 'Slots'],
    'PRAGMATIC'=> ['type' => 7, 'name' => 'PRAGMATIC','category' => 'Slots'],
    'km'      => ['type' => 9,  'name' => 'KM',       'category' => 'Slots'],
    'mg'      => ['type' => 4,  'name' => 'MG',       'category' => 'Slots'],
    'mg_fish' => ['type' => 37, 'name' => 'MG_Fish',  'category' => 'Slots'],
    'evolive' => ['type' => 17, 'name' => 'EVO_Live', 'category' => 'Live'],
    'evo'     => ['type' => 16, 'name' => 'EVO',      'category' => 'Slots'],
    'eg'      => ['type' => 57, 'name' => 'EG',       'category' => 'Slots'],
    'idg'     => ['type' => 101,'name' => 'IDG',      'category' => 'Slots'],
    'v8'      => ['type' => 21, 'name' => 'V8',       'category' => 'Slots'],
    'g9'      => ['type' => 41, 'name' => 'G9',       'category' => 'Slots'],
    'card365' => ['type' => 19, 'name' => 'Card365',  'category' => 'Cards'],
    'pg'      => ['type' => 5,  'name' => 'PG',       'category' => 'Slots'],
    'pgs'     => ['type' => 7,  'name' => 'PRAGMATIC','category' => 'Slots'],
    'db'      => ['type' => 0,  'name' => 'DB',       'category' => 'Slots'],
];

$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
$provider = isset($_GET['provider']) ? strtolower($_GET['provider']) : '';
$search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

$allGames = [];

foreach ($vendorMap as $fileKey => $info) {
    $filePath = "$dataDir/$fileKey.json";
    if (!file_exists($filePath)) continue;

    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    if (!$data || !isset($data['gameLists'])) continue;

    foreach ($data['gameLists'] as $game) {
        if (!isset($game['gameID']) || !isset($game['gameNameEn'])) continue;
        $allGames[] = [
            'gameID'       => $game['gameID'],
            'name'         => $game['gameNameEn'],
            'image'        => $game['img'] ?? '',
            'vendorCode'   => $game['vendorCode'] ?? $info['name'],
            'providerName' => $info['name'],
            'category'     => $info['category'],
            'type'         => $info['type'],
            'customGameType'=> $game['customGameType'] ?? 0,
        ];
    }
}

// Filter by provider
if ($provider) {
    $allGames = array_filter($allGames, function($g) use ($provider) {
        return strtolower($g['providerName']) === $provider || strtolower($g['vendorCode']) === $provider;
    });
}

// Filter by type
if ($type > 0) {
    $allGames = array_filter($allGames, function($g) use ($type) {
        return $g['type'] === $type;
    });
}

// Search
if ($search) {
    $allGames = array_filter($allGames, function($g) use ($search) {
        return strpos(strtolower($g['name']), $search) !== false;
    });
}

$allGames = array_values($allGames);
$total = count($allGames);
$sliced = array_slice($allGames, $offset, $limit);

echo json_encode([
    'code' => 0,
    'msg' => 'Success',
    'total' => $total,
    'offset' => $offset,
    'limit' => $limit,
    'data' => $sliced
], JSON_UNESCAPED_SLASHES);
