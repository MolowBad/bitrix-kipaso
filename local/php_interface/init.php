<?php
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/debug_init.log', date('c')." init start\n", FILE_APPEND);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Page\Asset;

// В init.php не ставим жёсткую проверку B_PROLOG_INCLUDED —
// в некоторых сценариях админской части или спец. скриптов она может быть не определена к моменту подключения

Loader::includeModule('sale');
Loader::includeModule('iblock');

const PT_FL = 1; // ID типа плательщика: ФЛ
const PT_YL = 2; // ID типа плательщика: ЮЛ

// Часто встречающиеся коды/имена свойств
const PROP_CODES_INN = ['INN','ИНН','INN_ORG','UF_INN','COMPANY_INN'];
const PROP_CODES_KPP = ['KPP','КПП','UF_KPP','COMPANY_KPP'];
const PROP_CODES_ORG = ['COMPANY','COMPANY_NAME','ORG_NAME','COMPANY_TITLE','COMPANY_FULL_NAME','НАЗВАНИЕ_ОРГАНИЗАЦИИ'];
const PROP_CODES_EMAIL = ['EMAIL','E-MAIL','E_MAIL','MAIL','ПОЧТА','ЭЛЕКТРОННАЯ ПОЧТА'];
const PROP_CODES_PHONE = ['PHONE','TEL','TELEPHONE','ТЕЛЕФОН','НОМЕР ТЕЛЕФОНА'];
const PROP_CODES_USER_FIO = ['FIO','ФИО','Ф.И.О.'];
const PROP_CODES_CONTACT_FIO = ['CONTACT_PERSON','CONTACT_FIO','CONTACT','КОНТАКТНОЕ ЛИЦО','ФИО'];
const PROP_CODES_CONTACT_POSITION = ['POSITION','ДОЛЖНОСТЬ'];
// Адресные свойства заказа
const PROP_CODES_CITY = ['CITY','ГОРОД'];
const PROP_CODES_STREET = ['STREET','УЛИЦА'];
const PROP_CODES_HOUSE = ['HOUSE','ДОМ'];
const PROP_CODES_KORPUS = ['KORPUS','КОРПУС'];
const PROP_CODES_BUILDING = ['BUILDING','СТРОЕНИЕ'];
const PROP_CODES_OFFICE = ['OFFICE','ОФИС','КВАРТИРА'];
const PROP_CODES_ZIP = ['ZIP','INDEX','ПОЧТОВЫЙ_ИНДЕКС','ПОЧТОВЫЙ ИНДЕКС'];
const PROP_CODES_COUNTRY = ['COUNTRY','СТРАНА'];

require_once __DIR__.'/lib/onec_exchange_handler.php';
require_once __DIR__.'/lib/assets_autoload.php';
require_once __DIR__.'/lib/cdek_address.php';

  try {
      \Bitrix\Main\Diag\Debug::writeToFile([
          'stage' => 'init_loaded',
          'time' => date('c'),
          'area' => (defined('ADMIN_SECTION') && ADMIN_SECTION === true) ? 'admin' : 'public',
      ], 'PAYER_TYPE_XML_PLUS', '/upload/payer_type_xml.log');
  } catch (\Throwable $e) {}
 


EventManager::getInstance()->addEventHandler('main', 'OnEndBufferContent', 'kipasoOnEndBufferContent', false, 1);
if (function_exists('AddEventHandler')) {
    AddEventHandler('main', 'OnEndBufferContent', 'kipasoOnEndBufferContent', 1);
}

