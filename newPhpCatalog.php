<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3) Включаем расширенное логирование
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/upload/owen_import_log.txt');
ini_set('log_errors', 1);

// Убираем лимит времени 
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '1024M'); 


ini_set('max_input_time', 0);       
ini_set('default_socket_timeout', 600); 
ini_set('post_max_size', '64M');    
ini_set('upload_max_filesize', '64M');
ini_set('output_buffering', 'Off'); 


if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
    apache_setenv('dont-vary', 1);
}


ignore_user_abort(true);


$_SERVER["DOCUMENT_ROOT"] = __DIR__;
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iblock/prolog.php');


if(!CModule::IncludeModule('iblock')) {
    trigger_error('Модуль iblock не подключен', E_USER_ERROR);
}

// Подключаем модуль торгового каталога для работы с ценами
if(!CModule::IncludeModule('catalog')) {
    trigger_error('Модуль catalog не подключен', E_USER_ERROR);
}


$iblockId = 16;
$xmlPath  = $_SERVER["DOCUMENT_ROOT"]."/catalogOven.xml";
if(!file_exists($xmlPath)) {
    die("XML-файл не найден по пути $xmlPath");
}



$xml = simplexml_load_file($xmlPath);
if(!$xml) {
    die("Ошибка парсинга XML");
}

function importSections($nodes, $parentId = 0) {
    global $iblockId;
    $secObj = new CIBlockSection;
    foreach($nodes as $node) {
        $code = (string)$node->id;
        $name = (string)$node->name;

        
        $db = CIBlockSection::GetList(
            [], 
            ["IBLOCK_ID"=>$iblockId, "CODE"=>$code], 
            false, 
            ["ID"]
        );
        if($exist = $db->Fetch()) {
            $sectionId = $exist["ID"];
        } else {
            $arFields = [
                "IBLOCK_ID"      => $iblockId,
                "NAME"           => $name,
                "CODE"           => $code,
                "SORT"           => 500,
                "IBLOCK_SECTION_ID" => $parentId,
                "ACTIVE"         => "Y",
            ];
            $sectionId = $secObj->Add($arFields);
            if(!$sectionId) {
                trigger_error("Ошибка создания раздела $name: ". $secObj->LAST_ERROR, E_USER_WARNING);
                continue;
            }
        }

        
        if(isset($node->items->item)) {
            importSections($node->items->item, $sectionId);
        }
    }
}

use Bitrix\Main\Web\HttpClient;


$docDir = $_SERVER['DOCUMENT_ROOT'].'/upload/doc/';
if (!is_dir($docDir)) {
    mkdir($docDir, 0755, true);
}

/**
 * Функция для сбора всех цен из XML и определения минимальных цен для каждого товара
 * @return array Массив с минимальными ценами по названиям товаров
 */
function collectMinPrices() {
    global $xml;
    
    $pricesData = [];
    $productIdPrices = []; // Для хранения цен по ID товара из XML
    
    foreach($xml->categories->category as $cat) {
        foreach($cat->items->item as $sub) {
            foreach($sub->products->product as $p) {
                $productId = trim((string)$p->id); // ID товара из XML
                
                if (isset($p->prices) && isset($p->prices->price)) {
                    foreach($p->prices->price as $priceItem) {
                        $productName = trim((string)$priceItem->name);
                        $price = (float)$priceItem->price;
                        $izdCode = trim((string)$priceItem->izd_code);
                        
                        if (empty($productName) || $price <= 0) {
                            continue;
                        }
                        
                        if (!isset($pricesData[$productName])) {
                            $pricesData[$productName] = [];
                        }
                        
                        $pricesData[$productName][] = [
                            'price' => $price,
                            'izd_code' => $izdCode
                        ];
                        
                        // Сохраняем цену по ID товара (для сопоставления с CML2_ARTICLE)
                        if (!empty($productId)) {
                            if (!isset($productIdPrices[$productId])) {
                                $productIdPrices[$productId] = [];
                            }
                            $productIdPrices[$productId][] = $price;
                        }
                    }
                }
            }
        }
    }
    
    // Определяем минимальные цены для каждого товара
    $minPrices = [];
    $minProductIdPrices = []; // Минимальные цены по ID товара
    
    foreach($pricesData as $productName => $prices) {
        $minPrice = null;
        foreach($prices as $priceData) {
            if ($minPrice === null || $priceData['price'] < $minPrice) {
                $minPrice = $priceData['price'];
            }
        }
        
        if ($minPrice !== null) {
            $minPrices[$productName] = number_format($minPrice, 2, '.', '');
        }
    }
    
    // Вычисляем минимальные цены по ID товара
    foreach($productIdPrices as $productId => $prices) {
        $minPrice = min($prices);
        $minProductIdPrices[$productId] = number_format($minPrice, 2, '.', '');
    }
    
    // Возвращаем два массива: минимальные цены по названию и по ID товара
    return [
        'byName' => $minPrices,
        'byProductId' => $minProductIdPrices
    ];
}

