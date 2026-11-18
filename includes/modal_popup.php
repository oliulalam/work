<?php
// Global Popup Announcement Component
// Include this file on frontend pages. It reads Admin > Settings popup data dynamically.

if (!isset($conn)) {
    $db_paths = [__DIR__ . '/db.php', __DIR__ . '/../includes/db.php', 'includes/db.php', '../includes/db.php'];
    foreach ($db_paths as $path) {
        if (file_exists($path)) { require_once $path; break; }
    }
}

if (!function_exists('rj_popup_ensure_column')) {
function rj_popup_ensure_column($conn, $column, $definition) {
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if (!$safeColumn) return;
    $check = @$conn->query("SHOW COLUMNS FROM settings LIKE '$safeColumn'");
    if ($check && $check->num_rows == 0) {
        @$conn->query("ALTER TABLE settings ADD COLUMN `$safeColumn` $definition");
    }
}
}

if (!function_exists('rj_popup_asset_url')) {
function rj_popup_asset_url($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^(https?:)?//|^data:|^/~i', $path)) return $path;
    if (file_exists($path)) return $path;
    if (file_exists('../' . $path)) return '../' . $path;
    return '/' . ltrim($path, '/');
}
}

$popup = null;
if (isset($conn) && !$conn->connect_error) {
    rj_popup_ensure_column($conn, 'popup_enabled', "tinyint(1) DEFAULT 0");
    rj_popup_ensure_column($conn, 'popup_title', "varchar(255) DEFAULT NULL");
    rj_popup_ensure_column($conn, 'popup_desc', "text DEFAULT NULL");
    rj_popup_ensure_column($conn, 'popup_image', "varchar(255) DEFAULT NULL");
    rj_popup_ensure_column($conn, 'popup_button_text', "varchar(100) DEFAULT NULL");
    rj_popup_ensure_column($conn, 'popup_button_link', "varchar(255) DEFAULT NULL");

    $pop_query = @$conn->query("SELECT popup_enabled, popup_title, popup_desc, popup_image, popup_button_text, popup_button_link FROM settings WHERE id=1 LIMIT 1");
    if ($pop_query && $pop_query->num_rows > 0) {
        $popup = $pop_query->fetch_assoc();
    }
}

if ($popup && intval($popup['popup_enabled']) === 1):
    $title = trim((string)($popup['popup_title'] ?? '')) ?: 'Announcement';
    $desc = trim((string)($popup['popup_desc'] ?? ''));
    $img_path = rj_popup_asset_url($popup['popup_image'] ?? '');
    $button_text = trim((string)($popup['popup_button_text'] ?? ''));
    $button_link = trim((string)($popup['popup_button_link'] ?? ''));
?>

<div id="promoModal" class="rj-promo-modal" aria-modal="true" role="dialog">
    <div class="rj-promo-card">
        <button type="button" onclick="closePromoModal()" class="rj-promo-close" aria-label="Close">&times;</button>

        <div class="rj-promo-head">
            <div class="rj-promo-icon">📢</div>
            <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>

        <div class="rj-promo-body">
            <?php if($img_path): ?>
                <img src="<?php echo htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo time(); ?>" onerror="this.style.display='none'" alt="Announcement" class="rj-promo-img">
            <?php endif; ?>

            <?php if($desc): ?>
                <p><?php echo nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')); ?></p>
            <?php endif; ?>

            <?php if($button_text && $button_link): ?>
                <a href="<?php echo htmlspecialchars($button_link, ENT_QUOTES, 'UTF-8'); ?>" class="rj-promo-btn">
                    <?php echo htmlspecialchars($button_text, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rj-promo-modal{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.78);backdrop-filter:blur(4px);padding:18px;font-family:Arial, sans-serif;}
.rj-promo-modal.is-open{display:flex;}
.rj-promo-card{position:relative;width:min(92vw,420px);max-height:90vh;overflow:auto;background:linear-gradient(180deg,#10251e,#061611);border:1px solid rgba(255,215,0,.35);border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.55);color:#fff;animation:rjPopIn .22s ease-out;}
.rj-promo-close{position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:999px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:#fff;font-size:26px;line-height:28px;cursor:pointer;z-index:2;}
.rj-promo-head{padding:18px 52px 14px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);}
.rj-promo-icon{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,204,0,.18);}
.rj-promo-head h3{margin:0;font-size:18px;line-height:1.3;font-weight:800;color:#ffd95a;}
.rj-promo-body{padding:18px;text-align:center;}
.rj-promo-img{width:100%;max-height:340px;object-fit:contain;border-radius:14px;margin-bottom:14px;background:#0a1a14;border:1px solid rgba(255,255,255,.12);}
.rj-promo-body p{margin:0 0 14px;color:#f5f5f5;font-size:14px;line-height:1.6;font-weight:600;}
.rj-promo-btn{display:inline-flex;align-items:center;justify-content:center;min-width:150px;border-radius:999px;background:linear-gradient(135deg,#ffd95a,#ff9f1a);color:#111;text-decoration:none;font-weight:900;padding:11px 18px;box-shadow:0 8px 22px rgba(255,170,0,.25);}
@keyframes rjPopIn{from{transform:scale(.96);opacity:0}to{transform:scale(1);opacity:1}}
</style>

<script>
(function(){
    function openPromo(){
        var modal = document.getElementById('promoModal');
        if(modal) modal.classList.add('is-open');
    }
    window.closePromoModal = function(){
        var modal = document.getElementById('promoModal');
        if(modal) modal.classList.remove('is-open');
    };
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(openPromo, 500); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') window.closePromoModal(); });
})();
</script>

<?php endif; ?>
