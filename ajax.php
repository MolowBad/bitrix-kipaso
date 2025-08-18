<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Basket as SaleBasket;
use Bitrix\Sale\Fuser;
use Bitrix\Currency\CurrencyManager;

// Bitrix bootstrap
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->RestartBuffer();

$result = [
    'status' => false,
    'errors' => [],
];

try {
    if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
        throw new \RuntimeException('Required modules not loaded');
    }

    $request = Context::getCurrent()->getRequest();

    $act = (string)$request->get('act');
    $siteId = (string)$request->get('site_id');
    if ($siteId === '') {
        $siteId = SITE_ID;
    }

    if ($act === 'addCart') {
        $isMulti = (string)$request->get('multi') === '1';
        $idsRaw = (string)$request->get('id');
        $qRaw = $request->get('q');

        
        $modification = trim((string)$request->get('modification'));
        $modPrice = $request->get('modification_price');
        $modPrice = ($modPrice !== null && $modPrice !== '') ? (float)$modPrice : null;

        // DEBUG: Логируем входящие данные запроса addCart
        $dbgFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/basket_debug.log';
        $dbgLine = date('Y-m-d H:i:s') . ' - addCart: id=' . $idsRaw . ', q=' . var_export($qRaw, true) . ', modification=' . $modification . ', modification_price=' . var_export($modPrice, true) . ', site_id=' . $siteId . "\n";
        @file_put_contents($dbgFile, $dbgLine, FILE_APPEND);

        if ($idsRaw === '') {
            throw new \InvalidArgumentException('Empty id');
        }

        $basket = SaleBasket::loadItemsForFUser(Fuser::getId(), $siteId);
        $currency = CurrencyManager::getBaseCurrency();

        $added = [];
        $lastProductId = null;

        if ($isMulti) {
            
            $ids = array_filter(explode(';', $idsRaw), static function ($v) { return $v !== '' && is_numeric($v); });
            $qtyMap = [];
            if (is_string($qRaw) && $qRaw !== '') {
                $pairs = array_filter(explode(';', $qRaw));
                foreach ($pairs as $pair) {
                    [$pid, $qty] = array_pad(explode(':', $pair, 2), 2, null);
                    if (is_numeric($pid) && $qty !== null) {
                        $qtyMap[(int)$pid] = max(1, (float)$qty);
                    }
                }
            }

            foreach ($ids as $sid) {
                $pid = (int)$sid;
                $qty = !empty($qtyMap[$pid]) ? (float)$qtyMap[$pid] : 1.0;
                $itemId = addToBasket($basket, $pid, $qty, $currency, $modification, $modPrice);
                if ($itemId) { $added[] = $itemId; }
                $lastProductId = $pid; 
            }
        } else {
            $pid = (int)$idsRaw;
            $qty = $qRaw !== null ? (float)$qRaw : 1.0;
            if ($qty <= 0) { $qty = 1.0; }
            $itemId = addToBasket($basket, $pid, $qty, $currency, $modification, $modPrice);
            if ($itemId) { $added[] = $itemId; }
            $lastProductId = $pid;
        }

        $save = $basket->save();
        if (!$save->isSuccess()) {
            foreach ($save->getErrors() as $e) { $result['errors'][] = $e->getMessage(); }
            throw new \RuntimeException('Basket save failed');
        }

        $result['status'] = true;
        $result['added'] = $added;

        // DEBUG: вернем свойства добавленного элемента корзины
        if (!empty($added)) {
            $debugItemId = (int)end($added);
            $result['debug_item_id'] = $debugItemId;
            $debugProps = [];
            foreach ($basket->getBasketItems() as $bi) {
                if ((int)$bi->getId() === $debugItemId) {
                    $props = $bi->getPropertyCollection()->getPropertyValues();
                    // Преобразуем в простой массив [CODE => VALUE]
                    foreach ($props as $code => $p) {
                        if (is_array($p) && array_key_exists('VALUE', $p)) {
                            $debugProps[$code] = $p['VALUE'];
                        }
                    }
                    break;
                }
            }
            $result['debug_props'] = $debugProps;
        }

        
        ob_start();
        $APPLICATION->IncludeComponent(
            "dresscode:sale.basket.window",
            ".default",
            [
                "SITE_ID" => $siteId,
                "PRODUCT_ID" => (int)$lastProductId,
            ],
            false
        );
        $result['window_component'] = ob_get_clean();
        header('Content-Type: application/json; charset=UTF-8');
    } elseif ($act === 'flushCart') {
     
        $topCartTemplate = (string)$request->get('topCartTemplate');
        $wishListTemplate = (string)$request->get('wishListTemplate');
        $compareTemplate = (string)$request->get('compareTemplate');

        
        $sanitize = static function($v) {
            $v = trim((string)$v);
            if ($v === '' || strtolower($v) === 'undefined' || strtolower($v) === 'null') { return ''; }
            return $v;
        };
        $topCartTemplate = $sanitize($topCartTemplate);
        $wishListTemplate = $sanitize($wishListTemplate);
        $compareTemplate = $sanitize($compareTemplate);

        
        if ($topCartTemplate === '') { $topCartTemplate = 'topCart2'; }
        if ($wishListTemplate === '') { $wishListTemplate = '.default'; }
        if ($compareTemplate === '') { $compareTemplate = '.default'; }

        ob_start();
        echo '<div class="dl">';
        $APPLICATION->IncludeComponent(
            "bitrix:sale.basket.basket.line",
            $topCartTemplate,
            [
                "HIDE_ON_BASKET_PAGES" => "N",
                "PATH_TO_BASKET" => SITE_DIR."personal/cart/",
                "PATH_TO_ORDER" => SITE_DIR."personal/order/make/",
                "PATH_TO_PERSONAL" => SITE_DIR."personal/",
                "PATH_TO_PROFILE" => SITE_DIR."personal/",
                "PATH_TO_REGISTER" => SITE_DIR."login/",
                "POSITION_FIXED" => "N",
                "SHOW_AUTHOR" => "N",
                "SHOW_EMPTY_VALUES" => "Y",
                "SHOW_NUM_PRODUCTS" => "Y",
                "SHOW_PERSONAL_LINK" => "N",
                "SHOW_PRODUCTS" => "Y",
                "SHOW_TOTAL_PRICE" => "Y",
                "COMPONENT_TEMPLATE" => $topCartTemplate,
                "SHOW_DELAY" => "N",
                "SHOW_NOTAVAIL" => "N",
                "SHOW_SUBSCRIBE" => "N",
                "SHOW_IMAGE" => "Y",
                "SHOW_PRICE" => "Y",
                "SHOW_SUMMARY" => "Y",
            ],
            false
        );
        echo '</div>';

        echo '<div class="dl">';
        // Footer cart uses the same component output, cartReload() will inject into #flushFooterCart
        $APPLICATION->IncludeComponent(
            "bitrix:sale.basket.basket.line",
            $topCartTemplate,
            [
                "HIDE_ON_BASKET_PAGES" => "N",
                "PATH_TO_BASKET" => SITE_DIR."personal/cart/",
                "PATH_TO_ORDER" => SITE_DIR."personal/order/make/",
                "PATH_TO_PERSONAL" => SITE_DIR."personal/",
                "PATH_TO_PROFILE" => SITE_DIR."personal/",
                "PATH_TO_REGISTER" => SITE_DIR."login/",
                "POSITION_FIXED" => "N",
                "SHOW_AUTHOR" => "N",
                "SHOW_EMPTY_VALUES" => "Y",
                "SHOW_NUM_PRODUCTS" => "Y",
                "SHOW_PERSONAL_LINK" => "N",
                "SHOW_PRODUCTS" => "Y",
                "SHOW_TOTAL_PRICE" => "Y",
                "COMPONENT_TEMPLATE" => $topCartTemplate,
                "SHOW_DELAY" => "N",
                "SHOW_NOTAVAIL" => "N",
                "SHOW_SUBSCRIBE" => "N",
                "SHOW_IMAGE" => "Y",
                "SHOW_PRICE" => "Y",
                "SHOW_SUMMARY" => "Y",
            ],
            false
        );
        echo '</div>';

        echo '<div class="dl">';
        $APPLICATION->IncludeComponent(
            "dresscode:favorite.line",
            $wishListTemplate,
            [],
            false
        );
        echo '</div>';

        echo '<div class="dl">';
        $APPLICATION->IncludeComponent(
            "dresscode:compare.line",
            $compareTemplate,
            [],
            false
        );
        echo '</div>';

        $html = ob_get_clean();
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        \CMain::FinalActions();
        die();
    } elseif ($act === 'listBasketItems') {
        $items = [];
        $basket = SaleBasket::loadItemsForFUser(Fuser::getId(), $siteId);
        foreach ($basket->getBasketItems() as $bi) {
            $props = [];
            $pv = $bi->getPropertyCollection()->getPropertyValues();
            foreach ($pv as $code => $p) {
                if (is_array($p) && array_key_exists('VALUE', $p)) {
                    $props[$code] = $p['VALUE'];
                }
            }
            $items[] = [
                'ID' => (int)$bi->getId(),
                'PRODUCT_ID' => (int)$bi->getProductId(),
                'QUANTITY' => (float)$bi->getQuantity(),
                'PROPS' => $props,
            ];
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo Json::encode([
            'status' => true,
            'site_id' => $siteId,
            'count' => count($items),
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
        \CMain::FinalActions();
        die();
    } elseif ($act === 'getBasketProps') {
        $basketId = (int)$request->get('basket_id');
        $props = [];
        $exists = false;
        if ($basketId > 0) {
            if (class_exists('Bitrix\\Sale\\Internals\\BasketPropertyTable')) {
                $exists = true;
                $res = \Bitrix\Sale\Internals\BasketPropertyTable::getList([
                    'filter' => ['=BASKET_ID' => $basketId],
                    'select' => ['ID','BASKET_ID','NAME','CODE','VALUE','SORT'],
                    'order' => ['SORT' => 'ASC','ID' => 'ASC']
                ]);
                while ($row = $res->fetch()) {
                    $props[] = $row;
                }
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo Json::encode([
            'status' => true,
            'basket_id' => $basketId,
            'exists' => $exists,
            'props' => $props,
        ], JSON_UNESCAPED_UNICODE);
        \CMain::FinalActions();
        die();
    } elseif ($act === 'getBasketDebugLog') {
        $lines = (int)$request->get('lines');
        if ($lines <= 0 || $lines > 1000) { $lines = 200; }
        $file = $_SERVER['DOCUMENT_ROOT'] . '/upload/basket_debug.log';
        $content = '';
        if (is_file($file) && is_readable($file)) {
            // Простая реализация tail: читаем файл и берём последние N строк
            $all = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($all)) {
                $slice = array_slice($all, -$lines);
                $content = implode("\n", $slice);
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo Json::encode([
            'status' => true,
            'lines' => $lines,
            'content' => $content,
            'exists' => (is_file($file) ? true : false),
        ], JSON_UNESCAPED_UNICODE);
        \CMain::FinalActions();
        die();
    } elseif ($act === 'getProductWindow') {
      
        $productId = (int)$request->get('id');
        ob_start();
        $APPLICATION->IncludeComponent(
            "dresscode:sale.basket.window",
            ".default",
            [
                "SITE_ID" => $siteId,
                "PRODUCT_ID" => $productId,
            ],
            false
        );
        $html = ob_get_clean();
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        \CMain::FinalActions();
        die();
    } else {
        $result['errors'][] = 'Unknown act';
    }
} catch (\Throwable $e) {
    $result['errors'][] = $e->getMessage();
}


header('Content-Type: application/json; charset=UTF-8');
echo Json::encode($result, JSON_UNESCAPED_UNICODE);

// stop Bitrix
\CMain::FinalActions();
die();

/**
 * Add product to basket, optionally with custom modification price and properties.
 *
 * @param \Bitrix\Sale\Basket $basket
 * @param int $productId
 * @param float $quantity
 * @param string $currency
 * @param string $modification
 * @param float|null $modPrice
 * @return int|false New or updated basket item ID on success
 */
function addToBasket(\Bitrix\Sale\Basket $basket, int $productId, float $quantity, string $currency, string $modification = '', ?float $modPrice = null)
{
    // DEBUG: Логируем входные данные
    $logFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/basket_debug.log';
    $logData = date('Y-m-d H:i:s') . " - Добавление товара ID: $productId, Кол-во: $quantity, Модификация: $modification, Цена модификации: $modPrice\n";
    file_put_contents($logFile, $logData, FILE_APPEND);

    if ($productId <= 0) { return false; }

    
    $existing = null;
    foreach ($basket->getBasketItems() as $bi) {
        if ((int)$bi->getProductId() === $productId) {
            // Сравниваем строго по модификации и цене модификации
            $props = $bi->getPropertyCollection()->getPropertyValues();
            $biMod = isset($props['MODIFICATION']['VALUE']) ? (string)$props['MODIFICATION']['VALUE'] : '';
            $biModPrice = isset($props['MODIFICATION_PRICE']['VALUE']) ? (float)$props['MODIFICATION_PRICE']['VALUE'] : null;

            $curMod = (string)$modification;
            $curModPrice = ($modPrice !== null) ? (float)$modPrice : null;

            $modsMatch = ($biMod === $curMod);
            $pricesMatch = ($biModPrice === $curModPrice);

            // Разрешаем слияние только если ОДИНАКОВЫ и модификация, и цена модификации (включая оба пустые)
            if ($modsMatch && $pricesMatch) {
                $existing = $bi;
                break;
            }
        }
    }

    if ($existing) {
        $existing->setField('QUANTITY', (float)$existing->getQuantity() + $quantity);
        
        return (int)($existing->getId() ?: $productId);
    }

    $item = $basket->createItem('catalog', $productId);

    $fields = [
        'QUANTITY' => $quantity,
        'CURRENCY' => $currency,
        'LID' => $basket->getSiteId(),
    ];

    
    if ($modPrice !== null) {
        $fields['PRICE'] = $modPrice;
        $fields['BASE_PRICE'] = $modPrice;
        $fields['CUSTOM_PRICE'] = 'Y';
    } else {
        
        if (\Bitrix\Main\Loader::includeModule('catalog')) {
            global $USER;
            $optimal = \CCatalogProduct::GetOptimalPrice($productId, $quantity, is_object($USER) ? $USER->GetUserGroupArray() : [2], 'N', [], $basket->getSiteId());
            if (is_array($optimal) && !empty($optimal['RESULT_PRICE'])) {
                $fields['PRICE'] = (float)$optimal['RESULT_PRICE']['DISCOUNT_PRICE'];
                $fields['BASE_PRICE'] = (float)$optimal['RESULT_PRICE']['BASE_PRICE'];
                $fields['CUSTOM_PRICE'] = 'N';
                if (!empty($optimal['RESULT_PRICE']['CURRENCY'])) {
                    $fields['CURRENCY'] = (string)$optimal['RESULT_PRICE']['CURRENCY'];
                }
            }
        }
    }

    $item->setFields($fields);
    // Сначала сохраним элемент, чтобы получить валидный BASKET_ID
    $firstSave = $item->save();
    $logFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/basket_debug.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Первое сохранение элемента корзины: " . ($firstSave->isSuccess() ? 'УСПЕШНО' : 'ОШИБКА') . ", ID: " . (int)$item->getId() . "\n", FILE_APPEND);

    if ($modification !== '' || $modPrice !== null) {
        $propCollection = $item->getPropertyCollection();

        // Сформируем массив свойств и установим одним вызовом
        $set = [];
        if ($modification !== '') {
            $set[] = [
                'NAME' => 'Modification',
                'CODE' => 'MODIFICATION',
                'VALUE' => $modification,
                'SORT' => 100,
            ];
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Готовим свойство MODIFICATION: $modification\n", FILE_APPEND);
        }
        if ($modPrice !== null) {
            $set[] = [
                'NAME' => 'Modification price',
                'CODE' => 'MODIFICATION_PRICE',
                'VALUE' => $modPrice,
                'SORT' => 110,
            ];
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Готовим свойство MODIFICATION_PRICE: $modPrice\n", FILE_APPEND);
        }

        if (!empty($set)) {
            $propCollection->setProperty($set);
            $saveResult = $propCollection->save();
            $itemSaveResult = $item->save();

            $log = date('Y-m-d H:i:s') . " - Сохранение коллекции свойств: " . ($saveResult->isSuccess() ? 'УСПЕШНО' : 'ОШИБКА') . "\n";
            $log .= date('Y-m-d H:i:s') . " - Повторное сохранение элемента: " . ($itemSaveResult->isSuccess() ? 'УСПЕШНО' : 'ОШИБКА') . " (ID: " . (int)$item->getId() . ")\n";

            // Доп.проверка: есть ли записи в BasketPropertyTable прямо сейчас
            if (class_exists('Bitrix\\Sale\\Internals\\BasketPropertyTable')) {
                $cnt = 0;
                $res = \Bitrix\Sale\Internals\BasketPropertyTable::getList([
                    'filter' => ['=BASKET_ID' => (int)$item->getId()],
                    'select' => ['ID','CODE','VALUE']
                ]);
                while ($row = $res->fetch()) { $cnt++; }
                $log .= date('Y-m-d H:i:s') . " - Проверка БД: свойств найдено: $cnt для BASKET_ID=" . (int)$item->getId() . "\n";
            }
            file_put_contents($logFile, $log, FILE_APPEND);
        }
    }

    
    return (int)($item->getId() ?: $productId);
}
