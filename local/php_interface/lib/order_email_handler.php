<?php

use Bitrix\Main\Loader;

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
                ], 'ORDER_NEW_SEND_EMAIL', $_SERVER['DOCUMENT_ROOT'].'/upload/order_email_ext.log');
            } catch (\Throwable $e2) {
            }
        }
    }
}
