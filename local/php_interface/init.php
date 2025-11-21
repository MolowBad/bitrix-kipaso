<?php
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

try {
    \Bitrix\Main\Diag\Debug::writeToFile([
        'stage' => 'init_loaded',
        'time' => date('c'),
        'area' => (defined('ADMIN_SECTION') && ADMIN_SECTION === true) ? 'admin' : 'public',
    ], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
} catch (\Throwable $e) {}

if (!function_exists('kipasoOnEndBufferContent')) {
    function kipasoOnEndBufferContent(&$content): void {
    
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    $is1cScript = (stripos($uri, '/bitrix/admin/1c_exchange.php') !== false) || (stripos($script, '1c_exchange.php') !== false);
    if (!$is1cScript) {
        Debug::writeToFile(['stage' => 'skip_not_1c', 'uri' => $uri, 'script' => $script], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
        return;
    }
    if (($_GET['type'] ?? '') !== 'sale' || ($_GET['mode'] ?? '') !== 'query') {
        Debug::writeToFile(['stage' => 'skip_not_sale_query', 'get' => $_GET], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
        return;
    }
    @ini_set('display_errors', '0');

    
    Debug::writeToFile([
        'stage' => 'before_parse',
        'uri' => $uri,
        'script' => $script,
        'len' => strlen($content),
    ], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');

    // Разбираем XML, полагаясь на объявленную в документе кодировку
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $loaded = @$dom->loadXML($content);
    if (!$loaded) {
        
        $try = @mb_convert_encoding($content, 'UTF-8');
        $loaded = $try ? @$dom->loadXML($try) : false;
        if (!$loaded) {
            Debug::writeToFile(['error' => 'XML parse failed'], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
            return;
        }
    }

    $xp = new \DOMXPath($dom);

    foreach ($xp->query('//КоммерческаяИнформация/Документ') as $docNode) {
       
        $idNode = $xp->query('./Ид', $docNode)->item(0);
        if (!$idNode) { continue; }
        $orderId = (int)trim($idNode->nodeValue);
        if ($orderId <= 0) { continue; }

        $order = \Bitrix\Sale\Order::load($orderId);
        if (!$order) { continue; }

        $isYL = ((int)$order->getPersonTypeId() === PT_YL);
        $payerValue = $isYL ? 'Юридическое лицо' : 'Физическое лицо';

     
        $props = $order->getPropertyCollection();
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
        $contactFioProp = $getPropByCodes(PROP_CODES_CONTACT_FIO);
        $contactPosProp = $getPropByCodes(PROP_CODES_CONTACT_POSITION);
        // Адресные значения из свойств заказа
        $addrCity = $getPropByCodes(PROP_CODES_CITY);
        $addrStreet = $getPropByCodes(PROP_CODES_STREET);
        $addrHouse = $getPropByCodes(PROP_CODES_HOUSE);
        $addrKorpus = $getPropByCodes(PROP_CODES_KORPUS);
        $addrBuilding = $getPropByCodes(PROP_CODES_BUILDING);
        $addrOffice = $getPropByCodes(PROP_CODES_OFFICE);
        $addrZip = $getPropByCodes(PROP_CODES_ZIP);
        $addrCountry = $getPropByCodes(PROP_CODES_COUNTRY);

        
        $req = $xp->query('./ЗначенияРеквизитов', $docNode)->item(0);
        $reqMap = [];
        if ($req) {
            foreach ($xp->query('./ЗначениеРеквизита', $req) as $zr) {
                $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                $v = (string)$xp->query('./Значение', $zr)->item(0)?->nodeValue;
                if ($n !== '') { $reqMap[$n] = $v; }
            }
        }

        
        if (!isset($reqMap['Тип плательщика (для обмена)'])) {
            $reqMap['Тип плательщика (для обмена)'] = $payerValue;
        }

     
        $ensureChild = function(\DOMNode $parent, string $name, ?string $value = null) use ($dom, $xp): \DOMElement {
            $node = $xp->query('./'.$name, $parent)->item(0);
            if (!$node) { $node = $dom->createElement($name); $parent->appendChild($node); }
            if ($value !== null) { while ($node->firstChild) { $node->removeChild($node->firstChild); } $node->appendChild($dom->createTextNode($value)); }
            return $node;
        };

        // 1) Перенос документных реквизитов в одноименные теги по новой схеме
        $mapNewTags = [
            'Способ доставки' => 'СпособДоставки',
            'Метод доставки ИД' => 'МетодДоставкиИД',
            'Метод оплаты' => 'МетодОплаты',
            'Метод оплаты ИД' => 'МетодОплатыИД',
            'Заказ оплачен' => 'ЗаказОплачен',
            'Доставка разрешена' => 'ДоставкаРазрешена',
            'Отменен' => 'Отменен',
            'Финальный статус' => 'ФинальныйСтатус',
            'Статус заказа' => 'СтатусЗаказа',
            'Статуса заказа ИД' => 'СтатусЗаказаИД',
            'Дата изменения статуса' => 'ДатаИзмененияСтатуса',
            'Сайт' => 'Сайт',
        ];
        foreach ($mapNewTags as $old => $new) {
            if (isset($reqMap[$old])) { $ensureChild($docNode, $new, (string)$reqMap[$old]); }
        }

       
        $hasAddrParts = ($addrCity !== '' || $addrStreet !== '' || $addrHouse !== '' || $addrKorpus !== '' || $addrBuilding !== '' || $addrOffice !== '' || $addrZip !== '' || $addrCountry !== '');

        
        $addrText = $reqMap['Адрес доставки'] ?? '';
        if (($addrStreet === '' || $addrHouse === '' || $addrCity === '') && $addrText !== '' && preg_match('/#[A-Z0-9]+$/u', $addrText)) {
            $parsedPickup = parseCdekAddress($addrText);
            if ($parsedPickup['CITY'] !== '' && ($addrCity === '' || mb_strtolower($addrCity) === 'москва')) {
                $addrCity = $parsedPickup['CITY'];
            }
            if ($parsedPickup['STREET'] !== '' && $addrStreet === '') {
                $addrStreet = $parsedPickup['STREET'];
            }
            if ($parsedPickup['HOUSE'] !== '' && $addrHouse === '') {
                $addrHouse = $parsedPickup['HOUSE'];
            }
            // корпус/строение/офис дополняем, если пусты
            if ($parsedPickup['KORPUS'] !== '' && $addrKorpus === '') { $addrKorpus = $parsedPickup['KORPUS']; }
            if ($parsedPickup['BUILDING'] !== '' && $addrBuilding === '') { $addrBuilding = $parsedPickup['BUILDING']; }
            if ($parsedPickup['OFFICE'] !== '' && $addrOffice === '') { $addrOffice = $parsedPickup['OFFICE']; }
           
            $hasAddrParts = ($addrCity !== '' || $addrStreet !== '' || $addrHouse !== '' || $addrKorpus !== '' || $addrBuilding !== '' || $addrOffice !== '' || $addrZip !== '' || $addrCountry !== '');
        }

        if ($hasAddrParts || !empty($reqMap['Адрес доставки'])) {
            $addr = $ensureChild($docNode, 'АдресДоставки');
            if ($hasAddrParts) {
                // Сформируем представление из частей
                $presentationParts = array_filter([
                    $addrCountry ?: null,
                    $addrCity ? 'г. '.$addrCity : null,
                    $addrStreet ? 'ул. '.$addrStreet : null,
                    $addrHouse ? 'д. '.$addrHouse : null,
                    $addrKorpus ? 'корп. '.$addrKorpus : null,
                    $addrBuilding ? 'стр. '.$addrBuilding : null,
                    $addrOffice ? 'офис '.$addrOffice : null,
                ]);
                $ensureChild($addr, 'Представление', implode(', ', $presentationParts));
                if ($addrZip !== '') { $ensureChild($addr, 'ПочтовыйИндекс', $addrZip); }
                if ($addrCountry !== '') { $ensureChild($addr, 'Страна', $addrCountry); }
                if ($addrCity !== '') { $ensureChild($addr, 'Город', $addrCity); }
                if ($addrStreet !== '') { $ensureChild($addr, 'Улица', $addrStreet); }
                if ($addrHouse !== '') { $ensureChild($addr, 'Дом', $addrHouse); }
                $ensureChild($addr, 'Корпус', $addrKorpus);
                $ensureChild($addr, 'Строение', $addrBuilding);
                $ensureChild($addr, 'Офис', $addrOffice);
            } else {
                
                $ensureChild($addr, 'Представление', (string)$reqMap['Адрес доставки']);
                $val = (string)$reqMap['Адрес доставки'];
                if (preg_match('/\b(\d{6})\b/u', $val, $m)) { $ensureChild($addr, 'ПочтовыйИндекс', $m[1]); }
                if (preg_match('/г\.?\s*([\p{L}\-\s]+)/u', $val, $m)) { $ensureChild($addr, 'Город', trim($m[1])); }
                $ensureChild($addr, 'Страна');
                $ensureChild($addr, 'Улица');
                $ensureChild($addr, 'Дом');
                $ensureChild($addr, 'Корпус');
                $ensureChild($addr, 'Строение');
                $ensureChild($addr, 'Офис');
            }
        }

        // После переноса удаляем старые ЗначенияРеквизитов целиком
        if ($req && $req->parentNode === $docNode) { $docNode->removeChild($req); $req = null; }

        
        $reqDoc = $dom->createElement('ЗначенияРеквизитов');
        $zr = $dom->createElement('ЗначениеРеквизита');
        $zr->appendChild($dom->createElement('Наименование', 'Тип плательщика (для обмена)'));
        $zr->appendChild($dom->createElement('Значение', $payerValue));
        $reqDoc->appendChild($zr);
        $docNode->appendChild($reqDoc);

     
        $buyer = $xp->query('./Контрагенты/Контрагент', $docNode)->item(0);
        if ($buyer) {
            // Наименование/ПолноеНаименование
            $nameNode = $xp->query('./Наименование', $buyer)->item(0);
            $displayName = $nameNode ? trim($nameNode->nodeValue) : '';
            if ($isYL && $org !== '') { $displayName = $org; $ensureChild($buyer, 'Наименование', $displayName); }
            $ensureChild($buyer, 'ПолноеНаименование', $displayName);
            
            if (!$isYL) { $ensureChild($buyer, 'Имя', $displayName); }
            // ИНН/КПП
            $ensureChild($buyer, 'ИНН', $isYL ? $inn : ($inn ?: ''));
            $ensureChild($buyer, 'КПП', $isYL ? $kpp : '');

           
            $addrReg = $ensureChild($buyer, 'АдресРегистрации');
            $legalAddr = $xp->query('./ЮридическийАдрес', $buyer)->item(0) ?: $xp->query('./Адрес', $buyer)->item(0);
            $presentation = '';
            $postcode = '';
            $country = '';
            $city = '';
            $street = '';
            if ($legalAddr) {
                $presNode = $xp->query('./Представление', $legalAddr)->item(0);
                if ($presNode) { $presentation = trim($presNode->nodeValue); }
                foreach ($xp->query('./АдресноеПоле', $legalAddr) as $ap) {
                    $t = trim((string)$xp->query('./Тип', $ap)->item(0)?->nodeValue);
                    $z = (string)$xp->query('./Значение', $ap)->item(0)?->nodeValue;
                    if ($t === 'Почтовый индекс') { $postcode = $z; }
                    elseif ($t === 'Страна') { $country = $z; }
                    elseif ($t === 'Город') { $city = $z; }
                    elseif ($t === 'Улица') { $street = $z; }
                }
            }
            if ($presentation !== '') { $ensureChild($addrReg, 'Представление', $presentation); }
            if ($postcode !== '') { $ensureChild($addrReg, 'ПочтовыйИндекс', $postcode); }
            if ($country !== '') { $ensureChild($addrReg, 'Страна', $country); }
            if ($city !== '') { $ensureChild($addrReg, 'Город', $city); }
            if ($street !== '') { $ensureChild($addrReg, 'Улица', $street); }
            $ensureChild($addrReg, 'Дом');
            $ensureChild($addrReg, 'Корпус');
            $ensureChild($addrReg, 'Строение');
            $ensureChild($addrReg, 'Офис');

           
            $email = $emailProp !== '' ? $emailProp : '';
            if ($email === '') {
                $emailNode = $xp->query('./Контакты/Контакт[Тип="Почта"]/Значение', $buyer)->item(0);
                if ($emailNode) { $email = trim($emailNode->nodeValue); }
            }
            if ($email === '' && method_exists($order, 'getPropertyCollection')) {
                $email = trim((string)$order->getPropertyCollection()->getUserEmail()?->getValue());
            }
            if ($email !== '') { $ensureChild($buyer, 'ЭлектроннаяПочта', $email); }

            $phone = $phoneProp !== '' ? $phoneProp : '';
            if ($phone === '' && method_exists($order, 'getPropertyCollection')) {
                $phone = trim((string)$order->getPropertyCollection()->getPhone()?->getValue());
            }
            if ($phone !== '') { $ensureChild($buyer, 'НомерТелефона', $phone); }

           
            foreach (['ЮридическийАдрес','Адрес','Контакты'] as $legacy) {
                $n = $xp->query('./'.$legacy, $buyer)->item(0);
                if ($n && $n->parentNode === $buyer) { $buyer->removeChild($n); }
            }
        }

        
        if ($contactFioProp !== '' || $contactPosProp !== '' || $phoneProp !== '') {
            
            $existingCl = $xp->query('./КонтактноеЛицо', $docNode)->item(0);
            $cl = $existingCl ?: $dom->createElement('КонтактноеЛицо');
            while ($cl->firstChild) { $cl->removeChild($cl->firstChild); }
            if ($contactFioProp !== '') { $cl->appendChild($dom->createElement('ФИО', $contactFioProp)); }
            if ($contactPosProp !== '') { $cl->appendChild($dom->createElement('Должность', $contactPosProp)); }
            if ($phoneProp !== '') { $cl->appendChild($dom->createElement('НомерТелефона', $phoneProp)); }

            
            if ($cl->parentNode) { $cl->parentNode->removeChild($cl); }
            $contractors = $xp->query('./Контрагенты', $docNode)->item(0);
            if ($contractors) {
                $next = $contractors->nextSibling;
                if ($next) { $docNode->insertBefore($cl, $next); }
                else { $docNode->appendChild($cl); }
            } else {
                
                $first = $docNode->firstChild;
                if ($first) { $docNode->insertBefore($cl, $first); }
                else { $docNode->appendChild($cl); }
            }
        }

       
        Debug::writeToFile(
            ['ORDER_ID'=>$orderId,'PT'=>$payerValue,'INN'=>$inn,'KPP'=>$kpp,'ORG'=>$org],
            'PAYER_TYPE_XML_PLUS',
            $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log'
        );

        
        foreach ($xp->query('./Товары/Товар', $docNode) as $itemNode) {
            // Достаём/создаём контейнер ЗначенияРеквизитов для конкретного товара
            $itemReq = $xp->query('./ЗначенияРеквизитов', $itemNode)->item(0);
            if (!$itemReq) {
                $itemReq = $dom->createElement('ЗначенияРеквизитов');
                $itemNode->appendChild($itemReq);
            }

            
            $addItemReq = function(string $name, string $value) use ($dom, $xp, $itemReq): void {
                foreach ($xp->query('./ЗначениеРеквизита/Наименование', $itemReq) as $n) {
                    if (trim($n->nodeValue) === $name) { return; }
                }
                $zr = $dom->createElement('ЗначениеРеквизита');
                $zr->appendChild($dom->createElement('Наименование', $name));
                $zr->appendChild($dom->createElement('Значение', $value));
                $itemReq->appendChild($zr);
            };

     
            $idNode = $xp->query('./Ид', $itemNode)->item(0);
            $primaryXmlId = $idNode ? trim($idNode->nodeValue) : '';
            $offerXmlIdFromComposite = '';
            if ($primaryXmlId !== '' && strpos($primaryXmlId, '#') !== false) {
                
                $parts = explode('#', $primaryXmlId, 2);
                $offerXmlIdFromComposite = trim($parts[1] ?? '');
            }

            
            $candidates = [];
           
            foreach ($xp->query('./ЗначенияРеквизитов/ЗначениеРеквизита', $itemNode) as $zr) {
                $name = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                $val  = trim((string)$xp->query('./Значение', $zr)->item(0)?->nodeValue);
                if ($val === '') { continue; }
                if ($name === 'СвойствоКорзины#PRODUCT.XML_ID') { $candidates['PRODUCT_XML_ID'] = $val; }
                if ($name === 'СвойствоКорзины#CATALOG.XML_ID') { $candidates['CATALOG_XML_ID'] = $val; }
            }

            $tryOrder = [];
            // 0) Если из составного Ид удалось получить правую часть (XML_ID оффера) — пробуем её первой
            if ($offerXmlIdFromComposite !== '') { $tryOrder[] = $offerXmlIdFromComposite; }
            if (!empty($candidates['PRODUCT_XML_ID'])) { $tryOrder[] = $candidates['PRODUCT_XML_ID']; }
            if ($primaryXmlId !== '') { $tryOrder[] = $primaryXmlId; }
            if (!empty($candidates['CATALOG_XML_ID'])) { $tryOrder[] = $candidates['CATALOG_XML_ID']; }

            $tryOrder = array_values(array_unique($tryOrder));

            $symbolicCode = '';
            $symbolicSource = '';

            
            if ($offerXmlIdFromComposite !== '') {
                $symbolicCode = $offerXmlIdFromComposite;
                $symbolicSource = 'from_composite_id_right_part';
            }

            
            if ($symbolicCode === '') {
                foreach ($tryOrder as $xmlIdCandidate) {
                    $res = \CIBlockElement::GetList([], ['=XML_ID' => $xmlIdCandidate], false, ['nTopCount' => 1], ['ID','IBLOCK_ID','CODE']);
                    if ($el = $res->Fetch()) {
                        $symbolicCode = (string)$el['CODE'];
                        $symbolicSource = 'by_xml_id_lookup';
                        break;
                    }
                }
            }

            
            if ($symbolicCode === '') {
                foreach ($tryOrder as $codeCandidate) {
                    $res = \CIBlockElement::GetList([], ['=CODE' => $codeCandidate], false, ['nTopCount' => 1], ['ID','IBLOCK_ID','CODE']);
                    if ($el = $res->Fetch()) {
                        $symbolicCode = (string)$el['CODE'];
                        $symbolicSource = 'by_code_lookup';
                        break;
                    }
                }
            }

           
            if ($symbolicCode !== '') {
                // Запишем как отдельный тег
                $artNode = $xp->query('./Артикул', $itemNode)->item(0);
                if (!$artNode) { $artNode = $dom->createElement('Артикул'); $itemNode->insertBefore($artNode, $xp->query('./БазоваяЕдиница', $itemNode)->item(0) ?: $itemNode->firstChild); }
                while ($artNode->firstChild) { $artNode->removeChild($artNode->firstChild); }
                $artNode->appendChild($dom->createTextNode($symbolicCode));
            }

           
            $cartPos = '';
            foreach ($xp->query('./ЗначенияРеквизитов/ЗначениеРеквизита', $itemNode) as $zr) {
                $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                $v = (string)$xp->query('./Значение', $zr)->item(0)?->nodeValue;
                if ($n === 'НомерПозицииКорзины') { $cartPos = $v; }
            }
            if ($cartPos !== '') {
                $numNode = $xp->query('./НомерПозицииКорзины', $itemNode)->item(0);
                if (!$numNode) { $numNode = $dom->createElement('НомерПозицииКорзины'); $itemNode->appendChild($numNode); }
                while ($numNode->firstChild) { $numNode->removeChild($numNode->firstChild); }
                $numNode->appendChild($dom->createTextNode($cartPos));
            }

            
            $guid = '';
            foreach ([$offerXmlIdFromComposite, $primaryXmlId] as $cand) {
                if ($guid === '' && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', (string)$cand)) { $guid = (string)$cand; }
            }
            if ($guid !== '') {
                $guidNode = $xp->query('./GUID_1c', $itemNode)->item(0);
                if (!$guidNode) { $guidNode = $dom->createElement('GUID_1c'); $itemNode->insertBefore($guidNode, $xp->query('./Наименование', $itemNode)->item(0)?->nextSibling ?: $itemNode->firstChild); }
                while ($guidNode->firstChild) { $guidNode->removeChild($guidNode->firstChild); }
                $guidNode->appendChild($dom->createTextNode($guid));
            }

            // Очистим из позиции реквизит с Артикул/НомерПозицииКорзины, если добавили отдельные теги
            $itemReq = $xp->query('./ЗначенияРеквизитов', $itemNode)->item(0);
            if ($itemReq) {
                foreach ($xp->query('./ЗначениеРеквизита', $itemReq) as $zr) {
                    $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                    if (in_array($n, ['Артикул','НомерПозицииКорзины'], true)) { $itemReq->removeChild($zr); }
                }
               
                if (!$xp->query('./ЗначениеРеквизита', $itemReq)->length) { $itemNode->removeChild($itemReq); }
            }

           
            Debug::writeToFile([
                'ORDER_ID' => $orderId,
                'ITEM_XML_ID_PRIMARY' => $primaryXmlId,
                'ITEM_OFFER_XML_FROM_COMPOSITE' => $offerXmlIdFromComposite,
                'ITEM_XML_CANDIDATES' => $tryOrder,
                'ITEM_CODE' => $symbolicCode,
                'ITEM_CODE_SOURCE' => $symbolicSource,
            ], 'PAYER_TYPE_XML_PLUS_ITEM', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
        }
    }

  
    $content = $dom->saveXML();
    }
}


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
                ], 'CDEK_ADDRESS_PARSER', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
                return;
            }
            
            $fullAddress = trim((string)$addressProperty->getValue());
            
            
            if (empty($fullAddress) || !preg_match('/#[A-Z0-9]+$/u', $fullAddress)) {
               
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                    'ERROR' => 'Address does not contain CDEK code',
                    'ADDRESS' => $fullAddress,
                ], 'CDEK_ADDRESS_PARSER', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
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
            ], 'CDEK_ADDRESS_PARSER', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
            
        } catch (\Throwable $e) {
           
            \Bitrix\Main\Diag\Debug::writeToFile([
                'ERROR' => $e->getMessage(),
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine(),
                'TRACE' => $e->getTraceAsString(),
            ], 'CDEK_ADDRESS_PARSER_ERROR', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
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
        } catch (\Throwable $e) {
            try {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => $orderId,
                    'ERROR' => $e->getMessage(),
                    'FILE' => $e->getFile(),
                    'LINE' => $e->getLine(),
                ], 'ORDER_NEW_SEND_EMAIL', $_SERVER['DOCUMENT_ROOT'].'/upload/order_email_ext.log');
            } catch (\Throwable $e2) {
            }
        }
    }
}

if (function_exists('AddEventHandler')) {
    AddEventHandler('sale', 'OnOrderNewSendEmail', 'kipasoOnOrderNewSendEmail');
}

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleComponentOrderOneStepProcess',
    'kipasoOnSaleComponentOrderProcess'
);
