#!/usr/bin/env php
<?php

// === Настройки запуска из консоли ===
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

// === DOCUMENT_ROOT сайта ===
$_SERVER["DOCUMENT_ROOT"] = '/home/c/ct47128/owen.kipaso.ru/public_html';

// === Подключаем ядро Битрикса ===
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Подключаем модуль main для использования CUtil::translit
if (!CModule::IncludeModule("main")) {
    throw new Exception("Не удалось подключить модуль main");
}

// === Константы ===
$iblockCode = '92';
$imageDir = $_SERVER["DOCUMENT_ROOT"] . '/upload/iblock/';
$jsonUrl = "https://owen.ru/export/catalog.json?host=owen.kipaso.ru&key=2PIPXjWSfUN9THUjSmExeOo0WDUVUks5";
$logFile = $_SERVER["DOCUMENT_ROOT"] . '/local/logs/owen_import.log';
$dryRun = false;

// === Функция логирования ===
function logMessage($msg) {
    global $logFile;
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    if (!is_writable($logDir)) {
        error_log("Log directory is not writable: $logDir");
        return;
    }
    file_put_contents($logFile, date("Y-m-d H:i:s") . " | " . $msg . "\n", FILE_APPEND);
}

// === Поиск раздела по символьному коду ===
function getSectionIdByCode($iblockId, $sectionCode) {
    $res = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $sectionCode], false, ['ID']);
    if ($arSection = $res->Fetch()) {
        logMessage("Найден раздел с CODE='$sectionCode', ID={$arSection['ID']}");
        return $arSection['ID'];
    }
    throw new Exception("Раздел с CODE='$sectionCode' не найден");
}

// === Генерация уникального символьного кода ===
function generateUniqueCode($name, $xmlId, $iblockId) {
    $baseCode = CUtil::translit($name, "ru", [
        "max_len" => 100,
        "replace_space" => "_",
        "replace_other" => "_",
        "change_case" => "lower",
        "delete_repeat_replace" => true
    ]);
    $code = $baseCode;
    $counter = 1;

    while (CIBlockElement::GetList([], ["IBLOCK_ID" => $iblockId, "CODE" => $code], false, false, ["ID"])->Fetch()) {
        $code = $baseCode . '-' . $xmlId . '-' . $counter;
        $counter++;
    }
    
    return $code;
}

// === Скачивание и сохранение изображения ===
function saveImageToBitrix($url, $dir, $itemName) {
    $fileName = basename(parse_url($url, PHP_URL_PATH));
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowedExt)) {
        logMessage("Неподдерживаемый формат файла: $url для товара '$itemName'");
        return null;
    }
    
    // Проверка, существует ли файл в Bitrix
    $filePath = $dir . $fileName;
    $res = CFile::GetList([], ['FILE_NAME' => $fileName, 'MODULE_ID' => 'iblock']);
    if ($file = $res->Fetch()) {
        logMessage("Файл уже существует в Bitrix: $filePath, ID={$file['ID']} для товара '$itemName'");
        return $file['ID'];
    }
    
    if (!file_exists($filePath)) {
        $content = @file_get_contents($url);
        if ($content === false) {
            logMessage("Не удалось скачать файл: $url для товара '$itemName'");
            return null;
        }
        if (!file_put_contents($filePath, $content)) {
            logMessage("Не удалось сохранить файл локально: $filePath для товара '$itemName'");
            return null;
        }
    }
    
    $fileArray = CFile::MakeFileArray($filePath);
    if (!$fileArray || !isset($fileArray['tmp_name']) || !is_readable($fileArray['tmp_name'])) {
        logMessage("Некорректный файл для Bitrix: $filePath для товара '$itemName'");
        return null;
    }
    
    $fileSize = filesize($filePath);
    if ($fileSize > 1024 * 1024 * 10) {
        logMessage("Файл слишком большой: $filePath, размер $fileSize байт для товара '$itemName'");
        return null;
    }
    
    $fileArray['MODULE_ID'] = 'iblock';
    $fileArray['name'] = $fileName;
    $fileArray['TITLE'] = $itemName . ' - ' . $fileName;

    // Принудительно отключаем подкаталоги и переименование
    COption::SetOptionString("iblock", "use_original_file_names", "Y");
    COption::SetOptionString("iblock", "file_subdir", "N");
    
    $fileId = CFile::SaveFile($fileArray, "iblock", false, false);
    if ($fileId === false || $fileId === 0) {
        logMessage("Ошибка сохранения файла в Bitrix: $filePath для товара '$itemName'");
        return null;
    }
    
    $file = CFile::GetByID($fileId)->Fetch();
    if (!$file) {
        logMessage("Файл с ID=$fileId не найден в Bitrix для товара '$itemName'");
        return null;
    }
    
    $filePathInBitrix = CFile::GetPath($fileId);
    $physicalPath = $_SERVER["DOCUMENT_ROOT"] . $filePathInBitrix;
    logMessage("Файл успешно сохранён в Bitrix: $filePath, ID=$fileId, путь в Bitrix: $filePathInBitrix, физический путь: $physicalPath для товара '$itemName'");
    
    // Проверяем существование файла
    if (!file_exists($physicalPath)) {
        logMessage("ВНИМАНИЕ: Файл не найден по физическому пути: $physicalPath для товара '$itemName'");
    } else {
        // Удаляем временный файл
        if (file_exists($filePath)) {
            @unlink($filePath);
            logMessage("Временный файл удалён: $filePath");
        }
    }
    
    return $fileId;
}