/**
 * Функция для проверки соединения с БД и переподключения при необходимости
 */
function checkDBConnection() {
    global $DB;
    
    try {
        // Проверяем соединение путем выполнения простого запроса
        $DB->Query("SELECT 1");
    } catch (\Exception $e) {
        // Если возникла ошибка, пробуем переподключиться
        try {
            $DB->Disconnect();
            $connected = $DB->Connect(
                $DB->DBHost, 
                $DB->DBName, 
                $DB->DBLogin, 
                $DB->DBPassword
            );
            
            if (!$connected) {
                echo "Ошибка соединения с базой данных. Попробуйте запустить скрипт заново.<br>";
                return false;
            }
        } catch (\Exception $e) {
            echo "Ошибка переподключения к БД: " . $e->getMessage() . "<br>";
            return false;
        }
    }
    
    return true;
}

function downloadFile($url, $description = '') {
    global $docDir;
    
    
    $fileName = basename($url);
    $localPath = $docDir . $fileName;
    
    
    if (file_exists($localPath)) {
        
        error_log("Файл уже существует: {$fileName}, используем локальную копию");
        
        
        $fileArray = CFile::MakeFileArray($localPath);
        $fileArray['MODULE_ID'] = 'iblock';
        
        if (!empty($description)) {
            $fileArray['description'] = $description;
        } else {
            $fileArray['description'] = $fileName;
        }
        
        return $fileArray;
    }
    
    
    $http = new HttpClient([
        'socketTimeout' => 600,    
        'streamTimeout' => 1800,   
        'disableSslVerification' => true, 
        'redirect' => true,        
        'redirectMax' => 5,         
        'waitResponse' => true    
    ]);
    
    
    try {
        $success = $http->download($url, $localPath);
        if ($success) {
            
            $fileArray = CFile::MakeFileArray($localPath);
            $fileArray['MODULE_ID'] = 'iblock';
            
            
            
            if (!empty($description)) {
                $fileArray['description'] = $description; 
            } else {
                $fileArray['description'] = $fileName; 
            }
            
            return $fileArray;
        } else {
            $errorMessage = "Не удалось скачать файл: {$url}, ошибка: " . $http->getError();
            trigger_error($errorMessage, E_USER_WARNING);
            error_log($errorMessage);
            return false;
        }
    } catch (\Exception $e) {
        $errorMessage = "Исключение при загрузке файла {$url}: " . $e->getMessage();
        trigger_error($errorMessage, E_USER_WARNING);
        error_log($errorMessage);
        return false;
    }
}

/**
 * Функция обрабатывает документы и сертификаты товара
 * @param SimpleXMLElement $product Товар из XML
 * @return array Массив с двумя элементами: [документы, сертификаты]
 */
