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

        // Реквизиты именно УРОВНЯ ДОКУМЕНТА
        $req = $xp->query('./ЗначенияРеквизитов', $docNode)->item(0);
        if (!$req) {
            $req = $dom->createElement('ЗначенияРеквизитов');
            $docNode->appendChild($req);
        }

        // Утилита добавления реквизита без дублей
        $addReq = function(string $name, string $value) use ($dom, $xp, $req): void {
            foreach ($xp->query('./ЗначениеРеквизита/Наименование', $req) as $n) {
                if (trim($n->nodeValue) === $name) { return; }
            }
            $zr = $dom->createElement('ЗначениеРеквизита');
            $zr->appendChild($dom->createElement('Наименование', $name));
            $zr->appendChild($dom->createElement('Значение', $value));
            $req->appendChild($zr);
        };

        // 1) Тип плательщика
        $addReq('Тип плательщика (для обмена)', $payerValue);

        // 2) ИНН / 3) КПП / 4) Название организации (для ФЛ — пусто)
        $addReq('ИНН', $isYL ? $inn : '');
        $addReq('КПП', $isYL ? $kpp : '');
        $addReq('Название организации', $isYL ? $org : '');

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

            // Добавляем реквизит (даже если пустой — по требованию 1С-специалиста реквизит должен присутствовать всегда)
            $addItemReq('Артикул', $symbolicCode);

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
