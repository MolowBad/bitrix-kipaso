<?php

use Bitrix\Main\Diag\Debug;

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
            if (!$idNode) {
                continue;
            }
            $orderId = (int)trim($idNode->nodeValue);
            if ($orderId <= 0) {
                continue;
            }

            $order = \Bitrix\Sale\Order::load($orderId);
            if (!$order) {
                continue;
            }

            $isYL = ((int)$order->getPersonTypeId() === PT_YL);
            $payerValue = $isYL ? 'Юридическое лицо' : 'Физическое лицо';

            $props = $order->getPropertyCollection();
            $getPropByCodes = function (array $codes) use ($props): string {
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

            $req = $xp->query('./ЗначенияРеквизитов', $docNode)->item(0);
            $reqMap = [];
            if ($req) {
                foreach ($xp->query('./ЗначениеРеквизита', $req) as $zr) {
                    $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                    $v = (string)$xp->query('./Значение', $zr)->item(0)?->nodeValue;
                    if ($n !== '') {
                        $reqMap[$n] = $v;
                    }
                }
            }

            if (!isset($reqMap['Тип плательщика (для обмена)'])) {
                $reqMap['Тип плательщика (для обмена)'] = $payerValue;
            }

            $ensureChild = function (\DOMNode $parent, string $name, ?string $value = null) use ($dom, $xp): \DOMElement {
                $node = $xp->query('./' . $name, $parent)->item(0);
                if (!$node) {
                    $node = $dom->createElement($name);
                    $parent->appendChild($node);
                }
                if ($value !== null) {
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }
                    $node->appendChild($dom->createTextNode($value));
                }
                return $node;
            };

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
                if (isset($reqMap[$old])) {
                    $ensureChild($docNode, $new, (string)$reqMap[$old]);
                }
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
                if ($parsedPickup['KORPUS'] !== '' && $addrKorpus === '') {
                    $addrKorpus = $parsedPickup['KORPUS'];
                }
                if ($parsedPickup['BUILDING'] !== '' && $addrBuilding === '') {
                    $addrBuilding = $parsedPickup['BUILDING'];
                }
                if ($parsedPickup['OFFICE'] !== '' && $addrOffice === '') {
                    $addrOffice = $parsedPickup['OFFICE'];
                }

                $hasAddrParts = ($addrCity !== '' || $addrStreet !== '' || $addrHouse !== '' || $addrKorpus !== '' || $addrBuilding !== '' || $addrOffice !== '' || $addrZip !== '' || $addrCountry !== '');
            }

            if ($hasAddrParts || !empty($reqMap['Адрес доставки'])) {
                $addr = $ensureChild($docNode, 'АдресДоставки');
                if ($hasAddrParts) {
                    $presentationParts = array_filter([
                        $addrCountry ?: null,
                        $addrCity ? 'г. ' . $addrCity : null,
                        $addrStreet ? 'ул. ' . $addrStreet : null,
                        $addrHouse ? 'д. ' . $addrHouse : null,
                        $addrKorpus ? 'корп. ' . $addrKorpus : null,
                        $addrBuilding ? 'стр. ' . $addrBuilding : null,
                        $addrOffice ? 'офис ' . $addrOffice : null,
                    ]);
                    $ensureChild($addr, 'Представление', implode(', ', $presentationParts));
                    if ($addrZip !== '') {
                        $ensureChild($addr, 'ПочтовыйИндекс', $addrZip);
                    }
                    if ($addrCountry !== '') {
                        $ensureChild($addr, 'Страна', $addrCountry);
                    }
                    if ($addrCity !== '') {
                        $ensureChild($addr, 'Город', $addrCity);
                    }
                    if ($addrStreet !== '') {
                        $ensureChild($addr, 'Улица', $addrStreet);
                    }
                    if ($addrHouse !== '') {
                        $ensureChild($addr, 'Дом', $addrHouse);
                    }
                    $ensureChild($addr, 'Корпус', $addrKorpus);
                    $ensureChild($addr, 'Строение', $addrBuilding);
                    $ensureChild($addr, 'Офис', $addrOffice);
                } else {
                    $ensureChild($addr, 'Представление', (string)$reqMap['Адрес доставки']);
                    $val = (string)$reqMap['Адрес доставки'];
                    if (preg_match('/\b(\d{6})\b/u', $val, $m)) {
                        $ensureChild($addr, 'ПочтовыйИндекс', $m[1]);
                    }
                    if (preg_match('/г\.?\s*([\p{L}\-\s]+)/u', $val, $m)) {
                        $ensureChild($addr, 'Город', trim($m[1]));
                    }
                    $ensureChild($addr, 'Страна');
                    $ensureChild($addr, 'Улица');
                    $ensureChild($addr, 'Дом');
                    $ensureChild($addr, 'Корпус');
                    $ensureChild($addr, 'Строение');
                    $ensureChild($addr, 'Офис');
                }
            }

            if ($req && $req->parentNode === $docNode) {
                $docNode->removeChild($req);
                $req = null;
            }

            $reqDoc = $dom->createElement('ЗначенияРеквизитов');
            $zr = $dom->createElement('ЗначениеРеквизита');
            $zr->appendChild($dom->createElement('Наименование', 'Тип плательщика (для обмена)'));
            $zr->appendChild($dom->createElement('Значение', $payerValue));
            $reqDoc->appendChild($zr);
            $docNode->appendChild($reqDoc);

            $buyer = $xp->query('./Контрагенты/Контрагент', $docNode)->item(0);
            if ($buyer) {
                $nameNode = $xp->query('./Наименование', $buyer)->item(0);
                $displayName = $nameNode ? trim($nameNode->nodeValue) : '';
                if ($isYL && $org !== '') {
                    $displayName = $org;
                    $ensureChild($buyer, 'Наименование', $displayName);
                }
                $ensureChild($buyer, 'ПолноеНаименование', $displayName);

                if (!$isYL) {
                    $ensureChild($buyer, 'Имя', $displayName);
                }

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
                    if ($presNode) {
                        $presentation = trim($presNode->nodeValue);
                    }
                    foreach ($xp->query('./АдресноеПоле', $legalAddr) as $ap) {
                        $t = trim((string)$xp->query('./Тип', $ap)->item(0)?->nodeValue);
                        $z = (string)$xp->query('./Значение', $ap)->item(0)?->nodeValue;
                        if ($t === 'Почтовый индекс') {
                            $postcode = $z;
                        } elseif ($t === 'Страна') {
                            $country = $z;
                        } elseif ($t === 'Город') {
                            $city = $z;
                        } elseif ($t === 'Улица') {
                            $street = $z;
                        }
                    }
                }
                if ($presentation !== '') {
                    $ensureChild($addrReg, 'Представление', $presentation);
                }
                if ($postcode !== '') {
                    $ensureChild($addrReg, 'ПочтовыйИндекс', $postcode);
                }
                if ($country !== '') {
                    $ensureChild($addrReg, 'Страна', $country);
                }
                if ($city !== '') {
                    $ensureChild($addrReg, 'Город', $city);
                }
                if ($street !== '') {
                    $ensureChild($addrReg, 'Улица', $street);
                }
                $ensureChild($addrReg, 'Дом');
                $ensureChild($addrReg, 'Корпус');
                $ensureChild($addrReg, 'Строение');
                $ensureChild($addrReg, 'Офис');

                $email = $emailProp !== '' ? $emailProp : '';
                if ($email === '') {
                    $emailNode = $xp->query('./Контакты/Контакт[Тип="Почта"]/Значение', $buyer)->item(0);
                    if ($emailNode) {
                        $email = trim($emailNode->nodeValue);
                    }
                }
                if ($email === '' && method_exists($order, 'getPropertyCollection')) {
                    $email = trim((string)$order->getPropertyCollection()->getUserEmail()?->getValue());
                }
                if ($email !== '') {
                    $ensureChild($buyer, 'ЭлектроннаяПочта', $email);
                }

                $phone = $phoneProp !== '' ? $phoneProp : '';
                if ($phone === '' && method_exists($order, 'getPropertyCollection')) {
                    $phone = trim((string)$order->getPropertyCollection()->getPhone()?->getValue());
                }
                if ($phone !== '') {
                    $ensureChild($buyer, 'НомерТелефона', $phone);
                }

                foreach (['ЮридическийАдрес', 'Адрес', 'Контакты'] as $legacy) {
                    $n = $xp->query('./' . $legacy, $buyer)->item(0);
                    if ($n && $n->parentNode === $buyer) {
                        $buyer->removeChild($n);
                    }
                }
            }

            if ($contactFioProp !== '' || $contactPosProp !== '' || $phoneProp !== '') {
                $existingCl = $xp->query('./КонтактноеЛицо', $docNode)->item(0);
                $cl = $existingCl ?: $dom->createElement('КонтактноеЛицо');
                while ($cl->firstChild) {
                    $cl->removeChild($cl->firstChild);
                }
                if ($contactFioProp !== '') {
                    $cl->appendChild($dom->createElement('ФИО', $contactFioProp));
                }
                if ($contactPosProp !== '') {
                    $cl->appendChild($dom->createElement('Должность', $contactPosProp));
                }
                if ($phoneProp !== '') {
                    $cl->appendChild($dom->createElement('НомерТелефона', $phoneProp));
                }

                if ($cl->parentNode) {
                    $cl->parentNode->removeChild($cl);
                }
                $contractors = $xp->query('./Контрагенты', $docNode)->item(0);
                if ($contractors) {
                    $next = $contractors->nextSibling;
                    if ($next) {
                        $docNode->insertBefore($cl, $next);
                    } else {
                        $docNode->appendChild($cl);
                    }
                } else {
                    $first = $docNode->firstChild;
                    if ($first) {
                        $docNode->insertBefore($cl, $first);
                    } else {
                        $docNode->appendChild($cl);
                    }
                }
            }

            Debug::writeToFile(
                ['ORDER_ID' => $orderId, 'PT' => $payerValue, 'INN' => $inn, 'KPP' => $kpp, 'ORG' => $org],
                'PAYER_TYPE_XML_PLUS',
                $_SERVER['DOCUMENT_ROOT'] . '/upload/payer_type_xml.log'
            );

            foreach ($xp->query('./Товары/Товар', $docNode) as $itemNode) {
                $itemReq = $xp->query('./ЗначенияРеквизитов', $itemNode)->item(0);
                if (!$itemReq) {
                    $itemReq = $dom->createElement('ЗначенияРеквизитов');
                    $itemNode->appendChild($itemReq);
                }

                $addItemReq = function (string $name, string $value) use ($dom, $xp, $itemReq): void {
                    foreach ($xp->query('./ЗначениеРеквизита/Наименование', $itemReq) as $n) {
                        if (trim($n->nodeValue) === $name) {
                            return;
                        }
                    }
                    $zr = $dom->createElement('ЗначениеРеквизита');
                    $zr->appendChild($dom->createElement('Наименование', $name));
                    $zr->appendChild($dom->createElement('Значение', $value));
                    $itemReq->appendChild($zr);
                };

                $idNodeItem = $xp->query('./Ид', $itemNode)->item(0);
                $primaryXmlId = $idNodeItem ? trim($idNodeItem->nodeValue) : '';
                $offerXmlIdFromComposite = '';
                if ($primaryXmlId !== '' && strpos($primaryXmlId, '#') !== false) {
                    $parts = explode('#', $primaryXmlId, 2);
                    $offerXmlIdFromComposite = trim($parts[1] ?? '');
                }

                $candidates = [];
                foreach ($xp->query('./ЗначенияРеквизитов/ЗначениеРеквизита', $itemNode) as $zr) {
                    $name = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                    $val = trim((string)$xp->query('./Значение', $zr)->item(0)?->nodeValue);
                    if ($val === '') {
                        continue;
                    }
                    if ($name === 'СвойствоКорзины#PRODUCT.XML_ID') {
                        $candidates['PRODUCT_XML_ID'] = $val;
                    }
                    if ($name === 'СвойствоКорзины#CATALOG.XML_ID') {
                        $candidates['CATALOG_XML_ID'] = $val;
                    }
                }

                $tryOrder = [];
                if ($offerXmlIdFromComposite !== '') {
                    $tryOrder[] = $offerXmlIdFromComposite;
                }
                if (!empty($candidates['PRODUCT_XML_ID'])) {
                    $tryOrder[] = $candidates['PRODUCT_XML_ID'];
                }
                if ($primaryXmlId !== '') {
                    $tryOrder[] = $primaryXmlId;
                }
                if (!empty($candidates['CATALOG_XML_ID'])) {
                    $tryOrder[] = $candidates['CATALOG_XML_ID'];
                }

                $tryOrder = array_values(array_unique($tryOrder));

                $symbolicCode = '';
                $symbolicSource = '';

                if ($offerXmlIdFromComposite !== '') {
                    $symbolicCode = $offerXmlIdFromComposite;
                    $symbolicSource = 'from_composite_id_right_part';
                }

                if ($symbolicCode === '') {
                    foreach ($tryOrder as $xmlIdCandidate) {
                        $res = \CIBlockElement::GetList([], ['=XML_ID' => $xmlIdCandidate], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'CODE']);
                        if ($el = $res->Fetch()) {
                            $symbolicCode = (string)$el['CODE'];
                            $symbolicSource = 'by_xml_id_lookup';
                            break;
                        }
                    }
                }

                if ($symbolicCode === '') {
                    foreach ($tryOrder as $codeCandidate) {
                        $res = \CIBlockElement::GetList([], ['=CODE' => $codeCandidate], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'CODE']);
                        if ($el = $res->Fetch()) {
                            $symbolicCode = (string)$el['CODE'];
                            $symbolicSource = 'by_code_lookup';
                            break;
                        }
                    }
                }

                if ($symbolicCode !== '') {
                    $artNode = $xp->query('./Артикул', $itemNode)->item(0);
                    if (!$artNode) {
                        $artNode = $dom->createElement('Артикул');
                        $itemNode->insertBefore($artNode, $xp->query('./БазоваяЕдиница', $itemNode)->item(0) ?: $itemNode->firstChild);
                    }
                    while ($artNode->firstChild) {
                        $artNode->removeChild($artNode->firstChild);
                    }
                    $artNode->appendChild($dom->createTextNode($symbolicCode));
                }

                $cartPos = '';
                foreach ($xp->query('./ЗначенияРеквизитов/ЗначениеРеквизита', $itemNode) as $zr) {
                    $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                    $v = (string)$xp->query('./Значение', $zr)->item(0)?->nodeValue;
                    if ($n === 'НомерПозицииКорзины') {
                        $cartPos = $v;
                    }
                }
                if ($cartPos !== '') {
                    $numNode = $xp->query('./НомерПозицииКорзины', $itemNode)->item(0);
                    if (!$numNode) {
                        $numNode = $dom->createElement('НомерПозицииКорзины');
                        $itemNode->appendChild($numNode);
                    }
                    while ($numNode->firstChild) {
                        $numNode->removeChild($numNode->firstChild);
                    }
                    $numNode->appendChild($dom->createTextNode($cartPos));
                }

                $guid = '';
                foreach ([$offerXmlIdFromComposite, $primaryXmlId] as $cand) {
                    if ($guid === '' && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', (string)$cand)) {
                        $guid = (string)$cand;
                    }
                }
                if ($guid !== '') {
                    $guidNode = $xp->query('./GUID_1c', $itemNode)->item(0);
                    if (!$guidNode) {
                        $guidNode = $dom->createElement('GUID_1c');
                        $itemNode->insertBefore($guidNode, $xp->query('./Наименование', $itemNode)->item(0)?->nextSibling ?: $itemNode->firstChild);
                    }
                    while ($guidNode->firstChild) {
                        $guidNode->removeChild($guidNode->firstChild);
                    }
                    $guidNode->appendChild($dom->createTextNode($guid));
                }

                $itemReq = $xp->query('./ЗначенияРеквизитов', $itemNode)->item(0);
                if ($itemReq) {
                    foreach ($xp->query('./ЗначениеРеквизита', $itemReq) as $zr) {
                        $n = trim((string)$xp->query('./Наименование', $zr)->item(0)?->nodeValue);
                        if (in_array($n, ['Артикул', 'НомерПозицииКорзины'], true)) {
                            $itemReq->removeChild($zr);
                        }
                    }
                    if (!$xp->query('./ЗначениеРеквизита', $itemReq)->length) {
                        $itemNode->removeChild($itemReq);
                    }
                }

                Debug::writeToFile([
                    'ORDER_ID' => $orderId,
                    'ITEM_XML_ID_PRIMARY' => $primaryXmlId,
                    'ITEM_OFFER_XML_FROM_COMPOSITE' => $offerXmlIdFromComposite,
                    'ITEM_XML_CANDIDATES' => $tryOrder,
                    'ITEM_CODE' => $symbolicCode,
                    'ITEM_CODE_SOURCE' => $symbolicSource,
                ], 'PAYER_TYPE_XML_PLUS_ITEM', $_SERVER['DOCUMENT_ROOT'] . '/upload/payer_type_xml.log');
            }
        }

        $content = $dom->saveXML();
    }
}