// === Формирование HTML для документации ===
function formatDocsAsHtml($docs) {
    $html = "<h3>Документация</h3><ul>";
    foreach ($docs as $docGroup) {
        foreach ($docGroup['items'] as $doc) {
            $html .= "<li><a href=\"{$doc['link']}\" target=\"_blank\">{$doc['name']}</a></li>";
        }
    }
    return $html . "</ul>";
}

// === Отключение обязательности DETAIL_PICTURE ===
function setDetailPictureRequired($iblockId, $required = false) {
    $iblockFields = CIBlock::GetFields($iblockId);
    $iblockFields['DETAIL_PICTURE']['IS_REQUIRED'] = $required ? 'Y' : 'N';
    CIBlock::SetFields($iblockId, $iblockFields);
    logMessage("Обязательность DETAIL_PICTURE установлена в: " . ($required ? 'Y' : 'N'));
}

try {
    logMessage("Bitrix version: " . (defined('SM_VERSION') ? SM_VERSION : 'Unknown'));
    logMessage("PHP user: " . get_current_user() . ", Group: " . posix_getgrgid(posix_getgid())['name']);
    
    // Проверка настроек модуля iblock
    $useOriginalNames = COption::GetOptionString("iblock", "use_original_file_names", "N");
    $useSubdirs = COption::GetOptionString("iblock", "file_subdir", "Y");
    logMessage("Настройки iblock: use_original_file_names=$useOriginalNames, file_subdir=$useSubdirs");

    // Проверка файлов ядра
    $bitrixFiles = ['/bitrix/modules/main/include.php', '/bitrix/modules/main/include/prolog_before.php'];
    foreach ($bitrixFiles as $file) {
        $fullPath = $_SERVER["DOCUMENT_ROOT"] . $file;
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            throw new Exception("Ошибка доступа к файлу: $fullPath");
        }
        logMessage("Файл доступен: $fullPath");
    }

    if (!CModule::IncludeModule("iblock")) throw new Exception("Модуль iblock не подключен");

    $rsIblock = CIBlock::GetList([], ["CODE" => $iblockCode]);
    $arIblock = $rsIblock->Fetch();
    if (!$arIblock) throw new Exception("Инфоблок с кодом '$iblockCode' не найден");
    $iblockId = $arIblock['ID'];
    logMessage("Найден инфоблок: ID=$iblockId, CODE=$iblockCode");

    $requiredProps = ["CML2_ARTICLE", "LINK_MANUFACTURER"];
    $properties = CIBlockProperty::GetList([], ["IBLOCK_ID" => $iblockId]);
    $existingProps = [];
    while ($prop = $properties->Fetch()) $existingProps[] = $prop["CODE"];
    foreach ($requiredProps as $prop) {
        if (!in_array($prop, $existingProps)) throw new Exception("Свойство $prop не найдено в инфоблоке");
    }
    if (!in_array("MORE_PHOTO", $existingProps)) {
        logMessage("Свойство MORE_PHOTO не найдено, изображения галереи не будут сохранены");
    }
    logMessage("Найдены требуемые свойства: " . implode(", ", $existingProps));

    if (!is_dir($imageDir)) mkdir($imageDir, 0755, true);
    if (!is_writable($imageDir)) throw new Exception("Папка недоступна для записи: $imageDir");
    logMessage("Папка для изображений доступна: $imageDir");

    logMessage("Начинаем импорт...");

    $ch = curl_init($jsonUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $jsonContent = curl_exec($ch);
    if ($jsonContent === false) {
        throw new Exception("Ошибка загрузки JSON: " . curl_error($ch));
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        throw new Exception("Ошибка загрузки JSON: HTTP код $httpCode");
    }
    logMessage("JSON успешно загружен (HTTP код: $httpCode)");

    $data = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("Ошибка разбора JSON: " . json_last_error_msg());
    logMessage("JSON успешно декодирован");

    if (!isset($data['categories']) || !is_array($data['categories'])) throw new Exception("Некорректная структура JSON");
    logMessage("Структура JSON проверена");

    $transactionStarted = false;
    \Bitrix\Main\Application::getConnection()->startTransaction();
    $transactionStarted = true;
    logMessage("Транзакция начата");

    // Отключаем обязательность DETAIL_PICTURE
    setDetailPictureRequired($iblockId, false);

    $currentXmlIds = [];

    foreach ($data['categories'] as $category) {
        $parentCode = CUtil::translit($category['name'], "ru", [
            "replace_space" => "_",
            "replace_other" => "_",
            "change_case" => "lower",
            "delete_repeat_replace" => true
        ]);
        logMessage("Обрабатывается категория: {$category['name']} (CODE=$parentCode)");
        try {
            $parentId = getSectionIdByCode($iblockId, $parentCode);
        } catch (Exception $e) {
            logMessage($e->getMessage());
            continue;
        }

        foreach ($category['items'] as $subCategory) {
            $subCatCode = CUtil::translit($subCategory['name'], "ru", [
                "replace_space" => "_",
                "replace_other" => "_",
                "change_case" => "lower",
                "delete_repeat_replace" => true
            ]);
            logMessage("Обрабатывается подкатегория: {$subCategory['name']} (CODE=$subCatCode)");
            try {
                $subCatId = getSectionIdByCode($iblockId, $subCatCode);
            } catch (Exception $e) {
                logMessage($e->getMessage());
                continue;
            }

            foreach ($subCategory['products'] as $item) {
                $currentXmlIds[] = $item['id'];
                $description = '';
                if (!empty($item['specs'])) $description .= "<h3>Характеристики</h3>" . $item['specs'];
                if (!empty($item['docs'])) $description .= formatDocsAsHtml($item['docs']);

                $mainImageId = (!empty($item['image'])) ? saveImageToBitrix($item['image'], $imageDir, $item['name']) : null;
                $gallery = [];
                if (!empty($item['images']) && in_array("MORE_PHOTO", $existingProps)) {
                    foreach ($item['images'] as $img) {
                        $imgId = saveImageToBitrix($img['src'], $imageDir, $item['name']);
                        if ($imgId) {
                            $gallery[] = ['VALUE' => $imgId, 'DESCRIPTION' => $item['name'] . ' - gallery'];
                        }
                    }
                }

                $arFields = [
                    "IBLOCK_ID" => $iblockId,
                    "NAME" => $item['name'],
                    "CODE" => generateUniqueCode($item['name'], $item['id'], $iblockId),
                    "XML_ID" => $item['id'],
                    "PROPERTY_VALUES" => [
                        "CML2_ARTICLE" => $item['sku'],
                        "LINK_MANUFACTURER" => $item['link'],
                    ],
                    "DETAIL_TEXT" => $description,
                    "DETAIL_TEXT_TYPE" => "html",
                    "ACTIVE" => "Y",
                    "IBLOCK_SECTION" => [$subCatId]
                ];

                // Добавляем DETAIL_PICTURE и PREVIEW_PICTURE
                if ($mainImageId) {
                    $file = CFile::GetByID($mainImageId)->Fetch();
                    if ($file) {
                        $filePathInBitrix = CFile::GetPath($mainImageId);
                        $physicalPath = $_SERVER["DOCUMENT_ROOT"] . $filePathInBitrix;
                        if (file_exists($physicalPath)) {
                            $arFields["DETAIL_PICTURE"] = CFile::MakeFileArray($physicalPath);
                            $arFields["PREVIEW_PICTURE"] = CFile::MakeFileArray($physicalPath);
                            logMessage("DETAIL_PICTURE и PREVIEW_PICTURE для '{$item['name']}': ID=$mainImageId, путь=$filePathInBitrix");
                        } else {
                            logMessage("Файл для DETAIL_PICTURE и PREVIEW_PICTURE не найден: $physicalPath для '{$item['name']}'");
                            $arFields["DETAIL_PICTURE"] = null;
                            $arFields["PREVIEW_PICTURE"] = null;
                        }
                    } else {
                        logMessage("DETAIL_PICTURE и PREVIEW_PICTURE не добавлены для '{$item['name']}': файл с ID=$mainImageId не существует");
                        $arFields["DETAIL_PICTURE"] = null;
                        $arFields["PREVIEW_PICTURE"] = null;
                    }
                }

                // Добавляем MORE_PHOTO
                if (!empty($gallery) && in_array("MORE_PHOTO", $existingProps)) {
                    foreach ($gallery as $galleryItem) {
                        $file = CFile::GetByID($galleryItem['VALUE'])->Fetch();
                        if (!$file) {
                            logMessage("MORE_PHOTO: файл с ID={$galleryItem['VALUE']} не существует для товара '{$item['name']}'");
                            $gallery = array_filter($gallery, function($item) use ($galleryItem) {
                                return $item['VALUE'] !== $galleryItem['VALUE'];
                            });
                        }
                    }
                    if (!empty($gallery)) {
                        $arFields["PROPERTY_VALUES"]["MORE_PHOTO"] = $gallery;
                        logMessage("MORE_PHOTO для '{$item['name']}': " . print_r($gallery, true));
                    } else {
                        logMessage("MORE_PHOTO не добавлен для '{$item['name']}': нет валидных файлов");
                    }
                }

                $el = new CIBlockElement;
                $res = CIBlockElement::GetList([], ["XML_ID" => $item['id'], "IBLOCK_ID" => $iblockId], false, false, ["ID", "NAME", "DETAIL_TEXT", "DETAIL_PICTURE", "PREVIEW_PICTURE"]);
                if ($arItem = $res->Fetch()) {
                    $needUpdate = $arItem['NAME'] !== $arFields['NAME'] || $arItem['DETAIL_TEXT'] !== $arFields['DETAIL_TEXT'] || !$arItem['DETAIL_PICTURE'] || !$arItem['PREVIEW_PICTURE'];
                    if ($needUpdate && !$dryRun) {
                        if ($el->Update($arItem["ID"], $arFields)) {
                            logMessage("Обновлён товар: {$item['name']} (XML_ID={$item['id']})");
                        } else {
                            logMessage("Ошибка обновления товара '{$item['name']}': {$el->LAST_ERROR}");
                        }
                    } else {
                        logMessage("Пропущен без изменений: {$item['name']} (XML_ID={$item['id']})");
                    }
                } else {
                    if (!$dryRun) {
                        $elementId = $el->Add($arFields);
                        if ($elementId) {
                            logMessage("Добавлен товар: {$item['name']} (XML_ID={$item['id']}, ElementID=$elementId)");
                        } else {
                            logMessage("Ошибка добавления товара '{$item['name']}': {$el->LAST_ERROR}");
                        }
                    }
                }
            }
        }
    }

    // Деактивация устаревших товаров
    logMessage("Проверка устаревших товаров...");
    $res = CIBlockElement::GetList([], ["IBLOCK_ID" => $iblockId], false, false, ["ID", "XML_ID", "DETAIL_PICTURE"]);
    $deactivatedCount = 0;
    while ($item = $res->Fetch()) {
        if (!in_array($item['XML_ID'], $currentXmlIds)) {
            $el = new CIBlockElement;
            $arFields = ["ACTIVE" => "N"];
            if (!$item['DETAIL_PICTURE']) {
                $arFields["DETAIL_PICTURE"] = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/1.gif");
                logMessage("Добавлена заглушка для DETAIL_PICTURE для товара '{$item['XML_ID']}'");
            }
            if (!$dryRun && $el->Update($item['ID'], $arFields)) {
                logMessage("Деактивирован товар: {$item['XML_ID']}");
                $deactivatedCount++;
            } else {
                logMessage("Ошибка деактивации товара '{$item['XML_ID']}': {$el->LAST_ERROR}");
            }
        }
    }
    logMessage("Деактивировано товаров: $deactivatedCount");

    // Диагностика товара itp17
    $res = CIBlockElement::GetList(
        [],
        ["IBLOCK_ID" => $iblockId, "XML_ID" => "itp17"],
        false,
        false,
        ["ID", "NAME", "DETAIL_PICTURE", "PREVIEW_PICTURE", "PROPERTY_MORE_PHOTO"]
    );
    if ($item = $res->Fetch()) {
        logMessage("Диагностика товара itp17: " . print_r($item, true));
        if ($item['DETAIL_PICTURE']) {
            $file = CFile::GetPath($item['DETAIL_PICTURE']);
            $physicalPath = $_SERVER["DOCUMENT_ROOT"] . $file;
            logMessage("Путь DETAIL_PICTURE: $file, физический путь: $physicalPath");
            if (!file_exists($physicalPath)) {
                logMessage("ВНИМАНИЕ: Файл DETAIL_PICTURE не найден: $physicalPath");
            }
        }
        if ($item['PREVIEW_PICTURE']) {
            $file = CFile::GetPath($item['PREVIEW_PICTURE']);
            $physicalPath = $_SERVER["DOCUMENT_ROOT"] . $file;
            logMessage("Путь PREVIEW_PICTURE: $file, физический путь: $physicalPath");
            if (!file_exists($physicalPath)) {
                logMessage("ВНИМАНИЕ: Файл PREVIEW_PICTURE не найден: $physicalPath");
            }
        }
        if ($item['PROPERTY_MORE_PHOTO_VALUE']) {
            foreach ((array)$item['PROPERTY_MORE_PHOTO_VALUE'] as $photoId) {
                $file = CFile::GetPath($photoId);
                $physicalPath = $_SERVER["DOCUMENT_ROOT"] . $file;
                logMessage("Путь MORE_PHOTO: $file, физический путь: $physicalPath");
                if (!file_exists($physicalPath)) {
                    logMessage("ВНИМАНИЕ: Файл MORE_PHOTO не найден: $physicalPath");
                }
            }
        }
    }

    // Восстанавливаем обязательность DETAIL_PICTURE
    setDetailPictureRequired($iblockId, true);

    \Bitrix\Main\Application::getConnection()->commitTransaction();
    logMessage("Импорт завершён успешно");

} catch (Exception $e) {
    if ($transactionStarted) {
        \Bitrix\Main\Application::getConnection()->rollbackTransaction();
        logMessage("Транзакция отменена");
    }
    logMessage("Критическая ошибка: " . $e->getMessage());
    die("Критическая ошибка: " . $e->getMessage());
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>