<?php
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;

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

// Лог факта загрузки init.php (однократно на запрос)
try {
    \Bitrix\Main\Diag\Debug::writeToFile([
        'stage' => 'init_loaded',
        'time' => date('c'),
        'area' => (defined('ADMIN_SECTION') && ADMIN_SECTION === true) ? 'admin' : 'public',
    ], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
} catch (\Throwable $e) {}

if (!function_exists('kipasoOnEndBufferContent')) {
    function kipasoOnEndBufferContent(&$content): void {
    // Обрабатываем только /bitrix/admin/1c_exchange.php?type=sale&mode=query
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

    // Лог входящего состояния (для диагностики)
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
        // Попытка принудительно привести к UTF-8, если объявление кодировки отсутствует/некорректно
        $try = @mb_convert_encoding($content, 'UTF-8');
        $loaded = $try ? @$dom->loadXML($try) : false;
        if (!$loaded) {
            Debug::writeToFile(['error' => 'XML parse failed'], 'PAYER_TYPE_XML_PLUS', $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log');
            return;
        }
    }

    $xp = new \DOMXPath($dom);

    foreach ($xp->query('//КоммерческаяИнформация/Документ') as $docNode) {
        // ID заказа
        $idNode = $xp->query('./Ид', $docNode)->item(0);
        if (!$idNode) { continue; }
        $orderId = (int)trim($idNode->nodeValue);
        if ($orderId <= 0) { continue; }

        $order = \Bitrix\Sale\Order::load($orderId);
        if (!$order) { continue; }

        $isYL = ((int)$order->getPersonTypeId() === PT_YL);
        $payerValue = $isYL ? 'Юридическое лицо' : 'Физическое лицо';

        // Чтение свойств заказа
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

        // Реквизиты уровня документа (старые) — соберём в карту для переноса в новые теги
        $req = $xp->query('./ЗначенияРеквизитов', $docNode)->item(0);
        $reqMap = [];
        if ($req) {
            foreach ($xp->query('./ЗначениеРеквизита', $req) as $zr) {
                $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                $v = (string)$xp->query('./Значение', $zr)->item(0)?->nodeValue;
                if ($n !== '') { $reqMap[$n] = $v; }
            }
        }

        // Поместим "Тип плательщика (для обмена)" при необходимости (оставим для совместимости, но перенесём также в Контрагент)
        if (!isset($reqMap['Тип плательщика (для обмена)'])) {
            $reqMap['Тип плательщика (для обмена)'] = $payerValue;
        }

        // Хелпер: получить/создать одиночный дочерний узел
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

        // Адрес доставки -> АдресДоставки (структурированный контейнер)
        // Приоритет: отдельные свойства заказа; если они не полные — дополняем из текстового реквизита "Адрес доставки" (например, адрес ПВЗ СДЭК)
        $hasAddrParts = ($addrCity !== '' || $addrStreet !== '' || $addrHouse !== '' || $addrKorpus !== '' || $addrBuilding !== '' || $addrOffice !== '' || $addrZip !== '' || $addrCountry !== '');

        // Попытка дополнить недостающие части из текстового адреса, если он похож на адрес СДЭК (содержит код #XXXX)
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
            // переоценим наличие частей после дополнения
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
                // Фолбэк на старый текстовый адрес
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

        // ВОССТАНОВИМ документный блок ЗначенияРеквизитов с единственным реквизитом:
        //  Тип плательщика (для обмена) = {Физическое лицо|Юридическое лицо}
        // Реквизит определяется ТОЛЬКО по типу плательщика заказа и не зависит от пользовательского свойства.
        $reqDoc = $dom->createElement('ЗначенияРеквизитов');
        $zr = $dom->createElement('ЗначениеРеквизита');
        $zr->appendChild($dom->createElement('Наименование', 'Тип плательщика (для обмена)'));
        $zr->appendChild($dom->createElement('Значение', $payerValue));
        $reqDoc->appendChild($zr);
        $docNode->appendChild($reqDoc);

        // Обновим блок Контрагента под новую схему
        $buyer = $xp->query('./Контрагенты/Контрагент', $docNode)->item(0);
        if ($buyer) {
            // Наименование/ПолноеНаименование
            $nameNode = $xp->query('./Наименование', $buyer)->item(0);
            $displayName = $nameNode ? trim($nameNode->nodeValue) : '';
            if ($isYL && $org !== '') { $displayName = $org; $ensureChild($buyer, 'Наименование', $displayName); }
            $ensureChild($buyer, 'ПолноеНаименование', $displayName);
            // Имя (для ФЛ можно продублировать отображаемое имя)
            if (!$isYL) { $ensureChild($buyer, 'Имя', $displayName); }
            // ИНН/КПП
            $ensureChild($buyer, 'ИНН', $isYL ? $inn : ($inn ?: ''));
            $ensureChild($buyer, 'КПП', $isYL ? $kpp : '');

            // АдресРегистрации из ЮридическийАдрес или Адрес
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

            // Контакты -> ЭлектроннаяПочта/НомерТелефона
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

            // Удалим старые ЮридическийАдрес/Адрес/Контакты чтобы соответствовать новой схеме
            foreach (['ЮридическийАдрес','Адрес','Контакты'] as $legacy) {
                $n = $xp->query('./'.$legacy, $buyer)->item(0);
                if ($n && $n->parentNode === $buyer) { $buyer->removeChild($n); }
            }
        }

        // КонтактноеЛицо: сформировать и разместить сразу после блока Контрагенты
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

        // Лог (на время отладки)
        Debug::writeToFile(
            ['ORDER_ID'=>$orderId,'PT'=>$payerValue,'INN'=>$inn,'KPP'=>$kpp,'ORG'=>$org],
            'PAYER_TYPE_XML_PLUS',
            $_SERVER['DOCUMENT_ROOT'].'/upload/payer_type_xml.log'
        );

        // 5) На уровне позиций заказа: добавить "Символьный код товара"
        foreach ($xp->query('./Товары/Товар', $docNode) as $itemNode) {
            // Достаём/создаём контейнер ЗначенияРеквизитов для конкретного товара
            $itemReq = $xp->query('./ЗначенияРеквизитов', $itemNode)->item(0);
            if (!$itemReq) {
                $itemReq = $dom->createElement('ЗначенияРеквизитов');
                $itemNode->appendChild($itemReq);
            }

            // Хелпер добавления реквизита для товара без дублей
            $addItemReq = function(string $name, string $value) use ($dom, $xp, $itemReq): void {
                foreach ($xp->query('./ЗначениеРеквизита/Наименование', $itemReq) as $n) {
                    if (trim($n->nodeValue) === $name) { return; }
                }
                $zr = $dom->createElement('ЗначениеРеквизита');
                $zr->appendChild($dom->createElement('Наименование', $name));
                $zr->appendChild($dom->createElement('Значение', $value));
                $itemReq->appendChild($zr);
            };

            // Определяем XML_ID товара/предложения
            $idNode = $xp->query('./Ид', $itemNode)->item(0);
            $primaryXmlId = $idNode ? trim($idNode->nodeValue) : '';
            $offerXmlIdFromComposite = '';
            if ($primaryXmlId !== '' && strpos($primaryXmlId, '#') !== false) {
                // В CML часто используется формат PROD_XML#OFFER_XML
                $parts = explode('#', $primaryXmlId, 2);
                $offerXmlIdFromComposite = trim($parts[1] ?? '');
            }

            // Возможные кандидаты XML_ID из значений реквизитов позиции (если присутствуют)
            $candidates = [];
            // приоритет: PRODUCT.XML_ID (элемент), затем Ид позиции, затем CATALOG.XML_ID
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

            // Простой кейс: если Ид имел вид PROD#OFFER, часто OFFER = символьный код (артикул). Используем напрямую.
            if ($offerXmlIdFromComposite !== '') {
                $symbolicCode = $offerXmlIdFromComposite;
                $symbolicSource = 'from_composite_id_right_part';
            }

            // Если не удалось — ищем элемент по XML_ID и берем его CODE
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

            // Если всё ещё пусто — попробуем точно так же искать по CODE (бывают выгрузки, где в Ид кладут CODE)
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

            // В новую схему добавляем отдельный тег Артикул
            if ($symbolicCode !== '') {
                // Запишем как отдельный тег
                $artNode = $xp->query('./Артикул', $itemNode)->item(0);
                if (!$artNode) { $artNode = $dom->createElement('Артикул'); $itemNode->insertBefore($artNode, $xp->query('./БазоваяЕдиница', $itemNode)->item(0) ?: $itemNode->firstChild); }
                while ($artNode->firstChild) { $artNode->removeChild($artNode->firstChild); }
                $artNode->appendChild($dom->createTextNode($symbolicCode));
            }

            // Если в ЗначенияРеквизитов была НомерПозицииКорзины — поднимем в отдельный тег
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

            // GUID_1c — если сможем получить из XML_ID товара (UUID-образный), добавим
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
                // Удалим контейнер, если он опустел
                if (!$xp->query('./ЗначениеРеквизита', $itemReq)->length) { $itemNode->removeChild($itemReq); }
            }

            // Отладочный лог по позиции
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

    // Возвращаем валидный XML (с декларацией)
    $content = $dom->saveXML();
    }
}

// Регистрируем обработчик и через D7 EventManager, и через старый AddEventHandler
EventManager::getInstance()->addEventHandler('main', 'OnEndBufferContent', 'kipasoOnEndBufferContent', false, 1);
if (function_exists('AddEventHandler')) {
    AddEventHandler('main', 'OnEndBufferContent', 'kipasoOnEndBufferContent', 1);
}

// ============================================================================
// ОБРАБОТКА АДРЕСА СДЭК ПРИ ОФОРМЛЕНИИ ЗАКАЗА
// ============================================================================

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
        
        // Удаляем код пункта выдачи (#SVRN129)
        if (preg_match('/#([A-Z0-9]+)$/u', $fullAddress, $matches)) {
            $result['CODE'] = $matches[1];
            $fullAddress = trim(preg_replace('/#[A-Z0-9]+$/u', '', $fullAddress));
        }
        
        // Разбираем адрес по запятым
        $parts = array_map('trim', explode(',', $fullAddress));
        
        if (count($parts) >= 1) {
            // Первая часть - город (убираем префиксы "г.", "город")
            $result['CITY'] = preg_replace('/^(г\.?|город)\s*/ui', '', $parts[0]);
        }
        
        if (count($parts) >= 2) {
            // Вторая часть - улица (убираем префиксы "ул.", "улица", "пр.", "проспект")
            $result['STREET'] = preg_replace('/^(ул\.?|улица|пр\.?|проспект|пер\.?|переулок)\s*/ui', '', $parts[1]);
        }
        
        if (count($parts) >= 3) {
            // Третья часть - может содержать дом, корпус, строение
            $addressPart = $parts[2];
            
            // Извлекаем номер дома
            if (preg_match('/(?:^|д\.?\s*|дом\s*)(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['HOUSE'] = $matches[1];
            }
            
            // Извлекаем корпус
            if (preg_match('/(?:к\.?|корп\.?|корпус)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['KORPUS'] = $matches[1];
            }
            
            // Извлекаем строение
            if (preg_match('/(?:с\.?|стр\.?|строение)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['BUILDING'] = $matches[1];
            }
            
            // Извлекаем офис/квартиру
            if (preg_match('/(?:оф\.?|офис|кв\.?|квартира)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['OFFICE'] = $matches[1];
            }
        }
        
        // Обработка дополнительных частей адреса (если разделены запятыми)
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
            
            // Получаем свойство с полным адресом доставки
            $addressProperty = null;
            $allProps = [];
            
            foreach ($propertyCollection as $property) {
                $code = (string)$property->getField('CODE');
                $name = (string)$property->getField('NAME');
                $value = trim((string)$property->getValue());
                
                // Собираем все свойства для логирования
                $allProps[] = [
                    'CODE' => $code,
                    'NAME' => $name,
                    'VALUE' => mb_substr($value, 0, 100) // Ограничиваем для лога
                ];
                
                // Ищем свойство "Адрес доставки"
                if (in_array(mb_strtoupper($code), ['ADDRESS', 'DELIVERY_ADDRESS', 'АДРЕС_ДОСТАВКИ', 'LOCATION', 'COMPANY_ADR'], true) ||
                    mb_stripos($name, 'Адрес доставки') !== false ||
                    (mb_stripos($name, 'адрес') !== false && mb_stripos($name, 'юридический') === false)) {
                    
                    // Проверяем, содержит ли значение код СДЭК
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
            
            // Проверяем, что это адрес СДЭК (содержит код пункта #XXX)
            if (empty($fullAddress) || !preg_match('/#[A-Z0-9]+$/u', $fullAddress)) {
                // Это не адрес СДЭК, пропускаем
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                    'ERROR' => 'Address does not contain CDEK code',
                    'ADDRESS' => $fullAddress,
                ], 'CDEK_ADDRESS_PARSER', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
                return;
            }
            
            // Парсим адрес
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
                // Если по коду не найдено, ищем по названию
                foreach ($propertyCollection as $p) {
                    $name = (string)$p->getField('NAME');
                    if ($name !== '' && in_array(mb_strtoupper($name), $upper, true)) {
                        return $p;
                    }
                }
                return null;
            };
            
            $updated = false;
            
            // Заполняем город
            if ($parsed['CITY'] && ($cityProp = $getPropByCodes(PROP_CODES_CITY))) {
                $currentValue = trim((string)$cityProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $cityProp->setValue($parsed['CITY']);
                    $updated = true;
                }
            }
            
            // Заполняем улицу
            if ($parsed['STREET'] && ($streetProp = $getPropByCodes(PROP_CODES_STREET))) {
                $currentValue = trim((string)$streetProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $streetProp->setValue($parsed['STREET']);
                    $updated = true;
                }
            }
            
            // Заполняем дом
            if ($parsed['HOUSE'] && ($houseProp = $getPropByCodes(PROP_CODES_HOUSE))) {
                $currentValue = trim((string)$houseProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $houseProp->setValue($parsed['HOUSE']);
                    $updated = true;
                }
            }
            
            // Заполняем корпус
            if ($parsed['KORPUS'] && ($korpusProp = $getPropByCodes(PROP_CODES_KORPUS))) {
                $currentValue = trim((string)$korpusProp->getValue());
                if (empty($currentValue)) {
                    $korpusProp->setValue($parsed['KORPUS']);
                    $updated = true;
                }
            }
            
            // Заполняем строение
            if ($parsed['BUILDING'] && ($buildingProp = $getPropByCodes(PROP_CODES_BUILDING))) {
                $currentValue = trim((string)$buildingProp->getValue());
                if (empty($currentValue)) {
                    $buildingProp->setValue($parsed['BUILDING']);
                    $updated = true;
                }
            }
            
            // Заполняем офис/квартиру
            if ($parsed['OFFICE'] && ($officeProp = $getPropByCodes(PROP_CODES_OFFICE))) {
                $currentValue = trim((string)$officeProp->getValue());
                if (empty($currentValue)) {
                    $officeProp->setValue($parsed['OFFICE']);
                    $updated = true;
                }
            }
            
            // Не нужно сохранять заказ - он еще не создан, изменения будут применены автоматически
            
            // Лог для отладки
            \Bitrix\Main\Diag\Debug::writeToFile([
                'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                'FULL_ADDRESS' => $fullAddress,
                'PARSED' => $parsed,
                'UPDATED' => $updated,
                'TIMESTAMP' => date('Y-m-d H:i:s'),
            ], 'CDEK_ADDRESS_PARSER', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
            
        } catch (\Throwable $e) {
            // Логируем ошибки, но не прерываем выполнение
            \Bitrix\Main\Diag\Debug::writeToFile([
                'ERROR' => $e->getMessage(),
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine(),
                'TRACE' => $e->getTraceAsString(),
            ], 'CDEK_ADDRESS_PARSER_ERROR', $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log');
        }
    }
}

// Регистрируем обработчик события обработки заказа (срабатывает ДО сохранения)
EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleComponentOrderOneStepProcess',
    'kipasoOnSaleComponentOrderProcess'
);
