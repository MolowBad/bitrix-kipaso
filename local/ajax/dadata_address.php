<?php
// /local/ajax/dadata_address.php
// Прокси к DaData Suggestions API: подсказки адресов

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET','POST'], true)) {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method Not Allowed']);
    exit;
}

try {
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!is_array($data)) { $data = []; }

    
    if (!isset($data['query'])) {
        $data['query'] = isset($_REQUEST['query']) ? (string)$_REQUEST['query'] : '';
    }

    $query = trim((string)($data['query'] ?? ''));
    if ($query === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Parameter "query" is required']);
        exit;
    }

    $count = isset($data['count']) ? (int)$data['count'] : 10;
    if ($count < 1) $count = 1;
    if ($count > 20) $count = 20;

    $payload = [
        'query' => $query,
        'count' => $count,
    ];

    // Прозрачно пробрасываем поддерживаемые параметры DaData Suggestions
    $passKeys = [
        'language', 'division', 'locations', 'locations_geo', 'locations_boost',
        'from_bound', 'to_bound', 'restrict_value', 'bounds', 'street_q'
    ];
    foreach ($passKeys as $k) {
        if (array_key_exists($k, $data)) { $payload[$k] = $data[$k]; }
    }

    
    $DADATA_TOKEN = '372ff00ccd7175ccd0db957aa87989e6126edbb3';
    $endpoint = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address';
    $timeout = 10;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . $DADATA_TOKEN,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        throw new Exception('DaData request error: ' . $err, 502);
    }
    if ($code < 200 || $code >= 300) {
        throw new Exception('DaData unexpected status: ' . $code, $code);
    }

    $res = json_decode($body, true);
    if (!is_array($res)) { throw new Exception('DaData invalid response', 502); }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code >= 600) { $code = 500; }
    http_response_code($code);
    echo json_encode(['success'=>false,'error'=>$e->getMessage(),'code'=>$code], JSON_UNESCAPED_UNICODE);
    exit;
}
