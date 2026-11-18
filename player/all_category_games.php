<?php
session_start();
require __DIR__ . '/../includes/kit_helper.php';

$selected_provider = isset($_GET['provider']) ? $_GET['provider'] : 'all';
$cat_name = 'All Games';

$allKitGames = kit_load_all_games();
$kitProviders = kit_get_providers($allKitGames);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo $cat_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <style>
        :root { --bg-dark: #071f18; --header-green: #0e3d2c; --accent-green: #1de9b6; --card-bg: #0a2d1f; }
        body { background-color: var(--bg-dark); color: white; font-family: 'Roboto', sans-serif; padding-bottom: 100px; overflow-x: hidden; }
        .header-fixed { background: var(--header-green); border-bottom: 1px solid #1a5c40; position: sticky; top: 0; z-index: 1000; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 15px; }
        .provider-chip { background: #1c5244; border: 1.5px solid #236353; transition: 0.3s; width: 75px; height: 45px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; overflow: hidden; }
        .provider-chip.active { border-color: var(--accent-green); background: #2a6b5a; box-shadow: 0 0 10px rgba(29, 233, 182, 0.3); }
        .game-card { background: var(--card-bg); border: 1px solid #1a5c40; border-radius: 12px; overflow: hidden; }
        .search-input { background: #0a2d1f; border: 1px solid #1a5c40; border-radius: 50px; padding: 10px 45px; width: 100%; color: white; outline: none; }
        #allProvModal { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0,0,0,0.95); padding: 20px; overflow-y: auto; }
    </style>
</head>
<body>

    <header class="header-fixed">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="text-white text-xl p-1"><i class="fas fa-chevron-left"></i></a>
            <h1 class="font-bold uppercase text-sm italic"><?php echo ($selected_provider == 'all') ? htmlspecialchars($cat_name) : 'Provider: ' . $selected_provider; ?></h1>
        </div>
    </header>

    <div class="p-4 bg-[#0a2d1f]/50">
        <div class="relative">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500"></i>
            <input type="text" id="gameSearch" class="search-input text-sm" placeholder="Search Game Name...">
        </div>
    </div>

    <div class="p-3 flex items-center gap-2 bg-[#0e3027] sticky top-[60px] z-40">
        <button onclick="toggleProvModal()" class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-black flex-shrink-0">
            <i class="fas fa-th"></i>
        </button>
        <div class="swiper provSlider overflow-hidden">
            <div class="swiper-wrapper">
                <div class="swiper-slide !w-auto">
                    <a href="?provider=all" class="provider-chip <?php echo ($selected_provider == 'all') ? 'active' : ''; ?>">
                        <span class="text-[10px] font-black uppercase">ALL</span>
                    </a>
                </div>
                <?php foreach($kitProviders as $p): ?>
                <div class="swiper-slide !w-auto">
                    <a href="?provider=<?php echo urlencode($p['name']); ?>" 
                       class="provider-chip <?php echo ($selected_provider == $p['name']) ? 'active' : ''; ?>">
                        <span class="text-[8px] font-black uppercase text-emerald-300"><?php echo $p['name']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="p-3 grid grid-cols-3 gap-3" id="gameDisplay"></div>

    <div class="p-6 text-center" id="loadMoreSection" style="display: none;">
        <button id="loadMoreBtn" class="bg-emerald-500 text-black px-8 py-2 rounded-full text-xs font-black uppercase italic shadow-lg active:scale-90 transition">
            Load More
        </button>
    </div>

    <div id="allProvModal">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-emerald-400 font-black italic uppercase">Select Provider</h2>
            <button onclick="toggleProvModal()" class="text-3xl">&times;</button>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <?php foreach($kitProviders as $p): ?>
            <a href="?provider=<?php echo urlencode($p['name']); ?>" class="bg-[#1c5244] p-3 rounded-lg text-center border border-white/5">
                <p class="text-[8px] font-black truncate uppercase text-gray-300"><?php echo $p['name']; ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'bottom_nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let offset = 0;
        const limit = 50;
        const provider = '<?php echo $selected_provider; ?>';

        new Swiper(".provSlider", { slidesPerView: "auto", spaceBetween: 8, freeMode: true });

        function toggleProvModal() { $('#allProvModal').fadeToggle(200); }

        function loadGames(reset = false) {
            if(reset) { offset = 0; $('#gameDisplay').html('<div class="col-span-full text-center py-20 text-emerald-500"><i class="fas fa-circle-notch fa-spin text-3xl"></i></div>'); }
            const search = $('#gameSearch').val();

            $.ajax({
                url: 'ajax_fetch_games.php',
                type: 'GET',
                data: { provider, search, offset, limit },
                success: function(html) {
                    if(reset) $('#gameDisplay').empty();
                    if(html.trim() === "") {
                        if(reset) $('#gameDisplay').html('<p class="col-span-full text-center py-10 text-gray-500 font-bold uppercase">No more games</p>');
                        $('#loadMoreSection').hide();
                    } else {
                        $('#gameDisplay').append(html);
                        $('#loadMoreSection').show();
                    }
                }
            });
        }

        $('#gameSearch').on('input', function() { loadGames(true); });
        $('#loadMoreBtn').click(function() { offset += limit; loadGames(); });
        $(document).ready(function() { loadGames(true); });
    </script>
</body>
</html>
