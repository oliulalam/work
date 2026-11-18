<?php
// LG-Pay Configuration
define('LG_APP_ID', 'MJL3321');
define('LG_KEY', 'rXdc7Fi6RvTPfftoBe3cGWusoGedPUa9');
define('LG_GATEWAY_URL', 'https://www.lg-pay.com/api/order/create');

// Test mode setting: Set to true if using trade_type = 'test', set to false for production bKash/Nagad channels
define('LG_TEST_MODE', false);

// Determine protocol and host dynamically
$lg_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
$lg_host = $_SERVER['HTTP_HOST'] ?? 'kaki66.com';
$lg_base_url = $lg_protocol . $lg_host;

define('LG_NOTIFY_URL', $lg_base_url . '/api/lg_callback.php');
define('LG_RETURN_URL', $lg_base_url . '/player/dashboard.php');

// Signature generation function (MD5 signature as per LG-Pay documentation)
if (!function_exists('generate_lg_sign')) {
    function generate_lg_sign($data, $key) {
        ksort($data);
        $string = http_build_query($data);
        $string = urldecode($string);
        $string = trim($string) . "&key=" . $key;
        return strtoupper(md5($string));
    }
}
?>