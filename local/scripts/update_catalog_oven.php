<?php
@set_time_limit(0);
@ini_set('memory_limit', '1024M');
@ini_set('display_errors', 1);

// Bootstrap DOCUMENT_ROOT (не тянем Bitrix — не требуется)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot === '') {
    $docRoot = realpath(__DIR__ . '/../../..');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}

// Конфигурация по умолчанию
$TARGET_REL = '/catalogOven.xml';
$SOURCE_URL = 'https://owen.ru/export/catalog.xml?host=owen.kipaso.ru&key=2PIPXjWSfUN9THUjSmExeOo0WDUVUks5';
$RETRIES    = isset($_GET['retries']) ? max(0, (int)$_GET['retries']) : 3;
$TIMEOUT    = isset($_GET['timeout']) ? max(5, (int)$_GET['timeout']) : 30; // секунд
$RUN        = isset($_GET['run']) ? (int)$_GET['run'] : 0;
$DRY        = isset($_GET['dry']) ? (int)$_GET['dry'] : 0; // dry=1 — ничего не меняем
$VERBOSE    = isset($_GET['log']) ? (int)$_GET['log'] : 1; // лог на экран
$USER_URL   = isset($_GET['url']) ? trim((string)$_GET['url']) : '';

if ($USER_URL !== '') { $SOURCE_URL = $USER_URL; }

$targetAbs = $docRoot . $TARGET_REL;
$targetDir = dirname($targetAbs);
$uploadDir = $docRoot . '/public_html/upload';
$backupDir = $uploadDir . '/catalog_oven_backups';
$logFile   = $uploadDir . '/update_catalog_oven.log';
$lockFile  = $uploadDir . '/update_catalog_oven.lock';

// Ответ всегда в текстовом виде
header('Content-Type: text/plain; charset=utf-8');

// Фиксируем целевой путь строго в /public_html/catalogOven.xml
$TARGET_REL = '/public_html/catalogOven.xml';
$targetAbs = $docRoot . $TARGET_REL;
$targetDir = dirname($targetAbs);

// Поддержка запуска из CLI: разбираем аргументы вида
//   --run
//   --dry
//   --retries=5
//   --timeout=60
//   --url="https://..."
if (PHP_SAPI === 'cli') {
    global $argv;
    $args = $argv ?? [];
    if (!empty($args)) { array_shift($args); }
    foreach ($args as $a) {
        $a = trim($a);
        if ($a === '--run') { $RUN = 1; }
        elseif ($a === '--dry') { $DRY = 1; }
        elseif (preg_match('/^--retries=(\d+)$/', $a, $m)) { $RETRIES = max(0, (int)$m[1]); }
        elseif (preg_match('/^--timeout=(\d+)$/', $a, $m)) { $TIMEOUT = max(5, (int)$m[1]); }
        elseif (preg_match('/^--url=(.+)$/', $a, $m)) { $SOURCE_URL = trim($m[1], "\"' "); }
        // Поддержим также стиль key=value без префикса -- на всякий случай
        elseif (preg_match('/^run=(\d+)$/', $a, $m)) { $RUN = (int)$m[1]; }
        elseif (preg_match('/^dry=(\d+)$/', $a, $m)) { $DRY = (int)$m[1]; }
        elseif (preg_match('/^retries=(\d+)$/', $a, $m)) { $RETRIES = max(0, (int)$m[1]); }
        elseif (preg_match('/^timeout=(\d+)$/', $a, $m)) { $TIMEOUT = max(5, (int)$m[1]); }
        elseif (preg_match('/^url=(.+)$/', $a, $m)) { $SOURCE_URL = trim($m[1], "\"' "); }
        elseif ($a === '--override-date') { $_GET['override_date'] = '1'; }
    }
}

