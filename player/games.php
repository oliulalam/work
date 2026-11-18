<?php
session_start();
require __DIR__ . '/../includes/kit_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$current_provider = isset($_GET['provider']) ? $_GET['provider'] : '';

$allKitGames = kit_load_all_games();

$kitProviders = kit_get_providers($allKitGames);

if (!empty($current_provider)) {
    $games = kit_get_games_by_provider($allKitGames, $current_provider);
} else {
    $games = $allKitGames;
}

$total_games = count($games);
$total_pages = ceil($total_games / $limit);
$games_page = array_slice($games, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Game Lobby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0a1e29; color: #fff; font-family: 'Roboto', sans-serif; }
        .header-premium { background: linear-gradient(90deg, #102b3f 0%, #1a5c92 100%); border-bottom: 2px solid #20b1ff; }
        .marquee-wrapper { background: #07151e; padding: 12px 0; overflow: hidden; border-bottom: 1px solid #1a3a4d; cursor: grab; }
        .marquee-content { display: flex; gap: 15px; width: max-content; animation: scroll-ultra-slow 120s linear infinite; }
        .marquee-wrapper:active .marquee-content { animation-play-state: paused; cursor: grabbing; }
        @keyframes scroll-ultra-slow { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .page-link { background: #102b3f; border: 1px solid #1a3a4d; padding: 8px 15px; border-radius: 8px; font-size: 12px; }
        .page-link.active { background: #1a5c92; border-color: #ffdf1b; color: #ffdf1b; font-weight: bold; }
        #providerModal { backdrop-filter: blur(8px); }
        .modal-header { position: sticky; top: 0; background: #102b3f; z-index: 50; border-bottom: 1px solid #1a3a4d; }
    </style>
</head>
<body>

    <header class="header-premium flex justify-between items-center px-4 h-[58px] sticky top-0 z-50">
        <a href="index.php" class="text-white"><i class="fas fa-arrow-left text-lg"></i></a>
        <h1 class="font-bold uppercase text-[12px]">Premium Lobby</h1>
        <button onclick="document.getElementById('providerModal').classList.remove('hidden')" class="text-[#ffdf1b] text-[10px] font-bold border border-[#ffdf1b] px-3 py-1 rounded-full uppercase">
            Providers <i class="fas fa-th-large ml-1"></i>
        </button>
    </header>

    <div class="marquee-wrapper">
        <div class="marquee-content">
            <?php for($i=0; $i<3; $i++): foreach($kitProviders as $b): ?>
                <a href="?provider=<?php echo urlencode($b['name']); ?>" class="bg-[#102b3f] border border-[#1a3a4d] px-4 py-2 rounded-lg text-[10px] font-bold uppercase whitespace-nowrap">
                    <?php echo $b['name']; ?>
                </a>
            <?php endforeach; endfor; ?>
        </div>
    </div>

    <div class="p-3">
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
            <?php foreach($games_page as $game): ?>
            <a href="launch.php?game_id=<?php echo $game['game_uid']; ?>" class="block bg-[#102b3f] rounded-xl border border-[#1a3a4d] overflow-hidden active:scale-95 transition">
                <img src="<?php echo $game['image']; ?>" loading="lazy" class="aspect-square w-full object-cover">
                <div class="p-1.5 border-t border-[#1a3a4d]">
                    <p class="text-[9px] font-bold truncate uppercase text-white"><?php echo $game['name']; ?></p>
                    <p class="text-[7px] text-blue-400 font-medium truncate uppercase"><?php echo $game['provider']; ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="p-4 flex justify-center items-center gap-2 overflow-x-auto no-scrollbar pb-10">
        <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&provider=<?php echo urlencode($current_provider); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>

        <?php 
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($i=$start; $i<=$end; $i++): ?>
            <a href="?page=<?php echo $i; ?>&provider=<?php echo urlencode($current_provider); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&provider=<?php echo urlencode($current_provider); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="providerModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-[#102b3f] w-full max-w-sm rounded-3xl overflow-hidden border border-[#1a3a4d] flex flex-col max-h-[80vh]">
            <div class="modal-header p-4 flex justify-between items-center">
                <span class="text-[#ffdf1b] font-black uppercase text-xs">All Providers</span>
                <button onclick="document.getElementById('providerModal').classList.add('hidden')" class="bg-[#1a5c92] text-white w-8 h-8 rounded-full flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="overflow-y-auto p-4 grid grid-cols-2 gap-3">
                <a href="?" class="bg-[#1a5c92] p-3 rounded-xl text-center text-[11px] font-bold uppercase">All Games</a>
                <?php foreach($kitProviders as $b): ?>
                    <a href="?provider=<?php echo urlencode($b['name']); ?>" class="bg-[#07151e] border border-[#1a3a4d] p-3 rounded-xl text-center text-[10px] font-bold uppercase hover:border-[#ffdf1b]">
                        <?php echo $b['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</body>
</html>