// ----------------------------------------------------------------------------
// Подключение автозаполнения реквизитов по ИНН на странице заказа тестовые правки
// ----------------------------------------------------------------------------
try {
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($reqUri, '/personal/cart/order/') === 0) {
      
        Asset::getInstance()->addString('<script>window.EGRUL_AUTOFILL_CONFIG = {
            debug: false,
            innSelector:    "#soa-property-10, input[name=\"ORDER_PROP_10\"]",
            companyNameSelector: "#soa-property-8, input[name=\"ORDER_PROP_8\"]",
            legalAddressSelector: "#soa-property-9, textarea[name=\"ORDER_PROP_9\"]",
            kppSelector:    "#soa-property-11, input[name=\"ORDER_PROP_11\"]",
            contactPersonSelector: "#soa-property-12, input[name=\"ORDER_PROP_12\"]",
            postalCodeSelector: "input[name=\"ORDER_PROP_16\"], input[name*=\"INDEX\" i], input[name*=\"ZIP\" i]",
            endpoint: "/local/ajax/egrul_lookup.php",
            debounceMs: 500
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/EGRUL-INN/egrul-autofill.js');
    }
    // Подключение на странице редактирования профиля юр. лица
    if (strpos($reqUri, '/personal/profile/') === 0) {
        // Фиксируем точные селекторы для полей профиля ЮЛ (есть дублирующиеся ID, поэтому приоритет по name)
        Asset::getInstance()->addString('<script>window.EGRUL_AUTOFILL_CONFIG = {
            debug: false,
            // ИНН
            innSelector: "input[name=\"ORDER_PROP_10\"], #sppd-property-2",
            // Название компании
            companyNameSelector: "input[name=\"ORDER_PROP_8\"], #sppd-property-0",
            // Юридический адрес (textarea)
            legalAddressSelector: "textarea[name=\"ORDER_PROP_9\"], #sppd-property-1",
            // КПП
            kppSelector: "input[name=\"ORDER_PROP_11\"], #sppd-property-3",
            // Контактное лицо (ID дублируется, используем name)
            contactPersonSelector: "input[name=\"ORDER_PROP_12\"]",
            // Индекс
            postalCodeSelector: "input[name=\"ORDER_PROP_16\"]",
            endpoint: "/local/ajax/egrul_lookup.php",
            debounceMs: 500
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/EGRUL-INN/egrul-autofill.js');
    }
    // Подсказки адресов DaData на странице оформления заказа
    if (strpos($reqUri, '/personal/cart/order/') === 0) {
        Asset::getInstance()->addString('<script>window.ADDRESS_SUGGEST_CONFIG = {
            debug: false,
            endpoint: "/local/ajax/dadata_address.php",
            // Селекторы по умолчанию покрывают большинство шаблонов Bitrix, при необходимости можно уточнить
            citySelector: "#soa-property-17, input[name=\\"ORDER_PROP_17\\"]",
            streetSelector: "#soa-property-31, input[name=\\"ORDER_PROP_31\\"]",
            houseSelector: "#soa-property-32, input[name=\\"ORDER_PROP_32\\"]",
            count: 10,
            language: "ru",
            debounceMs: 250
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/DADATA-ADDRESS/address-suggest.js');
    }
    // Подсказки адресов DaData на странице профиля
    if (strpos($reqUri, '/personal/profile/') === 0) {
        Asset::getInstance()->addString('<script>window.ADDRESS_SUGGEST_CONFIG = {
            debug: false,
            endpoint: "/local/ajax/dadata_address.php",
            count: 10,
            language: "ru",
            debounceMs: 250
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/DADATA-ADDRESS/address-suggest.js');
    }
} catch (\Throwable $e) {
    // silent
}


// ОБРАБОТКА АДРЕСА СДЭК ПРИ ОФОРМЛЕНИИ ЗАКАЗА


/**
 * Парсит адрес пункта выдачи СДЭК на составные части
 * Формат: "Воронеж, ул. Ильюшина, 13 #SVRN129"
 * 
 * @param string $fullAddress Полный адрес из пункта выдачи
 * @return array Массив с разобранными частями адреса
 */
if (!function_exists('parseCdekAddress')) {
    function parseCdekAddress(string $fullAddress): array {
        $result = [
            'CITY' => '',
            'STREET' => '',
            'HOUSE' => '',
            'KORPUS' => '',
            'BUILDING' => '',
            'OFFICE' => '',
            'CODE' => ''
        ];
        
       
        if (preg_match('/#([A-Z0-9]+)$/u', $fullAddress, $matches)) {
            $result['CODE'] = $matches[1];
            $fullAddress = trim(preg_replace('/#[A-Z0-9]+$/u', '', $fullAddress));
        }
        
    
        $parts = array_map('trim', explode(',', $fullAddress));
        
        if (count($parts) >= 1) {
            
            $result['CITY'] = preg_replace('/^(г\.?|город)\s*/ui', '', $parts[0]);
        }
        
        if (count($parts) >= 2) {
           
            $result['STREET'] = preg_replace('/^(ул\.?|улица|пр\.?|проспект|пер\.?|переулок)\s*/ui', '', $parts[1]);
        }
        
        if (count($parts) >= 3) {
            
            $addressPart = $parts[2];
            
            
            if (preg_match('/(?:^|\bд\.?\s*|\bдом\s*)(\d+[\da-zA-Zа-яА-Я]*?(?:[\/\.\-]\d+[\da-zA-Zа-яА-Я]*)*)/ui', $addressPart, $matches)) {
                $result['HOUSE'] = $matches[1];
            }
            
            
            if (preg_match('/(?:к\.?|корп\.?|корпус)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['KORPUS'] = $matches[1];
            }
            
            
            if (preg_match('/(?:с\.?|стр\.?|строение)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['BUILDING'] = $matches[1];
            }
            
            
            if (preg_match('/(?:оф\.?|офис|кв\.?|квартира)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['OFFICE'] = $matches[1];
            }
        }
        
        
        if (count($parts) > 3) {
            for ($i = 3; $i < count($parts); $i++) {
                $part = $parts[$i];
                
                if (!$result['KORPUS'] && preg_match('/(?:к\.?|корп\.?|корпус)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $part, $matches)) {
                    $result['KORPUS'] = $matches[1];
                }
                
                if (!$result['BUILDING'] && preg_match('/(?:с\.?|стр\.?|строение)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $part, $matches)) {
                    $result['BUILDING'] = $matches[1];
                }
                
                if (!$result['OFFICE'] && preg_match('/(?:оф\.?|офис|кв\.?|квартира)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $part, $matches)) {
                    $result['OFFICE'] = $matches[1];
                }
            }
        }
        
        return $result;
    }
}

/**
 * Обработчик события обработки заказа компонентом
 * Автоматически разбивает адрес доставки СДЭК на отдельные поля
 * Срабатывает ДО сохранения заказа
 * 
 * @param \Bitrix\Sale\Order $order
 */
if (!function_exists('kipasoOnSaleComponentOrderProcess')) {
    function kipasoOnSaleComponentOrderProcess($order): void {
        try {
            if (!$order || !is_object($order)) {
                return;
            }
            
            $propertyCollection = $order->getPropertyCollection();
            
            if (!$propertyCollection) {
                return;
            }
            
       
            $addressProperty = null;
            $allProps = [];
            
            foreach ($propertyCollection as $property) {
                $code = (string)$property->getField('CODE');
                $name = (string)$property->getField('NAME');
                $value = trim((string)$property->getValue());
                
             
                $allProps[] = [
                    'CODE' => $code,
                    'NAME' => $name,
                    'VALUE' => mb_substr($value, 0, 100) 
                ];
                
               
                if (in_array(mb_strtoupper($code), ['ADDRESS', 'DELIVERY_ADDRESS', 'АДРЕС_ДОСТАВКИ', 'LOCATION', 'COMPANY_ADR'], true) ||
                    mb_stripos($name, 'Адрес доставки') !== false ||
                    (mb_stripos($name, 'адрес') !== false && mb_stripos($name, 'юридический') === false)) {
                    
                    
                    if (!empty($value) && preg_match('/#[A-Z0-9]+$/u', $value)) {
                        $addressProperty = $property;
                        break;
                    }
                }
            }
            
            if (!$addressProperty) {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                    'ERROR' => 'Address property with CDEK code not found',
                    'ALL_PROPS' => $allProps,
                ], 'CDEK_ADDRESS_PARSER', '/upload/cdek_parser.log');
                return;
            }
            
            $fullAddress = trim((string)$addressProperty->getValue());
            
            
            if (empty($fullAddress) || !preg_match('/#[A-Z0-9]+$/u', $fullAddress)) {
               
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                    'ERROR' => 'Address does not contain CDEK code',
                    'ADDRESS' => $fullAddress,
                ], 'CDEK_ADDRESS_PARSER', '/upload/cdek_parser.log');
                return;
            }
            
            
            $parsed = parseCdekAddress($fullAddress);
            
            // Вспомогательная функция для поиска свойства по кодам
            $getPropByCodes = function(array $codes) use ($propertyCollection) {
                $upper = array_map(fn($s) => mb_strtoupper($s), $codes);
                foreach ($propertyCollection as $p) {
                    $code = (string)$p->getField('CODE');
                    if ($code !== '' && in_array(mb_strtoupper($code), $upper, true)) {
                        return $p;
                    }
                }
                
                foreach ($propertyCollection as $p) {
                    $name = (string)$p->getField('NAME');
                    if ($name !== '' && in_array(mb_strtoupper($name), $upper, true)) {
                        return $p;
                    }
                }
                return null;
            };
            
            $updated = false;
            
            
            if ($parsed['CITY'] && ($cityProp = $getPropByCodes(PROP_CODES_CITY))) {
                $currentValue = trim((string)$cityProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $cityProp->setValue($parsed['CITY']);
                    $updated = true;
                }
            }
            
      
            if ($parsed['STREET'] && ($streetProp = $getPropByCodes(PROP_CODES_STREET))) {
                $currentValue = trim((string)$streetProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $streetProp->setValue($parsed['STREET']);
                    $updated = true;
                }
            }
            
           
            if ($parsed['HOUSE'] && ($houseProp = $getPropByCodes(PROP_CODES_HOUSE))) {
                $currentValue = trim((string)$houseProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $houseProp->setValue($parsed['HOUSE']);
                    $updated = true;
                }
            }
            
           
            if ($parsed['KORPUS'] && ($korpusProp = $getPropByCodes(PROP_CODES_KORPUS))) {
                $currentValue = trim((string)$korpusProp->getValue());
                if (empty($currentValue)) {
                    $korpusProp->setValue($parsed['KORPUS']);
                    $updated = true;
                }
            }
            
          
            if ($parsed['BUILDING'] && ($buildingProp = $getPropByCodes(PROP_CODES_BUILDING))) {
                $currentValue = trim((string)$buildingProp->getValue());
                if (empty($currentValue)) {
                    $buildingProp->setValue($parsed['BUILDING']);
                    $updated = true;
                }
            }
            
            
            if ($parsed['OFFICE'] && ($officeProp = $getPropByCodes(PROP_CODES_OFFICE))) {
                $currentValue = trim((string)$officeProp->getValue());
                if (empty($currentValue)) {
                    $officeProp->setValue($parsed['OFFICE']);
                    $updated = true;
                }
            }
            
            
            
           
            \Bitrix\Main\Diag\Debug::writeToFile([
                'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                'FULL_ADDRESS' => $fullAddress,
                'PARSED' => $parsed,
                'UPDATED' => $updated,
                'TIMESTAMP' => date('Y-m-d H:i:s'),
            ], 'CDEK_ADDRESS_PARSER', '/upload/cdek_parser.log');
            
        } catch (\Throwable $e) {
           
            \Bitrix\Main\Diag\Debug::writeToFile([
                'ERROR' => $e->getMessage(),
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine(),
                'TRACE' => $e->getTraceAsString(),
            ], 'CDEK_ADDRESS_PARSER_ERROR', '/upload/cdek_parser.log');
        }
    }
}

if (!function_exists('kipasoOnOrderNewSendEmail')) {
    function kipasoOnOrderNewSendEmail($orderId, &$eventName, &$arFields) {
        try {
            if (!Loader::includeModule('sale')) {
                return;
            }
            $orderId = (int)$orderId;
            if ($orderId <= 0) {
                return;
            }
            $order = \Bitrix\Sale\Order::load($orderId);
            if (!$order) {
                return;
            }
            $personTypeId = (int)$order->getPersonTypeId();
            $payerTypeText = ($personTypeId === PT_YL) ? 'Юридическое лицо' : 'Физическое лицо';
            $arFields['ORDER_PAYER_TYPE'] = $payerTypeText;
            
            $props = $order->getPropertyCollection();
            if (!$props) {
                return;
            }
            $getPropByCodes = function(array $codes) use ($props): string {
                $upper = array_map(static fn($s) => mb_strtoupper($s), $codes);
                foreach ($props as $p) {
                    $code = (string)$p->getField('CODE');
                    if ($code !== '' && in_array(mb_strtoupper($code), $upper, true)) {
                        return trim((string)$p->getValue());
                    }
                }
                foreach ($props as $p) {
                    $name = (string)$p->getField('NAME');
                    if ($name !== '' && in_array(mb_strtoupper($name), $upper, true)) {
                        return trim((string)$p->getValue());
                    }
                }
                return '';
            };

            $inn = $getPropByCodes(PROP_CODES_INN);
            $kpp = $getPropByCodes(PROP_CODES_KPP);
            $org = $getPropByCodes(PROP_CODES_ORG);
            $emailProp = $getPropByCodes(PROP_CODES_EMAIL);
            $phoneProp = $getPropByCodes(PROP_CODES_PHONE);
            $userFio = $getPropByCodes(PROP_CODES_USER_FIO);
            $contactFioProp = $getPropByCodes(PROP_CODES_CONTACT_FIO);
            $contactPosProp = $getPropByCodes(PROP_CODES_CONTACT_POSITION);
            $addrCity = $getPropByCodes(PROP_CODES_CITY);
            $addrStreet = $getPropByCodes(PROP_CODES_STREET);
            $addrHouse = $getPropByCodes(PROP_CODES_HOUSE);
            $addrKorpus = $getPropByCodes(PROP_CODES_KORPUS);
            $addrBuilding = $getPropByCodes(PROP_CODES_BUILDING);
            $addrOffice = $getPropByCodes(PROP_CODES_OFFICE);
            $addrZip = $getPropByCodes(PROP_CODES_ZIP);
            $addrCountry = $getPropByCodes(PROP_CODES_COUNTRY);

            $addressParts = array_filter([
                $addrZip ?: null,
                $addrCountry ?: null,
                $addrCity ?: null,
                $addrStreet ?: null,
                $addrHouse ?: null,
                $addrKorpus ?: null,
                $addrBuilding ?: null,
                $addrOffice ?: null,
            ]);
            $addressFull = implode(', ', $addressParts);

            if ($inn !== '') {
                $arFields['ORDER_INN'] = $inn;
            }
            if ($kpp !== '') {
                $arFields['ORDER_KPP'] = $kpp;
            }
            if ($org !== '') {
                $arFields['ORDER_ORG'] = $org;
            }
            if ($emailProp !== '') {
                $arFields['ORDER_EMAIL_PROP'] = $emailProp;
            }
            if ($phoneProp !== '') {
                $arFields['ORDER_PHONE'] = $phoneProp;
            }
            if ($userFio !== '') {
                $arFields['ORDER_USER'] = $userFio;
            }
            if ($contactFioProp !== '') {
                $arFields['ORDER_CONTACT_FIO'] = $contactFioProp;
            }
            if ($contactPosProp !== '') {
                $arFields['ORDER_CONTACT_POSITION'] = $contactPosProp;
            }
            if ($addrCity !== '') {
                $arFields['ORDER_CITY'] = $addrCity;
            }
            if ($addrStreet !== '') {
                $arFields['ORDER_STREET'] = $addrStreet;
            }
            if ($addrHouse !== '') {
                $arFields['ORDER_HOUSE'] = $addrHouse;
            }
            if ($addrKorpus !== '') {
                $arFields['ORDER_KORPUS'] = $addrKorpus;
            }
            if ($addrBuilding !== '') {
                $arFields['ORDER_BUILDING'] = $addrBuilding;
            }
            if ($addrOffice !== '') {
                $arFields['ORDER_OFFICE'] = $addrOffice;
            }
            if ($addrZip !== '') {
                $arFields['ORDER_ZIP'] = $addrZip;
            }
            if ($addrCountry !== '') {
                $arFields['ORDER_COUNTRY'] = $addrCountry;
            }
            if ($addressFull !== '') {
                $arFields['ORDER_ADDRESS_FULL'] = $addressFull;
            }
            $basket = $order->getBasket();
            if ($basket) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
                $baseUrl = $host !== '' ? $scheme.'://'.$host : '';

                $rowsHtml = '';

                foreach ($basket as $basketItem) {
                    if (method_exists($basketItem, 'isBundleChild') && $basketItem->isBundleChild()) {
                        continue;
                    }

                    $nameRaw = (string)$basketItem->getField('NAME');
                    $nameSafe = htmlspecialcharsbx($nameRaw);
                    $qty = (float)$basketItem->getQuantity();
                    $price = (float)$basketItem->getPrice();
                    $currency = (string)$basketItem->getCurrency();
                    $sum = (float)$basketItem->getFinalPrice();
                    if ($sum <= 0) {
                        $sum = $price * $qty;
                    }

                    $priceFormatted = '';
                    $sumFormatted = '';
                    if (class_exists('CCurrencyLang') && $currency !== '') {
                        $priceFormatted = (string)\CCurrencyLang::CurrencyFormat($price, $currency, true);
                        $sumFormatted = (string)\CCurrencyLang::CurrencyFormat($sum, $currency, true);
                    } else {
                        $priceFormatted = number_format($price, 0, '.', ' ');
                        $sumFormatted = number_format($sum, 0, '.', ' ');
                    }

                    $photoHtml = '';
                    $productId = (int)$basketItem->getField('PRODUCT_ID');
                    $detailUrl = '';
                    $picSrc = '';

                    if ($productId > 0 && Loader::includeModule('iblock')) {
                        $element = null;
                        $res = \CIBlockElement::GetList([], ['ID' => $productId], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL']);
                        if ($tmp = $res->GetNext()) {
                            $element = $tmp;
                        }

                        if ((!$element || (!$element['PREVIEW_PICTURE'] && !$element['DETAIL_PICTURE'])) && Loader::includeModule('catalog') && class_exists('CCatalogSku')) {
                            $parentInfo = \CCatalogSku::GetProductInfo($productId);
                            if (is_array($parentInfo) && (int)$parentInfo['ID'] > 0) {
                                $resParent = \CIBlockElement::GetList([], ['ID' => (int)$parentInfo['ID']], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL']);
                                if ($tmpParent = $resParent->GetNext()) {
                                    $element = $tmpParent;
                                }
                            }
                        }

                        if ($element) {
                            $picId = (int)($element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE']);
                            if ($picId) {
                                $src = (string)\CFile::GetPath($picId);
                                if ($src !== '') {
                                    if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
                                        $picSrc = $src;
                                    } elseif ($baseUrl !== '') {
                                        $picSrc = $baseUrl.$src;
                                    } else {
                                        $picSrc = $src;
                                    }
                                }
                            }
                            if (!empty($element['DETAIL_PAGE_URL'])) {
                                $detailUrl = (string)$element['DETAIL_PAGE_URL'];
                                if ($baseUrl !== '' && strpos($detailUrl, 'http://') !== 0 && strpos($detailUrl, 'https://') !== 0) {
                                    $detailUrl = $baseUrl.$detailUrl;
                                }
                            }
                        }
                    }

                    if ($picSrc !== '') {
                        $imgTag = '<img src="'.$picSrc.'" alt="'.$nameSafe.'" style="display:block;width:64px;height:64px;object-fit:contain;border-radius:4px;" />';
                        if ($detailUrl !== '') {
                            $photoHtml = '<a href="'.$detailUrl.'" style="text-decoration:none;">'.$imgTag.'</a>';
                        } else {
                            $photoHtml = $imgTag;
                        }
                    }

                    $nameHtml = $nameSafe;
                    if ($detailUrl !== '') {
                        $nameHtml = '<a href="'.$detailUrl.'" style="color:#111827;text-decoration:none;">'.$nameSafe.'</a>';
                    }

                    $rowsHtml .= '<tr>'
                        .'<td align="left" style="padding:8px 8px 8px 0;border-bottom:1px solid #e5e7eb;">'.$photoHtml.'</td>'
                        .'<td align="left" style="padding:8px 8px;border-bottom:1px solid #e5e7eb;font-size:15px;color:#111827;">'.$nameHtml.'</td>'
                        .'<td align="center" style="padding:8px 8px;border-bottom:1px solid #e5e7eb;font-size:15px;color:#111827;">'.htmlspecialcharsbx((string)$qty).'</td>'
                        .'<td align="right" style="padding:8px 8px;border-bottom:1px solid #e5e7eb;font-size:15px;color:#111827;">'.$priceFormatted.'</td>'
                        .'<td align="right" style="padding:8px 0 8px 8px;border-bottom:1px solid #e5e7eb;font-size:15px;color:#111827;">'.$sumFormatted.'</td>'
                        .'</tr>';
                }

                if ($rowsHtml !== '') {
                    $itemsHtml = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#111827;">'
                        .'<thead>'
                        .'<tr>'
                        .'<th align="left" style="padding:8px 8px 8px 0;border-bottom:1px solid #e5e7eb;width:72px;font-weight:500;font-size:13px;color:#6b7280;">Фото</th>'
                        .'<th align="left" style="padding:8px 8px;border-bottom:1px solid #e5e7eb;font-weight:500;font-size:13px;color:#6b7280;">Товар</th>'
                        .'<th align="center" style="padding:8px 8px;border-bottom:1px solid #e5e7eb;width:70px;font-weight:500;font-size:13px;color:#6b7280;">Кол-во</th>'
                        .'<th align="right" style="padding:8px 8px;border-bottom:1px solid #e5e7eb;width:90px;font-weight:500;font-size:13px;color:#6b7280;">Цена</th>'
                        .'<th align="right" style="padding:8px 0 8px 8px;border-bottom:1px solid #e5e7eb;width:100px;font-weight:500;font-size:13px;color:#6b7280;">Сумма</th>'
                        .'</tr>'
                        .'</thead>'
                        .'<tbody>'
                        .$rowsHtml
                        .'</tbody>'
                        .'</table>';

                    $arFields['ORDER_ITEMS_HTML'] = $itemsHtml;
                }
            }
            $paidText = $order->isPaid() ? 'Оплачен' : 'Не оплачен';
            $arFields['ORDER_PAID_TEXT'] = $paidText;
            $arFields['ORDER_STATUS_TEXT'] = 'Создан';

            $paySystemTitle = '';
            $paymentCollection = $order->getPaymentCollection();
            if ($paymentCollection) {
                $names = [];
                foreach ($paymentCollection as $payment) {
                    if (method_exists($payment, 'isInner') && $payment->isInner()) {
                        continue;
                    }
                    $name = '';
                    if (method_exists($payment, 'getPaymentSystemName')) {
                        $name = (string)$payment->getPaymentSystemName();
                    } else {
                        $name = (string)$payment->getField('PAY_SYSTEM_NAME');
                    }
                    $name = trim($name);
                    if ($name !== '') {
                        $names[] = $name;
                    }
                }
                if (!empty($names)) {
                    $paySystemTitle = implode(', ', array_unique($names));
                }
            }
            if ($paySystemTitle !== '') {
                $arFields['ORDER_PAY_SYSTEM'] = $paySystemTitle;
            }
        } catch (\Throwable $e) {
            try {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => $orderId,
                    'ERROR' => $e->getMessage(),
                    'FILE' => $e->getFile(),
                    'LINE' => $e->getLine(),
                ], 'ORDER_NEW_SEND_EMAIL', '/upload/order_email_ext.log');
            } catch (\Throwable $e2) {
            }
        }
    }
}

if (function_exists('AddEventHandler')) {
    AddEventHandler('sale', 'OnOrderNewSendEmail', 'kipasoOnOrderNewSendEmail');
    AddEventHandler('sale', 'OnOrderPaySendEmail', 'kipasoOnOrderNewSendEmail');
}

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleComponentOrderOneStepProcess',
    'kipasoOnSaleComponentOrderProcess'
);