function logm($msg, $verbose = 1, $logFile = null) {
    $line = date('Y-m-d H:i:s') . ' ' . $msg . "\n";
    if ($verbose) {
        echo $line;
        @flush();
    }
    if ($logFile) {
        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}

function ensureDir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

function acquireLock($path) {
    $h = @fopen($path, 'c+');
    if (!$h) { return [null, 'Cannot open lock file']; }
    if (!@flock($h, LOCK_EX | LOCK_NB)) {
        return [null, 'Another process is running'];
    }
    ftruncate($h, 0);
    fwrite($h, (string)getmypid());
    fflush($h);
    return [$h, null];
}

function releaseLock($h) {
    if (is_resource($h)) { @flock($h, LOCK_UN); @fclose($h); }
}

function httpDownload($url, $timeout, &$httpCode = 0, &$err = '', &$headers = null) {
    $respHeaders = [];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'CatalogOvenUpdater/1.0',
        CURLOPT_ENCODING => '', // включаем поддержку gzip/deflate/brotli при наличии
        CURLOPT_HTTPHEADER => [
            'Accept: application/xml, text/xml, */*;q=0.9',
            'Accept-Encoding: gzip, deflate, br'
        ],
        CURLOPT_HEADERFUNCTION => function($ch, $header) use (&$respHeaders) {
            $len = strlen($header);
            $header = trim($header);
            if ($header !== '' && strpos($header, ':') !== false) {
                [$name, $value] = array_map('trim', explode(':', $header, 2));
                $lname = strtolower($name);
                if (!isset($respHeaders[$lname])) { $respHeaders[$lname] = []; }
                $respHeaders[$lname][] = $value;
            }
            return $len;
        },
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
    }
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (is_array($headers)) { $headers = $respHeaders; }

    // Fallback: если пришел 200, но пустое тело — попробуем через streams
    if ($err === '' && $httpCode >= 200 && $httpCode < 300 && (!is_string($body) || $body === '')) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'header' => "User-Agent: CatalogOvenUpdater/1.0\r\nAccept: application/xml, text/xml, */*;q=0.9\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'capture_peer_cert' => false,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) { $body = ''; }
    }
    return $body;
}

function downloadWithRetries($url, $retries, $timeout, $verbose, $logFile) {
    $attempt = 0; $lastErr = ''; $lastCode = 0; $lastBody = null; $lastHeaders = [];
    while ($attempt <= $retries) {
        $attempt++;
        logm("[HTTP] Запрос #{$attempt}: {$url}", $verbose, $logFile);
        $headers = [];
        $body = httpDownload($url, $timeout, $code, $err, $headers);
        if ($err !== '') { $lastErr = $err; }
        $lastCode = (int)$code; $lastBody = $body; $lastHeaders = $headers;
        $cl = isset($headers['content-length'][0]) ? $headers['content-length'][0] : '-';
        $ce = isset($headers['content-encoding'][0]) ? $headers['content-encoding'][0] : '-';
        $ct = isset($headers['content-type'][0]) ? $headers['content-type'][0] : '-';
        $previewLen = is_string($body) ? strlen($body) : 0;
        if ($code >= 200 && $code < 300 && $previewLen > 0) {
            logm("[HTTP] Успех: код {$code}, CT={$ct}, CE={$ce}, CL={$cl}, размер: {$previewLen} байт", $verbose, $logFile);
            return [$body, null];
        }
        logm("[HTTP] Ошибка: код={$code}, ошибка='{$err}', CT={$ct}, CE={$ce}, CL={$cl}, body_len={$previewLen}", $verbose, $logFile);
        // Небольшая пауза перед ретраем
        usleep(300000);
    }
    return [null, "Failed to download after " . ($retries + 1) . " attempts. Last code={$lastCode}, err='{$lastErr}'"];
}

function isValidXml($str, &$rootName = '') {
    if (!is_string($str) || $str === '') { return false; }
    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($str);
    $ok = ($xml !== false);
    if ($ok) {
        $rootName = $xml->getName();
    }
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $ok;
}

// Печать подсказки если не run=1
if (!$RUN) {
    echo "CatalogOven Updater — готов к запуску.\n";
    echo "Как пользоваться:\n";
    echo "  /local/scripts/update_catalog_oven.php?run=1 — выполнить обновление\n";
    echo "Параметры:\n";
    echo "  url=...     — источник XML (по умолчанию официальный URL)\n";
    echo "  retries=N   — число повторов при неудаче (по умолчанию {$RETRIES})\n";
    echo "  timeout=N   — таймаут в сек. (по умолчанию {$TIMEOUT})\n";
    echo "  dry=1       — режим проверки без записи файла\n";
    echo "  log=0|1     — выводить лог на экран (по умолчанию 1)\n";
    echo "Файлы:\n";
    echo "  Целевой: {$TARGET_REL}\n";
    echo "  Логи:   /public_html/upload/update_catalog_oven.log\n";
    echo "  Бэкапы: /public_html/upload/catalog_oven_backups/\n";
    exit;
}

ensureDir($uploadDir);
ensureDir($backupDir);
ensureDir($targetDir);

