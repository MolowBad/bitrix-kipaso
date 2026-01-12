<?php
// /local/ajax/egrul_lookup.php
// AJAX-эндпоинт: принимает inn или query, возвращает JSON с данными из egrul.nalog.ru


header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET','POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}


$inn = isset($_REQUEST['inn']) ? trim((string)$_REQUEST['inn']) : '';
$query = isset($_REQUEST['query']) ? trim((string)$_REQUEST['query']) : '';
$lookup = $inn !== '' ? $inn : $query;

if ($lookup === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parameter "inn" or "query" is required']);
    exit;
}


// DaData: поиск организации


try {
    $DADATA_TOKEN = '372ff00ccd7175ccd0db957aa87989e6126edbb3'; 
    $timeout = 12;

    $isInn = ($inn !== '' && preg_match('/^\d{10}(\d{2})?$/', $inn));
    $endpoint = $isInn
        ? 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party'
        : 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party';

    $payload = [
        'query' => $lookup,
        'branch_type' => 'MAIN', 
        'count' => 5,
    ];

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
    if (!is_array($res) || !isset($res['suggestions']) || !is_array($res['suggestions'])) {
        throw new Exception('DaData invalid response', 502);
    }

    
    $pick = null;
    foreach ($res['suggestions'] as $s) {
        $dt = $s['data'] ?? [];
        if (!is_array($dt)) continue;
        if (($dt['branch_type'] ?? '') === 'MAIN') { $pick = $s; break; }
        if ($pick === null) { $pick = $s; }
    }

    if ($pick === null) {
        echo json_encode([
            'success' => true,
            'source'  => 'dadata.ru',
            'query'   => $lookup,
            'data'    => null,
            'message' => 'No results',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $d = $pick['data'] ?? [];
    $companyName = $d['name']['full_with_opf'] ?? ($d['name']['short_with_opf'] ?? ($pick['value'] ?? ''));
    $legalAddress = $d['address']['unrestricted_value'] ?? ($d['address']['value'] ?? '');
    $postalCode   = $d['address']['data']['postal_code'] ?? '';
    $kpp = $d['kpp'] ?? '';
    $innVal = $d['inn'] ?? '';
    $ogrn = $d['ogrn'] ?? '';
    $manager = $d['management']['name'] ?? '';
    
    $regDate = '';
    if (!empty($d['state']['registration_date'])) {
        $ts = (int)$d['state']['registration_date'] / 1000; 
        if ($ts > 0) { $regDate = date('d.m.Y', $ts); }
    }

    echo json_encode([
        'success' => true,
        'source'  => 'dadata.ru',
        'query'   => $lookup,
        'data'    => [
            'company_name'   => (string)$companyName,
            'legal_address'  => (string)$legalAddress,
            'kpp'            => (string)$kpp,
            'postal_code'    => (string)$postalCode,
            'contact_person' => (string)$manager,
            'inn'            => (string)$innVal,
            'ogrn'           => (string)$ogrn,
            'register_date'  => (string)$regDate,
            'raw'            => $d,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code >= 600) { $code = 502; }
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}
// тут были альтернатива того что будет если Dadata не отвечает,удалил пока что