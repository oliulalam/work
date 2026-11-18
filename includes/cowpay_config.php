<?php
// Cowpay Gateway Configuration
define('COWPAY_MERCHANT_CODE', 'M2769530848203370439');
define('COWPAY_SECRET_KEY', '4af5bab09401122a7ac59e5b2a78b84b');
define('COWPAY_GATEWAY_URL', 'https://api.cowpay.co/pay');
define('COWPAY_QUERY_URL', 'https://api.cowpay.co/queryPayOrder');
define('COWPAY_COUNTRY_CODE', 'BD');

// Determine protocol and host dynamically
$cowpay_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                  (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false) 
                  ? 'https://' : 'http://';
$cowpay_host = $_SERVER['HTTP_HOST'] ?? 'kaki66.com';
$cowpay_base_url = $cowpay_protocol . $cowpay_host;

define('COWPAY_NOTIFY_URL', $cowpay_base_url . '/api/cowpay_callback.php');
define('COWPAY_RETURN_URL', $cowpay_base_url . '/player/dashboard.php');

/**
 * Cowpay MD5 Signature Generation
 * Sort non-empty fields in transdata array by ASCII key name,
 * join as key1=val1&key2=val2...&key=SECRET_KEY, and return UPPERCASE MD5.
 */
if (!function_exists('generate_cowpay_sign')) {
    function generate_cowpay_sign($transdata, $secret_key) {
        $filtered = array();
        foreach ($transdata as $k => $v) {
            if ($k !== 'sign' && $v !== null && $v !== '') {
                $filtered[$k] = (string)$v;
            }
        }
        ksort($filtered, SORT_STRING);
        
        $pairs = array();
        foreach ($filtered as $k => $v) {
            $pairs[] = $k . '=' . $v;
        }
        
        $string_to_sign = implode('&', $pairs) . '&key=' . trim((string)$secret_key);
        return strtoupper(md5($string_to_sign));
    }
}
?>