// Блокировка, чтобы не было одновременных запусков
[$lockHandle, $lockErr] = acquireLock($lockFile);
if (!$lockHandle) {
    logm('[LOCK] ' . $lockErr, $VERBOSE, $logFile);
    http_response_code(423);
    exit;
}

try {
    logm('--- START CatalogOven update ---', $VERBOSE, $logFile);
    logm('DOCUMENT_ROOT: ' . $docRoot, $VERBOSE, $logFile);
    logm('Target file: ' . $targetAbs, $VERBOSE, $logFile);
    logm('Source URL:  ' . $SOURCE_URL, $VERBOSE, $logFile);

    // 1) Скачиваем
    [$body, $err] = downloadWithRetries($SOURCE_URL, $RETRIES, $TIMEOUT, $VERBOSE, $logFile);
    if ($err !== null) {
        logm('[ERR] Загрузка не удалась: ' . $err, $VERBOSE, $logFile);
        http_response_code(502);
        exit;
    }

    // 2) Валидация XML
    $root = '';
    if (!isValidXml($body, $root)) {
        logm('[ERR] Получен некорректный XML. Прерывание.', $VERBOSE, $logFile);
        http_response_code(500);
        exit;
    }
    logm('[OK] XML валиден. Корневой тег: ' . $root, $VERBOSE, $logFile);

    // 2.1) Логируем исходный атрибут date корня и по желанию переопределяем на текущее время
    $srcDate = '';
    $xmlTmp = @simplexml_load_string($body);
    if ($xmlTmp !== false) {
        $srcDate = isset($xmlTmp['date']) ? (string)$xmlTmp['date'] : '';
        if ($srcDate !== '') {
            logm('[INFO] Исходный root@date от поставщика: ' . $srcDate, $VERBOSE, $logFile);
        }
        $override = isset($_GET['override_date']) && (string)$_GET['override_date'] === '1';
        if ($override) {
            $newDate = date('Y-m-d H:i');
            $xmlTmp['date'] = $newDate;
            $body = $xmlTmp->asXML();
            logm('[INFO] root@date переопределён на текущее серверное время: ' . $newDate, $VERBOSE, $logFile);
        }
    }

    // 3) Сравнение с текущим содержимым (если есть)
    $changed = true;
    if (is_file($targetAbs)) {
        $currentHash = @md5_file($targetAbs) ?: '';
        $newHash     = md5($body);
        if ($currentHash !== '' && $currentHash === $newHash) {
            logm('[SKIP] Содержимое не изменилось (MD5 совпадает).', $VERBOSE, $logFile);
            $changed = false;
        } else {
            logm('[DIFF] Обнаружены изменения: old=' . $currentHash . ', new=' . $newHash, $VERBOSE, $logFile);
        }
    }

    if ($DRY) {
        logm('[DRY] Режим dry-run: запись и бэкап НЕ выполняются.', $VERBOSE, $logFile);
        http_response_code(200);
        exit;
    }

    if (!$changed) {
        http_response_code(200);
        exit; // Нечего обновлять
    }

    // 4) Бэкап текущего файла (если есть)
    if (is_file($targetAbs)) {
        $ts = date('Ymd-His');
        $backupName = $backupDir . '/catalogOven-' . $ts . '.xml';
        if (@copy($targetAbs, $backupName)) {
            logm('[BACKUP] Создан бэкап: ' . $backupName, $VERBOSE, $logFile);
        } else {
            logm('[WARN] Не удалось создать бэкап текущего файла.', $VERBOSE, $logFile);
        }
    }

    // 5) Атомарная запись во временный файл и rename
    $tmp = $targetDir . '/.catalogOven.tmp.' . getmypid();
    $bytes = @file_put_contents($tmp, $body);
    if ($bytes === false || $bytes <= 0) {
        logm('[ERR] Не удалось записать временный файл: ' . $tmp, $VERBOSE, $logFile);
        @unlink($tmp);
        http_response_code(500);
        exit;
    }
    // Пробуем fsync на *nix системах (на Windows игнорируется)
    $fh = @fopen($tmp, 'r'); if ($fh) { @fflush($fh); @fclose($fh); }

    if (!@rename($tmp, $targetAbs)) {
        logm('[ERR] Не удалось переименовать временный файл в целевой.', $VERBOSE, $logFile);
        @unlink($tmp);
        http_response_code(500);
        exit;
    }

    logm('[OK] Файл успешно обновлён: ' . $targetAbs, $VERBOSE, $logFile);
    http_response_code(200);
} finally {
    releaseLock($lockHandle);
    logm('--- END CatalogOven update ---', $VERBOSE, $logFile);
}
