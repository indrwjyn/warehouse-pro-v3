<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_login();

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);
$awb     = strtoupper(preg_replace('/\s+/', '', clean_input($payload['awb'] ?? '')));
$courier = normalize_tracking_courier(clean_input($payload['courier'] ?? 'auto'));
$number  = preg_replace('/\D+/', '', clean_input($payload['number'] ?? ''));
$couriers = tracking_couriers();
$isAuto = ($courier === 'auto');

if ($awb === '') {
    echo json_encode(['status' => 400, 'message' => 'Nomor resi wajib diisi.']);
    exit;
}

if (!$isAuto && !isset($couriers[$courier])) {
    echo json_encode(['status' => 400, 'message' => 'Kode ekspedisi tidak didukung.']);
    exit;
}

if ($number !== '' && strlen($number) !== 5) {
    echo json_encode(['status' => 400, 'message' => 'Parameter number harus 5 digit terakhir nomor HP.']);
    exit;
}

if (BINDERBYTE_API_KEY === '' || BINDERBYTE_API_KEY === 'demo') {
    echo json_encode(['status' => 500, 'message' => 'API key BinderByte belum diatur. Set environment variable BINDERBYTE_API_KEY (lihat README).']);
    exit;
}

function request_tracking($courier, $awb, $number = '') {
    $query = [
        'api_key' => BINDERBYTE_API_KEY,
        'courier' => $courier,
        'awb'     => $awb,
    ];
    if ($number !== '') {
        $query['number'] = $number;
    }
    $endpoint = binderbyte_endpoint('track') . '?' . http_build_query($query);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 18,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'LocalWarehouseTracking/1.0',
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        return [
            'ok' => false,
            'status' => 502,
            'message' => 'Gagal menghubungi API BinderByte: ' . $curlError,
            'data' => null,
        ];
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return [
            'ok' => false,
            'status' => 502,
            'message' => 'Respons BinderByte tidak valid.',
            'data' => null,
        ];
    }

    $apiStatus  = (int) ($json['status'] ?? $json['code'] ?? $httpCode);
    $apiMessage = $json['message'] ?? $json['messages'] ?? 'Request tracking gagal.';
    $data       = $json['data'] ?? $json['value'] ?? null;

    return [
        'ok' => ($apiStatus === 200 && !empty($data)),
        'status' => $apiStatus ?: 400,
        'message' => $apiMessage,
        'data' => $data,
    ];
}

$attempted = $isAuto ? tracking_auto_couriers($awb) : [$courier];
$lastResult = null;
$matchedCourier = null;

foreach ($attempted as $candidate) {
    if (!isset($couriers[$candidate])) continue;
    $result = request_tracking($candidate, $awb, $number);
    $lastResult = $result;
    if ($result['ok']) {
        $matchedCourier = $candidate;
        break;
    }
}

if ($matchedCourier === null) {
    $message = $isAuto
        ? 'Resi belum ditemukan di ekspedisi yang dicoba: ' . implode(', ', array_map(fn($code) => $couriers[$code] ?? $code, $attempted))
        : ($lastResult['message'] ?? 'Data resi tidak ditemukan.');
    echo json_encode(['status' => $lastResult['status'] ?? 404, 'message' => $message]);
    exit;
}

echo json_encode([
    'status'                 => 200,
    'message'                => $lastResult['message'],
    'data'                   => $lastResult['data'],
    'detected_courier_code'  => $matchedCourier,
    'detected_courier_name'  => $couriers[$matchedCourier],
    'attempted_couriers'     => $isAuto ? $attempted : [],
]);