function collectProductDocs($product) {
    global $docDir;
    $docsArray = [];
    $certsArray = [];
    
    echo "<hr>Проверка документов для товара: {$product->name}<br>";
    
    if(isset($product->docs)) {
        echo "Секция docs существует.<br>";
        
        // Используем count() для SimpleXML более безопасным способом
        $docsCount = count($product->docs->children());
        echo "Количество дочерних элементов в docs: {$docsCount}<br>";
        
        // Отладка - выводим все дочерние элементы
        foreach($product->docs->children() as $childName => $child) {
            echo "Найден дочерний элемент: {$childName}<br>";
        }
        
        // Перебираем все группы документов
        foreach($product->docs->doc as $docGroup) {
            echo "Обработка группы документов: " . (string)$docGroup->name . "<br>";
            $groupName = (string)$docGroup->name;
            
            // Перебираем документы в группе
            if(isset($docGroup->items)) {
                $itemsCount = count($docGroup->items->children());
                echo "Количество элементов items: {$itemsCount}<br>";
                
                foreach($docGroup->items->item as $docItem) {
                    $docName = (string)$docItem->name;
                    $docLink = (string)$docItem->link;
                    
                    // Скачиваем файл и готовим его для загрузки в Битрикс
                    echo "Загрузка файла: {$docLink}<br>";
                    $fileArray = downloadFile($docLink, $docName);
                    
                    // Если файл успешно загружен, добавляем его в соответствующий массив
                    if ($fileArray) {
                        // Распределяем по типам в зависимости от группы
                        if(mb_strtolower($groupName) === 'документация') {
                            $docsArray[] = $fileArray;
                        } elseif(mb_strtolower($groupName) === 'сертификаты') {
                            $certsArray[] = $fileArray;
                        }
                        // Пропускаем обработку ПО по запросу пользователя
                    }
                }
            }
        }
    }
    
    // Выводим информацию о количестве найденных файлов
    if(!empty($docsArray)) {
        echo "Добавлено " . count($docsArray) . " документов в свойство DOCS<br>";
        echo "<pre>";
        print_r($docsArray);
        echo "</pre>";
    } else {
        echo "Документы не найдены<br>";
    }
    
    if(!empty($certsArray)) {
        echo "Добавлено " . count($certsArray) . " сертификатов в свойство SERT<br>";
        echo "<pre>";
        print_r($certsArray);
        echo "</pre>";
    } else {
        echo "Сертификаты не найдены<br>";
    }
    
    return [$docsArray, $certsArray];
}

