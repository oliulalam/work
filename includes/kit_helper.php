<?php
/**
 * Kit Helper - Full Game Catalog Loader & GameCloud Routing Helper
 */

function kit_load_all_games() {
    $dataDir = __DIR__ . '/../api/kit/data';

    $vendorMap = [
        'exclusive.json' => ['provider' => 'Exclusive Games', 'category' => 'Slots'],
        'jili.json'      => ['provider' => 'JILI',            'category' => 'Slots'],
        'pg.json'        => ['provider' => 'PG',              'category' => 'Slots'],
        'pgsoft.json'    => ['provider' => 'PGSOFT',          'category' => 'Slots'],
        'pgs.json'       => ['provider' => 'PGSOFT',          'category' => 'Slots'],
        'evolive.json'   => ['provider' => 'EVO_Live',        'category' => 'Live'],
        'evo.json'       => ['provider' => 'EVO_Live',        'category' => 'Live'],
        'jdb.json'       => ['provider' => 'JDB',             'category' => 'Slots'],
        'idg.json'       => ['provider' => 'IDG',             'category' => 'Slots'],
        'g9.json'        => ['provider' => 'G9',              'category' => 'Slots'],
        'db.json'        => ['provider' => 'DB',              'category' => 'Slots'],
        'live.json'      => ['provider' => 'Live Casino',     'category' => 'Live'],
        'card365.json'   => ['provider' => 'CARD365',         'category' => 'Live'],
        'cq9.json'       => ['provider' => 'CQ9',             'category' => 'Slots'],
        'eg.json'        => ['provider' => 'EG',              'category' => 'Slots'],
        'km.json'        => ['provider' => 'KM',              'category' => 'Board'],
        'mg.json'        => ['provider' => 'Microgaming',     'category' => 'Slots'],
        'mg_fish.json'   => ['provider' => 'Microgaming',     'category' => 'Fish'],
        'PRAGMATIC.json' => ['provider' => 'Pragmatic',       'category' => 'Slots'],
        'v8.json'        => ['provider' => 'V8',              'category' => 'Board'],
    ];

    $allGames = [];
    $gameId = 1;

    // 1. Load games from JSON data files
    foreach ($vendorMap as $file => $info) {
        $filePath = "$dataDir/$file";
        if (!file_exists($filePath)) continue;

        $json = @file_get_contents($filePath);
        $data = @json_decode($json, true);
        if (!$data || !isset($data['gameLists'])) continue;

        foreach ($data['gameLists'] as $game) {
            $gid = $game['gameID'] ?? $game['gameCode'] ?? '';
            if (empty($gid) || !isset($game['gameNameEn'])) continue;

            $img = $game['img'] ?? $game['image'] ?? $game['icon'] ?? '';
            $img = trim($img);

            if (empty($img)) continue;
            if (!preg_match('#^https?://#i', $img)) continue;

            $host = @parse_url($img, PHP_URL_HOST);
            $deadHosts = ['bhaukaalgame.fun', 'sqwzecuuatenlhlzqver.supabase.co', 'imagensfivers.com'];
            if (in_array($host, $deadHosts, true)) continue;

            $allGames[] = [
                'id'           => $gameId++,
                'game_uid'     => $gid,
                'name'         => $game['gameNameEn'],
                'image'        => $img,
                'provider'     => $info['provider'],
                'provider_id'  => $info['provider'],
                'vendorCode'   => $game['vendorCode'] ?? $info['provider'],
                'category'     => $info['category'],
            ];
        }
    }

    // 2. Load games from GetAllGameList.php catalog
    $catalogPath = __DIR__ . '/../bdtapi/GetAllGameList.php';
    if (file_exists($catalogPath)) {
        $content = @file_get_contents($catalogPath);
        if ($content && preg_match('/\$jsonData\s*=\s*\'(\{.*\})\';/s', $content, $m)) {
            $d = @json_decode($m[1], true);
            if (is_array($d) && isset($d['data']) && is_array($d['data'])) {
                foreach ($d['data'] as $catKey => $catItems) {
                    if (!is_array($catItems)) continue;

                    $items = [];
                    if (isset($catItems['platformList']) || isset($catItems['clicksTopList']) || isset($catItems['clicksVideoTopList'])) {
                        if (isset($catItems['platformList'])) $items = array_merge($items, $catItems['platformList']);
                        if (isset($catItems['clicksTopList'])) $items = array_merge($items, $catItems['clicksTopList']);
                        if (isset($catItems['clicksVideoTopList'])) $items = array_merge($items, $catItems['clicksVideoTopList']);
                    } else {
                        $items = $catItems;
                    }

                    foreach ($items as $game) {
                        if (!is_array($game)) continue;
                        $gid = $game['gameCode'] ?? $game['gameID'] ?? '';
                        $name = $game['gameNameEn'] ?? $game['slotsName'] ?? '';
                        $img = $game['imgUrl'] ?? $game['img'] ?? $game['vendorImg'] ?? $game['categoryImg'] ?? '';

                        if (empty($gid) || empty($name) || empty($img)) continue;

                        $vendor = strtoupper($game['vendorCode'] ?? $catKey);

                        $allGames[] = [
                            'id'           => $gameId++,
                            'game_uid'     => $gid,
                            'name'         => $name,
                            'image'        => $img,
                            'provider'     => $vendor,
                            'provider_id'  => $vendor,
                            'vendorCode'   => $vendor,
                            'category'     => ucfirst($catKey),
                        ];
                    }
                }
            }
        }
    }

    // Filter unique games by game_uid
    $seenUids = [];
    $uniqueGames = [];
    foreach ($allGames as $g) {
        $uidKey = strtolower($g['game_uid']);
        if (!isset($seenUids[$uidKey])) {
            $seenUids[$uidKey] = true;
            $uniqueGames[] = $g;
        }
    }

    return $uniqueGames;
}

function kit_get_providers($games) {
    $seen = [];
    $providers = [];
    foreach ($games as $g) {
        $p = $g['provider'];
        if (!isset($seen[$p])) {
            $seen[$p] = true;
            $providers[] = ['name' => $p, 'slug' => strtolower($p)];
        }
    }
    return $providers;
}

function kit_get_categories($games) {
    $seen = [];
    $cats = [];
    foreach ($games as $g) {
        $c = $g['category'];
        if (!isset($seen[$c])) {
            $seen[$c] = true;
            $cats[] = $c;
        }
    }
    return $cats;
}

function kit_get_games_by_provider($games, $provider) {
    return array_values(array_filter($games, function($g) use ($provider) {
        return strtolower($g['provider']) === strtolower($provider);
    }));
}

function kit_get_games_by_category($games, $category) {
    return array_values(array_filter($games, function($g) use ($category) {
        return strtolower($g['category']) === strtolower($category);
    }));
}

function kit_search_games($games, $query) {
    $q = strtolower($query);
    return array_values(array_filter($games, function($g) use ($q) {
        return strpos(strtolower($g['name']), $q) !== false
            || strpos(strtolower($g['provider']), $q) !== false;
    }));
}
