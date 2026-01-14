<?php

@set_time_limit(0);

$defaultUrl = 'https://meyertec.owen.ru/export/catalog.xml?host=owen.kipaso.ru&key=afOavhVgttik-rIgesgbk6Zkk-Y_by8W';

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot === '') {
    $docRoot = realpath(__DIR__ . '/../../..');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}

if ($docRoot === '' || !is_dir($docRoot)) {
    fwrite(STDERR, "Не удалось определить DOCUMENT_ROOT\n");
    exit(2);
}

$url = $defaultUrl;
$outPath = $docRoot . '/meyertecAPI.xml';
$timeout = 120;

if (php_sapi_name() === 'cli') {
    $args = $argv ?? [];
    if (!empty($args)) {
        array_shift($args);
    }

    foreach ($args as $arg) {
        if (preg_match('/^--url=(.+)$/', $arg, $m)) {
            $url = (string)$m[1];
        } elseif (preg_match('/^--out=(.+)$/', $arg, $m)) {
            $outPath = (string)$m[1];
        } elseif (preg_match('/^--timeout=(\d+)$/', $arg, $m)) {
            $timeout = max(1, (int)$m[1]);
        }
    }
} else {
    if (isset($_GET['url'])) {
        $url = (string)$_GET['url'];
    }
    if (isset($_GET['out'])) {
        $outPath = (string)$_GET['out'];
    }
    if (isset($_GET['timeout'])) {
        $timeout = max(1, (int)$_GET['timeout']);
    }
}

$lockPath = $docRoot . '/upload/meyertecAPI.xml.lock';
@mkdir(dirname($lockPath), 0755, true);
$lockFp = @fopen($lockPath, 'c');
if (!$lockFp) {
    fwrite(STDERR, "Не удалось открыть lock-файл: {$lockPath}\n");
    exit(3);
}

if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Скрипт уже выполняется (lock): {$lockPath}\n");
    fclose($lockFp);
    exit(0);
}

function downloadUrl(string $url, int $timeout): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'kipaso-meyertec-xml-updater/1.0',
        ]);

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return [null, "cURL error: {$err}", $http];
        }

        return [$body, null, $http];
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'follow_location' => 1,
            'user_agent' => 'kipaso-meyertec-xml-updater/1.0',
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        $e = error_get_last();
        return [null, 'file_get_contents error: ' . ($e['message'] ?? 'unknown'), 0];
    }

    return [$body, null, 200];
}

function isLikelyXml(string $content): bool
{
    $content = ltrim($content);
    if ($content === '') {
        return false;
    }
    if (stripos($content, '<?xml') !== 0) {
        return false;
    }

    return true;
}

[$xmlContent, $downloadError, $httpCode] = downloadUrl($url, $timeout);
if ($downloadError !== null) {
    fwrite(STDERR, "Ошибка скачивания: {$downloadError}\n");
    exit(4);
}

if ($httpCode !== 0 && $httpCode !== 200) {
    fwrite(STDERR, "HTTP код не 200: {$httpCode}\n");
    exit(5);
}

$xmlContent = (string)$xmlContent;
if (!isLikelyXml($xmlContent)) {
    fwrite(STDERR, "Скачанный контент не похож на XML (первые 200 символов):\n" . substr($xmlContent, 0, 200) . "\n");
    exit(6);
}

$outDir = dirname($outPath);
if (!is_dir($outDir)) {
    if (!@mkdir($outDir, 0755, true) && !is_dir($outDir)) {
        fwrite(STDERR, "Не удалось создать директорию: {$outDir}\n");
        exit(7);
    }
}

if (!is_writable($outDir)) {
    fwrite(STDERR, "Директория недоступна для записи: {$outDir}\n");
    exit(8);
}

$tmpPath = $outPath . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));

$bytes = @file_put_contents($tmpPath, $xmlContent, LOCK_EX);
if ($bytes === false || $bytes <= 0) {
    @unlink($tmpPath);
    fwrite(STDERR, "Не удалось записать временный файл: {$tmpPath}\n");
    exit(9);
}

if (!@rename($tmpPath, $outPath)) {
    @unlink($tmpPath);
    fwrite(STDERR, "Не удалось заменить файл назначения: {$outPath}\n");
    exit(10);
}

@chmod($outPath, 0644);

fwrite(STDOUT, "OK: обновлен {$outPath} (bytes={$bytes})\n");

flock($lockFp, LOCK_UN);
fclose($lockFp);

exit(0);
