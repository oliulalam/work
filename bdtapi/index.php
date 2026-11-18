<?php
/**
 * GameCloud — Launch helper + Webhook entry (official kit style)
 * Currency: BDT | Mode: seamless
 */
if (!defined('GAME_API_SKIP_MAINTENANCE')) {
    define('GAME_API_SKIP_MAINTENANCE', true);
}
header('Content-Type: application/json; charset=utf-8');

define('GAMECLOUD_RESELLER_ID', 292);
define('GAMECLOUD_API_TOKEN', 'c5c4df22b0a94132927b9beb8b3442ec');
define('GAMECLOUD_SECRET_KEY', '8b4b1bde096adfe282a627aeddfc7c5e4669dda7843825f5d7ffbeabdf03632f');
define('GAMECLOUD_PREFIX', 'RS_KAKI66');
define('GAMECLOUD_CURRENCY', 'BDT');
define('GAMECLOUD_HOME_URL', 'https://kaki66.com/player/dashboard.php');

function launchGame($userId, $gameCode) {
    $gatewayUrl = 'https://api.gamecloudapi.com/api/v1/game/launch';

    $playerId = (string)$userId;
    if (strpos($playerId, GAMECLOUD_PREFIX) === false) {
        $playerId = GAMECLOUD_PREFIX . '_' . $playerId;
    }

    $payload = array(
        'reseller_id'   => GAMECLOUD_RESELLER_ID,
        'player_id'     => $playerId,
        'game_uid'      => $gameCode,
        'mode'          => 'seamless',
        'currency_code' => GAMECLOUD_CURRENCY, // BDT
        'home_url'      => GAMECLOUD_HOME_URL
    );

    $ch = curl_init($gatewayUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-Token: ' . GAMECLOUD_API_TOKEN,
        'X-Secret-Key: ' . GAMECLOUD_SECRET_KEY
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return array('status' => 'FAILED', 'error' => $error);
    }
    return json_decode($response, true);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Launch via GET: /bdtapi/index.php?userId=123&gameCode=a04d1f3eb8ccec8a4823bdf18e3f0e84
if ((!is_array($data) || empty($data)) && isset($_GET['userId']) && isset($_GET['gameCode'])) {
    echo json_encode(launchGame($_GET['userId'], $_GET['gameCode']));
    exit;
}

// Webhook JSON → same callback handler as registered URL
if (is_array($data) && !empty($data) && (isset($data['action']) || isset($data['player_id']))) {
    // php://input already consumed — pass body to callback
    $GLOBALS['__gamecloud_raw_override'] = $input;
    // Re-inject for callback if it only reads php://input
    require __DIR__ . '/callback.php';
    exit;
}

http_response_code(400);
echo json_encode(array('status' => 'FAILED', 'error' => 'INVALID_JSON'));
?>
