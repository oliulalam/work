<?php
require __DIR__ . '/../includes/kit_helper.php';

$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 1;
$provider = isset($_GET['provider']) ? strtolower($_GET['provider']) : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$allKitGames = kit_load_all_games();

if ($provider !== 'all') {
    $games = kit_get_games_by_provider($allKitGames, $provider);
} else {
    $games = $allKitGames;
}

if (!empty($search)) {
    $games = kit_search_games($games, $search);
}

$total = count($games);
$pageGames = array_slice($games, $offset, $limit);

if (!empty($pageGames)) {
    foreach ($pageGames as $g) {
        echo '
        <a href="launch.php?game_id='.$g['game_uid'].'" class="game-card">
            <div class="aspect-square bg-[#071f18] p-1">
                <img src="'.$g['image'].'" class="w-full h-full object-cover rounded-lg shadow-inner" onerror="this.src=\'https://placehold.co/150x150/0a2d1f/1de9b6?text=GAME\'">
            </div>
            <div class="p-1.5">
                <p class="text-[7px] text-emerald-400 font-black truncate uppercase">'.$g['provider'].'</p>
                <p class="text-[9px] text-gray-200 font-bold truncate">'.$g['name'].'</p>
            </div>
        </a>';
    }
}
