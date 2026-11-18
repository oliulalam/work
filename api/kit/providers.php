<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$apiKey = 'cVHxP3DfOty4f7V6';
$url = "https://api.pkapiserver.fun/api/v1/providers?api_key=$apiKey";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['code' => $httpCode, 'msg' => 'Provider fetch failed']);
    exit;
}

$data = json_decode($response, true);
echo json_encode([
    'code' => 0,
    'msg'  => 'Success',
    'data' => $data['data'] ?? $data
]);
