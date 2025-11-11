<?php
use Bitrix\Main\Loader;

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define ('DisableEventsCheck', true);
define('BX_NO_ACCELERATOR_RESET', true);


$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot === '') {
    
    $docRoot = realpath(__DIR__ . '/../../');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}
// Инициализация переменных окружения для CLI до подключения пролога
if (PHP_SAPI === 'cli') {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'test.owen.kipaso.ru';
    $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'test.owen.kipaso.ru';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/local/scripts/import_guid.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    // Диагностика в CLI (без вывода до подключения пролога)
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
require $docRoot . '/bitrix/modules/main/include/prolog_before.php';
//require_once __DIR__.'/../local/php_interface/bootstrap.php'; // еще как вариант конфиги, константы, автолоад

@set_time_limit(0);
@ini_set('memory_limit', '1024M');

if (!Loader::includeModule('iblock')) {
    die('ошибка подключения модуля iblock');
}


const SKU_IBLOCK_ID = 17;
const GUID_PROPERTY_ID = 116;
const XML_PATH = '/1c-exchange/ДанныеПоНоменк.xml';
const LOG_FILE = '/upload/logs/import_guid.log';

//$dir = rtrim($docRoot ?? '', '/').'/upload/logs';
$dir = rtrim($docRoot, '/').'/upload/logs';

if (file_exists($dir) && !is_dir($dir)) {
    echo "Путь занят файлом: $dir\n";
    exit;
}

if (!is_dir($dir)) {//встроенная функция в PHP, которая проверяет, является ли указанный путь директорией (каталогом)
    $ok = @mkdir($dir, 0775, true);
    if (!$ok && !is_dir($dir)) {
        $err = error_get_last()['message'] ?? 'ошибка создания директории';
        echo "Не удалось создать директорию $dir: $err\n";
        exit;
    }
    @chmod($dir, 0775);
    if ((int) ($_GET['log'] ?? 0)===1) {
        echo "Директория $dir создана\n";
    }
}

//dirname — функция в PHP, которая возвращает путь к родительскому каталогу из указанного пути к файлу или каталогу.
$logPath = $docRoot . LOG_FILE;

function logMessage(string $level, string $message, bool $verbose, string $logPath): void
    {
        $logMessage = date('[d.m.Y H:i:s] ') . "[$level] " . $message . PHP_EOL;    
        @file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);//функция в PHP, которая позволяет записать строку в файл,флаг FILE_APPEND позволяет не перезаписать весь файл 
        if ($verbose === true) {
            echo $logMessage;
        }
    }

if (!file_exists($logPath)) { //проверяет существование файла или каталога
    $oke = touch($logPath); // создаем файл если его нет
    if ( !$oke && !file_exists($logPath)) {
        $err = error_get_last()['message'] ?? 'ошибка создания файла';
        exit ("ошибка создания файла  $logPath : $err");
    }
    @chmod($logPath, 0664);
}


if (!is_file($logPath)) {
    exit('ошибка создания файла ' . $logPath);
}

if (!is_writable($logPath)) { // доступен ли файл для записи
    exit('ошибка создания файла ' . $logPath);
}


$run = isset($_GET['run']) ? (int)$_GET['run'] : 0;
$dry = isset($_GET['dry']) ? (int)$_GET['dry'] : 0;
$log = isset($_GET['log']) ? (int)$_GET['log'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

// CLI режим: если запускаем из терминала без параметров — включим run/log по умолчанию
if (PHP_SAPI === 'cli') {
    $run = $run ?: 1;
    $log = $log ?: 1;
}

$verbose = ((int)$log === 1);

if (!$run) {
    echo "Импорт GUID готов. Запустите с параметром ?run=1 для выполнения.\n";
    echo "Параметры: dry=1 (без обновлений), log=1 (подробный лог), limit=N.\n";
    echo "XML: " . XML_PATH . "\n";
    echo "SKU_IBLOCK_ID: " . SKU_IBLOCK_ID . "\n";
    exit;
}

$xmlAbs = $docRoot . XML_PATH;

if (!file_exists($xmlAbs)) {
    logMessage('ERROR', 'Файл XML не найден: ' . $xmlAbs, true, $logPath);
    exit('Файл XML не найден: ' . $xmlAbs);
}

if (!is_file($xmlAbs)) {
    logMessage('ERROR', 'Файл XML не является файлом: ' . $xmlAbs, true, $logPath);
    exit('Файл XML не является файлом: ' . $xmlAbs);
}

if (!is_readable($xmlAbs)) {
    logMessage('ERROR', 'Файл XML не доступен для чтения: ' . $xmlAbs, true, $logPath);
    exit('Файл XML не доступен для чтения: ' . $xmlAbs);
}

$stats = [
    'total' => 0,
    'found' => 0,
    'updated' => 0,
    'skippedSame' => 0,
    'notFound' => 0,
    'duplicatesXml' => 0,
    'errors' => 0,
];

$reader = new XMLReader();
if (!$reader->open($xmlAbs)) {
    logMessage('ERROR', 'Ошибка открытия XML: ' . $xmlAbs, true, $logPath);
    exit('Ошибка открытия XML: ' . $xmlAbs);
}
$map = []; //хранит уже обработанные articule из XML для выявления дубликатов


while ($reader->read()) { //читаем XML файл read() читает следующий узел
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'NOMENKLATURE') { //если узел является элементом и его имя равно 'NOMENKLATURE'

        $code = trim((string)$reader->getAttribute('articule')); //получаем значение атрибута articule
        $guid = trim((string)$reader->getAttribute('guid')); //получаем значение атрибута guid

        if ($code === '' || $guid === '') {
            $stats['errors']++;
            logMessage('ERROR', "Пустой articule или guid в XML", $log === 1, $logPath);
            continue;
        }
        if (array_key_exists($code, $map)) {
            $stats['duplicatesXml']++;
            logMessage('WARNING', "Дубликат articule в XML: '{$code}'", $log === 1, $logPath);
            continue;
        }

        $map[$code] = $guid; //добавляем articule и guid в массив map для отслеживания дубликатов

    }
}
$reader->close();
if (empty($map)) {
    logMessage('error', 'Карта сопоставлений пуста: нет данных для импорта', $verbose, $logPath);
    exit('Нет данных для импорта');
}

// Диагностика: выведем размер карты и первые 5 пар CODE => GUID, затем завершим работу
$size = count($map);
logMessage('info', 'map size=' . $size, $verbose, $logPath);
$shown = 0;
foreach ($map as $k => $v) {
    logMessage('info', 'SAMPLE ' . $k . ' => ' . $v, true, $logPath);
    if (++$shown >= 5) { 
        break; 
    }
}
echo "Диагностика карты завершена. Запись в БД пока не выполняется.\n";
exit(0);



    