function importProducts() {
    
    ini_set('default_socket_timeout', 0); 
    
    global $xml, $iblockId, $docDir;
    $el = new CIBlockElement;
    
    // Собираем минимальные цены товаров
    echo "<h3>Этап 1: Сбор цен из XML-файла</h3>";
    $priceData = collectMinPrices();
    $minPrices = $priceData['byName'];
    $minProductIdPrices = $priceData['byProductId'];
    
    $count = count($minPrices);
    $countById = count($minProductIdPrices);
    echo "Собрано минимальных цен по названиям: {$count}<br>";
    echo "Собрано минимальных цен по ID товаров: {$countById}<br>";
    
    $i = 0;
    foreach ($minPrices as $productName => $price) {
        echo "Товар: {$productName} - Мин. цена: {$price} руб.<br>";
        if (++$i >= 10) {
            echo "... и еще " . ($count - 10) . " товаров<br>";
            break;
        }
    }
    
    $processedCount = 0;
    $pricesUpdated = 0;
    $totalProducts = 0;
    
    // Считаем общее количество товаров
    foreach($xml->categories->category as $cat) {
        foreach($cat->items->item as $sub) {
            foreach($sub->products->product as $p) {
                $totalProducts++;
            }
        }
    }
    
    echo "<h3>Этап 2: Обработка товаров и обновление цен</h3>";
    echo "Всего товаров для обработки: {$totalProducts}<br><hr>";
    
    // Создаем массивы для поиска цен по разным критериям
    $pricesByName = $minPrices;       // Поиск по названию товара
    $pricesByArticle = $minProductIdPrices;  // Массив цен по ID товара из XML для сопоставления с CML2_ARTICLE
    $pricesByCode = [];               // Поиск по коду изделия
    
    // Собираем дополнительно коды изделий из блока цен
    foreach($xml->categories->category as $cat) {
        foreach($cat->items->item as $sub) {
            foreach($sub->products->product as $p) {
                if (isset($p->prices) && isset($p->prices->price)) {
                    foreach($p->prices->price as $priceItem) {
                        $izdCode = trim((string)$priceItem->izd_code);
                        $price = (float)$priceItem->price;
                        
                        // Сохраняем цены по коду изделия, если есть
                        if (!empty($izdCode) && $price > 0) {
                            if (!isset($pricesByCode[$izdCode]) || $price < (float)$pricesByCode[$izdCode]) {
                                $pricesByCode[$izdCode] = number_format($price, 2, '.', '');
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Используем результаты функции collectMinPrices как основной источник
    foreach($minPrices as $productName => $price) {
        $pricesByArticle[$productName] = $price;
    }
    
    echo "Подготовлено для сопоставления:<br>";
    echo "- По названиям товаров: " . count($pricesByName) . "<br>";
    echo "- По кодам изделий: " . count($pricesByCode) . "<br>";
    echo "- По ID товаров из XML: " . count($pricesByArticle) . "<br>";
    echo "- Всего уникальных цен: " . count($minPrices) . "<br><br>";
    

    foreach($xml->categories->category as $cat) {
        foreach($cat->items->item as $sub) {
            // 1) Находим ID секции
            $sectionCode = (string)$sub->id;
            $dbS = CIBlockSection::GetList(
                [], 
                ["IBLOCK_ID" => $iblockId, "CODE" => $sectionCode], 
                false, 
                ["ID"]
            );
            if(!$sec = $dbS->Fetch()) {
                continue; 
            }
            $sectionId = $sec["ID"];

            
            foreach($sub->products->product as $p) {
                $processedCount++;
                
                $xmlId      = (string)$p->id;
                $name       = (string)$p->name;
                $detailText = (string)$p->desc;  
                $specificText = trim((string)$p->specs); 
                
                // Добавляем артикул из id в свойство CML2_ARTICLE загрузка инфы об артикулах
                $article = $xmlId; // Используем значение из тега id как артикул
                
                echo "<hr><strong>Товар #{$processedCount}/{$totalProducts}: {$name}</strong> (Артикул: {$xmlId})<br>";
                
                // Проверяем наличие блока цен у данного товара
                $hasOwnPrices = isset($p->prices) && isset($p->prices->price);
                if ($hasOwnPrices) {
                    $ownPricesCount = count($p->prices->price);
                    echo "У товара есть собственный блок цен ({$ownPricesCount} позиций)<br>";
                }
                
                $imgUrl     = (string)$p->image;

                
                $fileArray = \CFile::MakeFileArray($imgUrl);
                $fileArray["MODULE_ID"] = "iblock";

                $propertyValues = [];
                
                // Добавляем артикул в свойства
                $propertyValues['CML2_ARTICLE'] = $article;

                // ВНИМАНИЕ: Блок обработки документов и сертификатов закомментирован
                // для предотвращения дублирования файлов при повторном запуске импорта
                /*
                // Обрабатываем документы и сертификаты
                list($docsArray, $certsArray) = collectProductDocs($p);
                
                // Проверяем соединение с БД перед работой с файлами
                checkDBConnection();
                
                // Добавляем свойства в массив, формируем правильный формат для множественных свойств типа "файл"
                if(!empty($docsArray)) {
                    // Для множественных свойств типа файл, нужно передавать значения в специальном формате
                    $docsValues = [];
                    foreach($docsArray as $fileArray) {
                        $fileId = CFile::SaveFile($fileArray, "iblock");
                        if ($fileId) {
                            $docsValues[] = $fileId;
                            echo "Файл сохранен с ID: {$fileId}<br>";
                        }
                    }
                    
                    if (!empty($docsValues)) {
                        $propertyValues['DOCS'] = $docsValues;
                        echo "<hr>Свойство DOCS подготовлено к сохранению: " . count($docsValues) . " документов<br>";
                        echo "<pre>";
                        var_dump($docsValues);
                        echo "</pre>";
                    }
                }
                
                if(!empty($certsArray)) {
                    // Для множественных свойств типа файл, нужно передавать значения в специальном формате
                    $certValues = [];
                    foreach($certsArray as $fileArray) {
                        $fileId = CFile::SaveFile($fileArray, "iblock");
                        if ($fileId) {
                            $certValues[] = $fileId;
                            echo "Сертификат сохранен с ID: {$fileId}<br>";
                        }
                    }
                    
                    if (!empty($certValues)) {
                        $propertyValues['SERT'] = $certValues;
                        echo "<hr>Свойство SERT подготовлено к сохранению: " . count($certValues) . " сертификатов<br>";
                        echo "<pre>";
                        var_dump($certValues);
                        echo "</pre>";
                    }
                }
                */

                // 4.1) Технические характеристики (HTML-свойство)
                if ($specificText !== '') {
                    // Проверяем, содержит ли текст HTML-теги
                    if (strip_tags($specificText) !== $specificText) {
                        // Если содержит HTML-теги, сохраняем как HTML
                        $propertyValues['SPECIFICATIONS_TEXT'] = [
                            'VALUE' => [
                                'TEXT' => $specificText,
                                'TYPE' => 'HTML',
                            ],
                        ];
                    } else {
                        // Если это просто текст, то оборачиваем его в параграфы для форматирования
                        $formattedText = '<p>' . str_replace("\n", '</p><p>', $specificText) . '</p>';
                        $formattedText = str_replace('<p></p>', '', $formattedText);
                        
                        $propertyValues['SPECIFICATIONS_TEXT'] = [
                            'VALUE' => [
                                'TEXT' => $formattedText,
                                'TYPE' => 'HTML',
                            ],
                        ];
                    }
                }
                
                // Переменная для хранения CML2_ARTICLE товара
                $productArticle = "";
                
                // Поиск цены для данного товара (по приоритету)
                $productPrice = null;
                $priceSource = '';
                
                // 1. Поиск по CML2_ARTICLE (совпадение с ID товара из XML) - высший приоритет
                if (!empty($productArticle) && isset($pricesByArticle[$productArticle])) {
                    $productPrice = $pricesByArticle[$productArticle];
                    $priceSource = "по свойству CML2_ARTICLE = '{$productArticle}'";
                }
                // 2. Поиск по точному названию товара
                elseif (isset($pricesByName[$name])) {
                    $productPrice = $pricesByName[$name];
                    $priceSource = "по точному названию '{$name}'";
                }
                // 3. Поиск по xmlId (артикулу в XML)
                elseif (isset($pricesByArticle[$xmlId])) {
                    $productPrice = $pricesByArticle[$xmlId];
                    $priceSource = "по XML ID товара '{$xmlId}'";
                }
                // 4. Поиск по коду изделия
                elseif (isset($pricesByCode[$xmlId])) {
                    $productPrice = $pricesByCode[$xmlId];
                    $priceSource = "по коду изделия '{$xmlId}'";
                }
                // 5. Поиск по частичному совпадению CML2_ARTICLE с кодами товаров
                elseif (!empty($productArticle)) {
                    foreach($pricesByArticle as $articleId => $price) {
                        if (stripos($productArticle, $articleId) !== false || stripos($articleId, $productArticle) !== false) {
                            $productPrice = $price;
                            $priceSource = "по частичному совпадению CML2_ARTICLE '{$productArticle}' с ID товара '{$articleId}'";
                            break;
                        }
                    }
                }
                // 6. Поиск по частичному совпадению названия
                if ($productPrice === null) {
                    foreach($pricesByName as $priceName => $price) {
                        // Проверяем, содержится ли артикул в названии цены
                        if (stripos($priceName, $xmlId) !== false && strlen($xmlId) > 2) {
                            $productPrice = $price;
                            $priceSource = "по частичному совпадению артикула '{$priceName}' с '{$xmlId}'";
                            break;
                        }
                        // Проверяем частичное совпадение названий (минимум 3 символа)
                        elseif (strlen($name) > 3 && (stripos($priceName, $name) !== false || stripos($name, $priceName) !== false)) {
                            $productPrice = $price;
                            $priceSource = "по частичному совпадению названия '{$priceName}' с '{$name}'";
                            break;
                        }
                    }
                }
                
                // 7. Поиск по кодам изделий (частичное совпадение)
                if ($productPrice === null) {
                    foreach($pricesByCode as $izdCode => $price) {
                        if (stripos($xmlId, $izdCode) !== false || stripos($izdCode, $xmlId) !== false) {
                            $productPrice = $price;
                            $priceSource = "по частичному совпадению кода изделия '{$izdCode}' с '{$xmlId}'";
                            break;
                        }
                    }
                }
                
                // Выводим результат поиска
                if ($productPrice !== null) {
                    echo "Найдена цена {$priceSource}: <strong>{$productPrice} руб.</strong><br>";
                }
                
                // Если цена найдена, добавляем её в свойства
                if ($productPrice !== null) {
                    $propertyValues['PRICE'] = $productPrice;
                    $pricesUpdated++;
                    echo "<strong>Цена обновлена: {$productPrice} руб.</strong><br>";
                } else {
                    echo "Цена для товара '{$name}' (артикул: '{$xmlId}') не найдена<br>";
                }
                
                // Объединяем все свойства в один массив
                $allProperties = $propertyValues;
                
                // Проверяем наличие свойств перед формированием загрузки
                if (!empty($allProperties)) {
                    echo "<hr>Всего свойств для загрузки: " . count($allProperties) . "<br>";
                    /*
                    // Закомментировано для предотвращения дублирования файлов
                    if (isset($allProperties['DOCS'])) {
                        echo "Документы присутствуют: " . count($allProperties['DOCS']) . "<br>";
                    }
                    if (isset($allProperties['SERT'])) {
                        echo "Сертификаты присутствуют: " . count($allProperties['SERT']) . "<br>";
                    }
                    */
                    if (isset($allProperties['PRICE'])) {
                        echo "Цена: " . $allProperties['PRICE'] . " руб.<br>";
                    }
                }
                
                $arLoad = [
                    "IBLOCK_ID"         => $iblockId,
                    "XML_ID"            => $xmlId,
                    "NAME"              => $name,
                    "CODE"              => $xmlId,
                    "ACTIVE"            => "Y",
                    "IBLOCK_SECTION_ID" => $sectionId,
                    "DETAIL_TEXT"       => $detailText,
                    "PREVIEW_PICTURE"   => $fileArray,
                    "DETAIL_PICTURE"    => $fileArray,
                    "PROPERTY_VALUES"   => $allProperties, 
                ];

                $resE = CIBlockElement::GetList(
                    [], 
                    ["IBLOCK_ID" => $iblockId, "XML_ID" => $xmlId], 
                    false, 
                    false, 
                    ["ID"]
                )->Fetch();

                if($resE) {
                    $current = CIBlockElement::GetByID($resE["ID"])->GetNext();
                    if ($current && $current["DETAIL_PICTURE"]) {
                        $existingFile = CFile::GetByID($current["DETAIL_PICTURE"])->Fetch();
                        
                        if ($existingFile["ORIGINAL_NAME"] === basename($imgUrl)) {
                            unset($arLoad["PREVIEW_PICTURE"], $arLoad["DETAIL_PICTURE"]);
                        }
                    }
                    
                    // Получаем свойство CML2_ARTICLE из Битрикс
                    $dbProps = CIBlockElement::GetProperty(
                        $iblockId, 
                        $resE["ID"], 
                        [], 
                        ["CODE" => "CML2_ARTICLE"]
                    );
                    if ($prop = $dbProps->Fetch()) {
                        $productArticle = trim($prop["VALUE"]);
                        echo "CML2_ARTICLE товара: {$productArticle}<br>";
                    }
                    
                   
                    if(!$el->Update($resE["ID"], $arLoad)) {
                        trigger_error(
                            "Ошибка обновления товара {$name}: ".$el->LAST_ERROR,
                            E_USER_WARNING
                        );
                    } else {
                        
                        // Дополнительно установим свойства напрямую, чтобы убедиться, что они сохранены
                        if (!empty($allProperties)) {
                            echo "<hr>Явно устанавливаем свойства для товара ID: {$resE["ID"]}<br>";
                            
                            // Проверяем соединение с БД перед операциями с файлами
                            checkDBConnection();
                            
                            // Устанавливаем свойства напрямую
                            CIBlockElement::SetPropertyValuesEx($resE["ID"], $iblockId, $allProperties);
                            
                            // Обновляем цену товара в торговом каталоге
                            if (isset($productPrice) && $productPrice > 0) {
                                // Проверяем, что товар добавлен в торговый каталог
                                if (!CCatalogProduct::GetByID($resE["ID"])) {
                                    CCatalogProduct::Add(["ID" => $resE["ID"], "QUANTITY" => 100]);
                                    echo "<strong>Товар добавлен в торговый каталог</strong><br>";
                                }

                                $arFields = array(
                                   "PRODUCT_ID" => $resE["ID"],
                                   "CATALOG_GROUP_ID" => 1, // Базовый тип цены (может потребоваться изменение)
                                   "PRICE" => $productPrice,
                                   "CURRENCY" => "RUB",
                                );
                                
                                // Проверяем, есть ли уже цена для этого товара
                                $resPrice = CPrice::GetList(
                                    array(),
                                    array(
                                        "PRODUCT_ID" => $resE["ID"],
                                        "CATALOG_GROUP_ID" => 1
                                    )
                                );
                                
                                if ($arr = $resPrice->Fetch()) {
                                    // Если цена уже есть, обновляем её
                                    $result = CPrice::Update($arr["ID"], $arFields);
                                    echo "<strong>Цена в каталоге обновлена: {$productPrice} руб.</strong><br>";
                                } else {
                                    // Если цены нет, добавляем новую
                                    $result = CPrice::Add($arFields);
                                    echo "<strong>Новая цена в каталоге добавлена: {$productPrice} руб.</strong><br>";
                                }
                                
                                if (!$result) {
                                    echo "Ошибка при обновлении цены товара.<br>";
                                }
                            }
                            
                            /*
                            // Закомментировано для предотвращения дублирования файлов
                            // Проверяем, сохранились ли документы
                            echo "<hr>Проверка сохранения документов:<br>";
                            $dbProps = CIBlockElement::GetProperty($iblockId, $resE["ID"], [], ["CODE" => "DOCS"]);
                            $docsCount = 0;
                            while($prop = $dbProps->Fetch()) {
                                if($prop["VALUE"]) {
                                    $docsCount++;
                                    $fileInfo = CFile::GetByID($prop["VALUE"])->Fetch();
                                    if($fileInfo) {
                                        echo "Документ {$docsCount}: ID {$prop["VALUE"]}, Имя: {$fileInfo['ORIGINAL_NAME']}, Описание: {$prop["DESCRIPTION"]}<br>";
                                    } else {
                                        echo "Документ {$docsCount}: ID {$prop["VALUE"]} - информация о файле не найдена<br>";
                                    }
                                }
                            }
                            
                            if ($docsCount === 0) {
                                echo "Документы не найдены в свойствах товара<br>";
                            }
                            
                            // Проверяем, сохранились ли сертификаты
                            echo "<hr>Проверка сохранения сертификатов:<br>";
                            $dbProps = CIBlockElement::GetProperty($iblockId, $resE["ID"], [], ["CODE" => "SERT"]);
                            $certsCount = 0;
                            while($prop = $dbProps->Fetch()) {
                                if($prop["VALUE"]) {
                                    $certsCount++;
                                    $fileInfo = CFile::GetByID($prop["VALUE"])->Fetch();
                                    if($fileInfo) {
                                        echo "Сертификат {$certsCount}: ID {$prop["VALUE"]}, Имя: {$fileInfo['ORIGINAL_NAME']}, Описание: {$prop["DESCRIPTION"]}<br>";
                                    } else {
                                        echo "Сертификат {$certsCount}: ID {$prop["VALUE"]} - информация о файле не найдена<br>";
                                    }
                                }
                            }
                            
                            if ($certsCount === 0) {
                                echo "Сертификаты не найдены в свойствах товара<br>";
                            }
                            */
                            
                            // Проверяем сохранение свойства SPECIFICATIONS_TEXT
                            $dbSpecsProps = CIBlockElement::GetProperty($iblockId, $resE["ID"], [], ["CODE" => "SPECIFICATIONS_TEXT"]);
                            $hasSpecText = false;
                            while($specProp = $dbSpecsProps->Fetch()) {
                                if (!empty($specProp["VALUE"])) {
                                    $hasSpecText = true;
                                }
                            }
                        }
                    }
                } else {
                   
                    $newElementId = $el->Add($arLoad);
                    if(!$newElementId) {
                        trigger_error(
                            "Ошибка добавления товара {$name}: ".$el->LAST_ERROR,
                            E_USER_WARNING
                        );
                    } else {
                        
                        // Если есть свойства для нового элемента - устанавливаем их напрямую
                        if (!empty($allProperties)) {
                            echo "<hr>Явно устанавливаем свойства для нового товара ID: {$newElementId}<br>";
                            
                            // Проверяем соединение с БД перед операциями с файлами
                            checkDBConnection();
                            
                            // Устанавливаем свойства напрямую
                            CIBlockElement::SetPropertyValuesEx($newElementId, $iblockId, $allProperties);
                            
                            /*
                            // Закомментировано для предотвращения дублирования файлов
                            // Проверяем, сохранились ли документы
                            echo "<hr>Проверка сохранения документов для нового товара:<br>";
                            $dbProps = CIBlockElement::GetProperty($iblockId, $newElementId, [], ["CODE" => "DOCS"]);
                            $docsCount = 0;
                            while($prop = $dbProps->Fetch()) {
                                if($prop["VALUE"]) {
                                    $docsCount++;
                                    $fileInfo = CFile::GetByID($prop["VALUE"])->Fetch();
                                    if($fileInfo) {
                                        echo "Документ {$docsCount}: ID {$prop["VALUE"]}, Имя: {$fileInfo['ORIGINAL_NAME']}, Описание: {$prop["DESCRIPTION"]}<br>";
                                    } else {
                                        echo "Документ {$docsCount}: ID {$prop["VALUE"]} - информация о файле не найдена<br>";
                                    }
                                }
                            }
                            
                            if ($docsCount === 0) {
                                echo "Документы не найдены в свойствах нового товара<br>";
                            }
                            
                            // Проверяем, сохранились ли сертификаты
                            echo "<hr>Проверка сохранения сертификатов для нового товара:<br>";
                            $dbProps = CIBlockElement::GetProperty($iblockId, $newElementId, [], ["CODE" => "SERT"]);
                            $certsCount = 0;
                            while($prop = $dbProps->Fetch()) {
                                if($prop["VALUE"]) {
                                    $certsCount++;
                                    $fileInfo = CFile::GetByID($prop["VALUE"])->Fetch();
                                    if($fileInfo) {
                                        echo "Сертификат {$certsCount}: ID {$prop["VALUE"]}, Имя: {$fileInfo['ORIGINAL_NAME']}, Описание: {$prop["DESCRIPTION"]}<br>";
                                    } else {
                                        echo "Сертификат {$certsCount}: ID {$prop["VALUE"]} - информация о файле не найдена<br>";
                                    }
                                }
                            }
                            
                            if ($certsCount === 0) {
                                echo "Сертификаты не найдены в свойствах нового товара<br>";
                            }
                            */
                        }
                    }
                }
            }
        }
    }
    
    // Выводим итоговую статистику
    echo "<strong>Статистика по импорту:</strong><br>";
    echo "Обработано товаров: {$processedCount} из {$totalProducts}<br>";
    echo "Обновлено цен: {$pricesUpdated}<br>";
    
    // Выводим информацию о сопоставлении цен и статистику
    echo "<h3>Информация о ценах:</h3>";
    echo "- Общее количество товаров в Битрикс: {$totalProducts}<br>";
    echo "- Минимальные цены по названиям: " . count($pricesByName) . "<br>";
    echo "- Минимальные цены по ID товаров (для CML2_ARTICLE): " . count($pricesByArticle) . "<br>";
    echo "- Обновлено цен в торговом каталоге: {$pricesUpdated}<br>";
    echo "<hr>";
    echo "Процент товаров с обновленными ценами: " . round(($pricesUpdated / $totalProducts) * 100, 2) . "%<br>";
    echo "<hr>";
}


// 4. Запускаем
importSections($xml->categories->category);
importProducts();

echo "Импорт завершён.";