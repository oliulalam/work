<?php
$url = $_GET['url'] ?? '';
if (empty($url)) exit;
$safeUrl = addslashes($url);
?>
<script>
    var u = "<?php echo $safeUrl; ?>";
    try { window.top !== window.self ? window.top.location.replace(u) : window.location.replace(u); } catch(e) { window.location.replace(u); }
</script>
