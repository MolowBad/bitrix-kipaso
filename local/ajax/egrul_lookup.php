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


function egrul_http_post($url, array $postFields, $cookieFile, $timeout = 15) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: https://egrul.nalog.ru',
            'Referer: https://egrul.nalog.ru/',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$body, $code, $errno, $err];
}

function egrul_http_get($url, $cookieFile, $timeout = 15) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Referer: https://egrul.nalog.ru/',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$body, $code, $errno, $err];
}

function json_safe_decode($str) {
    $data = json_decode($str, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
}

function normalize_subject(array $s) {
    $fullName = $s['n'] ?? '';
    $abbr = $s['c'] ?? '';
    $companyName = $fullName !== '' ? $fullName : $abbr;
    $head = $s['g'] ?? '';
    $contact = $head;
    
    if (strpos($head, ':') !== false) {
        $parts = explode(':', $head, 2);
        $contact = trim($parts[1]);
    }
    return [
        'company_name'  => $companyName,
        'legal_address' => (string)($s['a'] ?? ''),
        'kpp'           => (string)($s['p'] ?? ''),
        'contact_person'=> $contact,
        'inn'           => (string)($s['i'] ?? ''),
        'ogrn'          => (string)($s['o'] ?? ''),
        'register_date' => (string)($s['r'] ?? ''),
        'raw'           => $s,
    ];
}

try {
    
    $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'egrul_' . md5($lookup) . '.cookie';

    
    [$body1, $code1, $errno1, $err1] = egrul_http_post('https://egrul.nalog.ru/', [
        'query' => $lookup,
        'vyp3CaptchaToken' => '',
        'page' => '',
        'nameEq' => 'on',
        'region' => '',
        'PreventChromeAutocomplete' => '',
    ], $cookieFile);

    if ($errno1 !== 0) {
        throw new Exception('cURL POST error: ' . $err1, 500);
    }
    if ($code1 < 200 || $code1 >= 300) {
        throw new Exception('Unexpected POST status: ' . $code1, $code1);
    }

    $json1 = json_safe_decode($body1);
    if (!is_array($json1) || !isset($json1['t'])) {
        throw new Exception('Invalid POST response format', 502);
    }
    $t = (string)$json1['t'];
    if ($t === '') {
        throw new Exception('Empty token received', 502);
    }

    
    [$body2, $code2, $errno2, $err2] = egrul_http_get('https://egrul.nalog.ru/search-result/' . rawurlencode($t), $cookieFile);
    if ($errno2 !== 0) {
        throw new Exception('cURL GET error: ' . $err2, 500);
    }
    if ($code2 < 200 || $code2 >= 300) {
        throw new Exception('Unexpected GET status: ' . $code2, $code2);
    }

    $json2 = json_safe_decode($body2);
    if (!is_array($json2) || !isset($json2['rows']) || !is_array($json2['rows'])) {
        throw new Exception('Invalid GET response format', 502);
    }

    $rows = $json2['rows'];
    if (empty($rows)) {
        echo json_encode([
            'success' => true,
            'source'  => 'egrul.nalog.ru',
            'query'   => $lookup,
            'data'    => null,
            'message' => 'No results',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    
    $selected = $rows[0];
    if ($inn !== '' && preg_match('/^\d{10}(\d{2})?$/', $inn)) {
        foreach ($rows as $row) {
            if (isset($row['i']) && $row['i'] === $inn) {
                $selected = $row;
                break;
            }
        }
    }

    $normalized = normalize_subject($selected);

    echo json_encode([
        'success' => true,
        'source'  => 'egrul.nalog.ru',
        'query'   => $lookup,
        'data'    => $normalized,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code >= 600) {
        $code = 500;
    }
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'code'    => $code,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
