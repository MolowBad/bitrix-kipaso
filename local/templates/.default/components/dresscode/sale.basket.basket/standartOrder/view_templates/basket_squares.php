<div class="items productList">
	<?foreach ($arResult["ITEMS"] as $ix => $arElement):?>
		<?$countPos += $arElement["QUANTITY"]?>
		<div class="item product parent" data-product-iblock-id="<?=$arElement["IBLOCK_ID"]?>" data-id="<?=$arElement["ID"]?>" data-cart-id="<?=$arElement["ID"]?>">
			<div class="tabloid">
			 	<div class="topSection">
					<div class="column">
						<?if($arElement["CATALOG_QUANTITY"] > 0):?>
							<?if(!empty($arElement["STORES"])):?>
								<a href="#" data-id="<?=$arElement["ID"]?>" class="inStock label changeAvailable getStoresWindow"><img src="<?=SITE_TEMPLATE_PATH?>/images/inStock.svg" alt="<?=GetMessage("AVAILABLE")?>" class="icon"><span><?=GetMessage("AVAILABLE")?></span></a>
							<?else:?>
								<span class="inStock label changeAvailable"><img src="<?=SITE_TEMPLATE_PATH?>/images/inStock.svg" alt="<?=GetMessage("AVAILABLE")?>" class="icon"><span><?=GetMessage("AVAILABLE")?></span></span>
							<?endif;?>
						<?else:?>
							<?if(!empty($arElement["CATALOG_AVAILABLE"]) && $arElement["CATALOG_AVAILABLE"] == "Y"):?>
								<a class="onOrder label changeAvailable"><img src="<?=SITE_TEMPLATE_PATH?>/images/onOrder.svg" alt="<?=GetMessage("ON_ORDER")?>" class="icon"><?=GetMessage("ON_ORDER")?></a>
							<?else:?>
								<a class="outOfStock label changeAvailable"><img src="<?=SITE_TEMPLATE_PATH?>/images/outOfStock.svg" alt="<?=GetMessage("NOAVAILABLE")?>" class="icon"><?=GetMessage("NOAVAILABLE")?></a>
							<?endif;?>
						<?endif;?>
                    </div>
                    <div class="column">
						<a href="#" class="delete" data-id="<?=$arElement["BASKET_ID"]?>"></a>
                    </div>
			 	</div>
				<div class="productTable">
					<div class="productColImage">
					    <a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="picture" target="_blank">
					    	<img src="<?=!empty($arElement["PICTURE"]["src"]) ? $arElement["PICTURE"]["src"] : SITE_TEMPLATE_PATH."/images/empty.svg"?>" alt="<?=$arElement["NAME"]?>">
					    	<span class="getFastView" data-id="<?=$arElement["PRODUCT_ID"]?>"><?=GetMessage("FAST_VIEW_PRODUCT_LABEL")?></span>
					    </a>
					</div>
					<div class="productColText">
						<a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="name" target="_blank"><span class="middle"><?=$arElement["NAME"]?></span></a>
						<?
						// Отладка в консоль браузера
					$__modName = '';
					$__modPrice = null;
					$__isRealModification = false; // Флаг, показывающий, является ли товар реальной модификацией
					$basketId = isset($arElement['BASKET_ID']) ? $arElement['BASKET_ID'] : 'Нет BASKET_ID';
					$elementId = $arElement['ID'];
					$keysArray = array_keys($arElement);
					$propsFound = !empty($arElement['PROPS']) && is_array($arElement['PROPS']);
					$propsArray = $propsFound ? $arElement['PROPS'] : [];
					
					// Массив для хранения отладочных сообщений JavaScript
					$jsDebug = [];
					$jsDebug[] = "console.group('\u041e\u0442\u043b\u0430\u0434\u043a\u0430 (\u0431\u043b\u043e\u043a\u0438) - ID: {$elementId} / \u041a\u043e\u0440\u0437\u0438\u043d\u0430 ID: {$basketId}');";
					$jsDebug[] = "console.log('\u041a\u043b\u044e\u0447\u0438 \u044d\u043b\u0435\u043c\u0435\u043d\u0442\u0430:', ['" . implode("', '", $keysArray) . "']);";
					$jsDebug[] = "console.log('\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 PROPS:', '" . ($propsFound ? "\u041d\u0410\u0419\u0414\u0415\u041d\u042b" : "\u041d\u0415 \u041d\u0410\u0419\u0414\u0415\u041d\u042b") . "');";
					if ($propsFound) {
						// Формируем массив в формате JavaScript
						$jsDebug[] = "console.log('\u0421\u043e\u0434\u0435\u0440\u0436\u0438\u043c\u043e\u0435 PROPS:');";
						
						$jsTable = "console.table([";
						$first = true;
						foreach ($propsArray as $prop) {
							if (!$first) {
								$jsTable .= ",";
							}
							$jsTable .= "{";
							$propFirst = true;
							foreach ($prop as $key => $value) {
								if (!$propFirst) {
									$jsTable .= ",";
								}
								$jsTable .= "\"$key\":\"" . addslashes($value) . "\"";
								$propFirst = false;
							}
							$jsTable .= "}";
							$first = false;
						}
						$jsTable .= "]);";
						$jsDebug[] = $jsTable;
					}
					
					// 1) Прямой доступ к свойствам корзины через два метода для максимальной надежности
if (!empty($arElement['BASKET_ID']) && \Bitrix\Main\Loader::includeModule('sale')) {
	// Принудительная очистка кеша корзины для гарантированного получения актуальных данных
	try {
		\Bitrix\Main\Application::getInstance()->getTaggedCache()->clearByTag('sale_basket');
		\Bitrix\Main\Data\Cache::createInstance()->clean("sale_basket_".\Bitrix\Sale\Fuser::getId(), "/sale/basket");
	} catch (\Exception $e) {
		// Игнорируем ошибки очистки кеша
	}

	// Попытка 1: CSaleBasket - самый прямой способ получения свойств
	$basketItem = CSaleBasket::GetByID($arElement['BASKET_ID']);
	if ($basketItem && $basketItem['PRODUCT_ID'] == $arElement['PRODUCT_ID']) {
		$jsDebug[] = "console.log('%cНайден текущий товар в корзине ID #' + '{$arElement['BASKET_ID']}', 'color: blue; font-weight: bold');";
		
		// Получаем свойства корзины через CSaleBasket
		$basketProps = [];
		$dbBasketProps = CSaleBasket::GetPropsList(array("SORT" => "ASC"), array("BASKET_ID" => $arElement['BASKET_ID']));
		
		while ($prop = $dbBasketProps->Fetch()) {
			$basketProps[] = $prop;
			$jsDebug[] = "console.log('%cСвойство корзины найдено: ' + '{$prop['CODE']} = {$prop['VALUE']}', 'color: blue');";
			
			if ($prop['CODE'] == 'MODIFICATION' && !empty($prop['VALUE'])) {
				$__modName = $prop['VALUE'];
				$__isRealModification = true; // Помечаем как реальную модификацию
				$jsDebug[] = "console.log('%cНАЙДЕНА МОДИФИКАЦИЯ в CSaleBasket: ' + '{$prop['VALUE']}', 'color: green; font-weight: bold');";
			}
			if ($prop['CODE'] == 'MODIFICATION_PRICE' && !empty($prop['VALUE'])) {
				$__modPrice = (float)str_replace([' ', '\u00A0', "\xC2\xA0"], '', (string)$prop['VALUE']);
				$jsDebug[] = "console.log('%cНАЙДЕНА ЦЕНА МОДИФИКАЦИИ в CSaleBasket: ' + '{$__modPrice}', 'color: green; font-weight: bold');";
			}
		}
	}

	// Попытка 2: Напрямую из БД свойств корзины
	$dbRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList([
		'filter' => ['BASKET_ID' => $arElement['BASKET_ID']],
		'select' => ['CODE', 'VALUE', 'ID', 'NAME']
	]);
	
	// Собираем свойства для консоли
	$dbProps = [];
	while($prop = $dbRes->fetch()) {
		$dbProps[] = $prop;
		$jsDebug[] = "console.log('%cСвойство корзины найдено в БД: ' + '{$prop['CODE']} = {$prop['VALUE']}', 'color: purple');";
		
		if($prop['CODE'] == 'MODIFICATION' && !empty($prop['VALUE'])) {
			$__modName = $prop['VALUE'];
			$__isRealModification = true; // Помечаем как реальную модификацию
			$jsDebug[] = "console.log('%cНАЙДЕНА МОДИФИКАЦИЯ ЧЕРЕЗ БД: ' + '" . addslashes($prop['VALUE']) . "', 'color: green; font-weight: bold');";
		}
		if($prop['CODE'] == 'MODIFICATION_PRICE' && !empty($prop['VALUE'])) {
			$__modPrice = (float)str_replace([' ', '\u00A0', "\xC2\xA0"], '', (string)$prop['VALUE']);
			$jsDebug[] = "console.log('%cНАЙДЕНА ЦЕНА МОДИФИКАЦИИ ЧЕРЕЗ БД: ' + '{$__modPrice}', 'color: green; font-weight: bold');";
		}
	}
					
					// Метод 2: Через объектную модель Bitrix (D7)
					if (empty($__modName)) {
						try {
							// Очистка кеша корзины для гарантированного получения актуальных данных
							\Bitrix\Main\Application::getInstance()->getTaggedCache()->clearByTag('sale_basket');
							
							$registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
							$basketClassName = $registry->getBasketClassName();
							$basket = $basketClassName::loadItemsForFUser(
								\Bitrix\Sale\Fuser::getId(), 
								\Bitrix\Main\Context::getCurrent()->getSite()
							);
							
							$item = $basket->getItemById($arElement['BASKET_ID']);
							if ($item) {
								$propertyCollection = $item->getPropertyCollection();
								$basketProperties = $propertyCollection->getPropertyValues();
								
								// Добавляем отладочную информацию в JavaScript-код вместо вложенных <script>
								$jsDebug[] = "console.log('API-модель корзины (блок): Элемент найден #" . $arElement['BASKET_ID'] . "');";
								
								// Ищем свойства
								$foundApiProps = false;
								foreach ($basketProperties as $property) {
									$foundApiProps = true;
									if ($property['CODE'] == 'MODIFICATION' && !empty($property['VALUE'])) {
										$__modName = $property['VALUE'];
										$__isRealModification = true; // Помечаем как реальную модификацию
										$jsDebug[] = "console.log('%c\u041d\u0410\u0419\u0414\u0415\u041d\u0410 \u043c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u0447\u0435\u0440\u0435\u0437 API (\u0431\u043b\u043e\u043a): " . addslashes($property['VALUE']) . "', 'color: green; font-weight: bold');";
									}
									if ($property['CODE'] == 'MODIFICATION_PRICE' && !empty($property['VALUE'])) {
										$__modPrice = (float)str_replace([' ', '\u00A0', "\xC2\xA0"], '', (string)$property['VALUE']);
									}
								}
								
								if (!$foundApiProps) {
									$jsDebug[] = "console.log('API-модель (блок): Свойства не найдены');";
								}
							}
						} catch (\Exception $e) {
							$jsDebug[] = "console.log('Ошибка при работе с API корзины (блок): " . addslashes($e->getMessage()) . "');";
						}
					}
				}	
						
						// Выводим свойства БД в консоль
						$jsDebug[] = "console.log('\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 \u0438\u0437 \u0411\u0414:', '" . (!empty($dbProps) ? "\u041d\u0410\u0419\u0414\u0415\u041d\u042b" : "\u041d\u0415 \u041d\u0410\u0419\u0414\u0415\u041d\u042b") . "');";
						if (!empty($dbProps)) {
							// Формируем массив в формате JavaScript
							$jsDebug[] = "console.log('\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 \u0438\u0437 \u0411\u0414:');";
							$jsProps = "console.table([";
							$first = true;
							foreach ($dbProps as $prop) {
								if (!$first) {
									$jsProps .= ",";
								}
								$jsProps .= "{";
								$propFirst = true;
								foreach ($prop as $key => $value) {
									if (!$propFirst) {
										$jsProps .= ",";
									}
									$jsProps .= "\"$key\":\"" . addslashes($value) . "\"";
									$propFirst = false;
								}
								$jsProps .= "}";
								$first = false;
							}
							$jsProps .= "]);";
							$jsDebug[] = $jsProps;
						}
					
					// Выводим информацию о модификации, если она есть
					if (!empty($__modName)) {
						$jsDebug[] = "console.log('\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u043d\u0430\u0439\u0434\u0435\u043d\u0430 \u0438 \u043e\u0442\u043e\u0431\u0440\u0430\u0436\u0435\u043d\u0430: ' + '".(addslashes($__modName))."');";
					} else {
						$jsDebug[] = "console.log('\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u041d\u0415 \u043d\u0430\u0439\u0434\u0435\u043d\u0430');";

// Принудительная очистка кеша корзины для устранения проблем с отображением
if (\Bitrix\Main\Loader::includeModule('sale')) {
    try {
        \Bitrix\Main\Application::getInstance()->getTaggedCache()->clearByTag('sale_basket');
        \Bitrix\Main\Data\Cache::createInstance()->clean("sale_basket_".\Bitrix\Sale\Fuser::getId(), "/sale/basket");
    } catch (\Exception $e) {
        // Ошибки очистки кеша не влияют на работу сайта
    }
}

// Добавляем отладку исходных данных
$jsDebug[] = "console.log('%c\u0414\u0430\u043d\u043d\u044b\u0435 \u044d\u043b\u0435\u043c\u0435\u043d\u0442\u0430 \u043a\u043e\u0440\u0437\u0438\u043d\u044b', 'color: blue; font-weight: bold')";
$jsDebug[] = "console.log('ID \u0442\u043e\u0432\u0430\u0440\u0430:', {$arElement['PRODUCT_ID']}, '\nBasket ID:', {$arElement['BASKET_ID']})";
$jsDebug[] = "console.log('%c\u0417\u043d\u0430\u0447\u0435\u043d\u0438\u044f \u043c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u0438', 'color: green; font-weight: bold')";
$jsDebug[] = "console.log('\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f:', ".($__modName ? "'".$__modName."'" : 'null').", '\n\u0426\u0435\u043d\u0430 \u043c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u0438:', ".($__modPrice ? $__modPrice : 'null').");";

// Попробуем получить данные напрямую из CSaleBasket
if (!empty($arElement['BASKET_ID']) && \Bitrix\Main\Loader::includeModule('sale')) {
    // Очищаем переменные модификации перед поиском новых значений
    $__modName = '';
    $__modPrice = null;
    $__isRealModification = false;

    // Получаем сам товар в корзине для проверки
    $basketItem = CSaleBasket::GetByID($arElement['BASKET_ID']);
    if ($basketItem && $basketItem['PRODUCT_ID'] == $arElement['PRODUCT_ID']) {
        $jsDebug[] = "console.log('%cНайден текущий товар в корзине ID #' + '{$arElement['BASKET_ID']}', 'color: blue; font-weight: bold');";        
        
        // Получаем свойства товара напрямую из БД
        $basketPropsDb = \Bitrix\Sale\Internals\BasketPropertyTable::getList([
            'filter' => [
                'BASKET_ID' => $arElement['BASKET_ID']
            ]
        ]);
        
        // Проверяем свойства на наличие модификации
        $directProps = [];
        $foundDirect = false;
        $hasModification = false;
        
        while ($prop = $basketPropsDb->fetch()) {
            $directProps[] = $prop;
            $foundDirect = true;
            
            // Логируем все найденные свойства для отладки
            $jsDebug[] = "console.log('%cНайдено свойство корзины', 'color: purple');";  
            $jsDebug[] = "console.log('{$prop['CODE']}', '" . addslashes($prop['VALUE']) . "');";  
            
            if ($prop['CODE'] == 'MODIFICATION' && !empty($prop['VALUE'])) {
                $__modName = $prop['VALUE'];
                $hasModification = true;
                $__isRealModification = true; // Помечаем как реальную модификацию
                $jsDebug[] = "console.log('%cНАЙДЕНА МОДИФИКАЦИЯ ДЛЯ ТОВАРА #' + '{$arElement['PRODUCT_ID']}' + ': ' + '" . addslashes($prop['VALUE']) . "', 'color: green; font-weight: bold');";
            }
            if ($prop['CODE'] == 'MODIFICATION_PRICE' && !empty($prop['VALUE'])) {
                $__modPrice = (float)str_replace(array(' ', "\xC2\xA0", "\u00A0"), '', (string)$prop['VALUE']);
                $jsDebug[] = "console.log('%c\u041d\u0410\u0419\u0414\u0415\u041d\u0410 \u0426\u0415\u041d\u0410 \u041c\u041e\u0414\u0418\u0424\u0418\u041a\u0410\u0426\u0418\u0418 \u0447\u0435\u0440\u0435\u0437 \u0411\u0414: ' + '{$__modPrice}', 'color: green; font-weight: bold');";
            }
        }
        
        if (!$hasModification) {
            // Если модификации не найдены, сбрасываем переменные чтобы не было ложных срабатываний
            $__modName = '';
            $__modPrice = null;
            $__isRealModification = false;
            $jsDebug[] = "console.log('%c\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u041d\u0415 \u043d\u0430\u0439\u0434\u0435\u043d\u0430 \u0434\u043b\u044f \u0442\u043e\u0432\u0430\u0440\u0430 #' + '{$arElement['PRODUCT_ID']}', 'color: red');"; 
        }
    }
    else {
        // Если товар не соответствует, сбрасываем модификацию
        $__modName = '';
        $__modPrice = null;
        $__isRealModification = false;
        $jsDebug[] = "console.log('%c\u0422\u043e\u0432\u0430\u0440 \u0432 \u043a\u043e\u0440\u0437\u0438\u043d\u0435 \u043d\u0435 \u0441\u043e\u043e\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u0435\u0442 \u0442\u0435\u043a\u0443\u0449\u0435\u043c\u0443 #' + '{$arElement['PRODUCT_ID']}', 'color: red');";  
    }
    
    if (!$foundDirect) {
        $jsDebug[] = "console.log('%c\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 \u043a\u043e\u0440\u0437\u0438\u043d\u044b \u043d\u0435 \u043d\u0430\u0439\u0434\u0435\u043d\u044b \u0434\u043b\u044f #' + '{$arElement['BASKET_ID']}', 'color: orange');";
    } else {
        $jsDebug[] = "console.log('CSaleBasket: \u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 \u043d\u0430\u0439\u0434\u0435\u043d\u044b');";
    }
}
					}

					// 3) Поиск в PROPERTIES если не нашли модификацию ранее
					if ($__modName === '' && isset($arElement['PROPERTIES']['MODIFICATION']['VALUE'])) {
						$__modName = (string)$arElement['PROPERTIES']['MODIFICATION']['VALUE'];
						if(!empty($__modName)) {
							$__isRealModification = true; // Устанавливаем флаг модификации
							$jsDebug[] = "console.log('%c\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u043d\u0430\u0439\u0434\u0435\u043d\u0430 \u0432 PROPERTIES: ' + '" . addslashes($__modName) . "', 'color: green; font-weight: bold')";
						}
					}
					
					$jsDebug[] = "console.groupEnd()";
						if ($__modPrice === null && isset($arElement['PROPERTIES']['MODIFICATION_PRICE']['VALUE'])) {
							$__modPrice = (float)$arElement['PROPERTIES']['MODIFICATION_PRICE']['VALUE'];
						}

						if ($__isRealModification && $__modName !== ''): ?>
							<div class="itemModification" style="background-color: #f0f8ff; padding: 6px; margin: 5px 0; border-radius: 4px; border: 1px solid #cce">
								<div class="modificationName" style="font-weight: 600; margin-bottom: 3px;"><span class="modLabel" style="color: #555;">Модификация:</span> <span class="modValue" style="color: #0066cc;"><?=htmlspecialcharsbx($__modName)?></span></div>
								<? if ($__modPrice !== null): ?>
									<div class="modificationPrice" style="font-size: 0.9em;">Цена модификации: <b><?=FormatCurrency($__modPrice, isset($arResult['CURRENCY']['CODE']) ? $arResult['CURRENCY']['CODE'] : ($arElement['CURRENCY'] ?? \Bitrix\Currency\CurrencyManager::getBaseCurrency()));?></b></div>
								<? endif; ?>
							</div>
						<? else: ?>
							<?
							// Отладка: если модификация не найдена, выведем свойства позиции корзины в HTML-комментарий
							if (!empty($arElement['BASKET_ID']) && \Bitrix\Main\Loader::includeModule('sale')) {
								$propsDump = [];
								$propsRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList([
									'filter' => ['BASKET_ID' => $arElement['BASKET_ID']],
									'select' => ['CODE','VALUE']
								]);
								while ($prop = $propsRes->fetch()) {
									$propsDump[] = sprintf('%s=%s', (string)($prop['CODE'] ?? ''), (string)($prop['VALUE'] ?? ''));
								}
								if (!empty($propsDump)) {
									echo "\n<!-- basket-props: ".htmlspecialcharsbx(implode('; ', $propsDump))." -->\n";
								}
							}
							?>
						<? endif; ?>
						<?if($arElement["COUNT_PRICES"] > 1):?>
							<a href="#" class="price getPricesWindow" data-id="<?=$arElement["ID"]?>">
								<span class="priceIcon"></span><span class="priceContainer" data-price="<?=$arElement["PRICE"];?>"><?=$arElement["PRICE_FORMATED"];?></span>
								<?if($arParams["HIDE_MEASURES"] != "Y" && !empty($arResult["MEASURES"][$arElement["CATALOG_MEASURE"]]["SYMBOL_RUS"])):?>
									<span class="measure"> / <?=$arResult["MEASURES"][$arElement["CATALOG_MEASURE"]]["SYMBOL_RUS"]?></span>
								<?endif;?>
			  					<s class="discount"><span class="discountContainer<?if(empty($arElement["DISCOUNT"])):?> hidden<?endif;?>"><?=$arElement["BASE_PRICE_FORMATED"]?></span></s>
			  				</a>
						<?else:?>
							<a class="price">
								<span class="priceContainer" data-price="<?=$arElement["PRICE"];?>"><?=$arElement["PRICE_FORMATED"];?></span>
								<?if($arParams["HIDE_MEASURES"] != "Y" && !empty($arResult["MEASURES"][$arElement["CATALOG_MEASURE"]]["SYMBOL_RUS"])):?>
									<span class="measure"> / <?=$arResult["MEASURES"][$arElement["CATALOG_MEASURE"]]["SYMBOL_RUS"]?></span>
								<?endif;?>
			  					<s class="discount"><span class="discountContainer<?if(empty($arElement["DISCOUNT"])):?> hidden<?endif;?>"><?=$arElement["BASE_PRICE_FORMATED"]?></span></s>
			  				</a>
						<?endif;?>
						<div class="basketQty">
							<?=GetMessage("BASKET_QUANTITY_LABEL")?> <a href="#" class="minus" data-id="<?=$arElement["BASKET_ID"]?>"></a>
							<input name="qty" type="text" value="<?=$arElement["QUANTITY"]?>" class="qty"<?if($arElement["CATALOG_QUANTITY_TRACE"] == "Y" && $arElement["CATALOG_CAN_BUY_ZERO"] == "N"):?> data-last-value="<?=$arElement["QUANTITY"]?>" data-max-quantity="<?=$arElement["CATALOG_QUANTITY"]?>"<?endif;?> data-id="<?=$arElement["BASKET_ID"]?>" data-ratio="<?=$arElement["CATALOG_MEASURE_RATIO"]?>" />
							<a href="#" class="plus" data-id="<?=$arElement["BASKET_ID"]?>"></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?endforeach;?>
	
	<?php if (!empty($jsDebug)): ?>
	<!-- Вывод всех собранных отладочных сообщений в конце страницы -->
	<script>
	<?php foreach ($jsDebug as $jsLine): ?>
		<?= $jsLine; ?>;
	<?php endforeach; ?>
	</script>
	<?php endif; ?>
	<?if($arParams["DISABLE_FAST_ORDER"] !== "Y"):?>
		<div class="item product fastBayContainer<?if(empty($arResult["IS_MIN_ORDER_AMOUNT"])):?> hidden<?endif;?>">
			<div class="tb">
				<div class="tc">
					<img class="fastBayImg" src="<?=SITE_TEMPLATE_PATH?>/images/basketProductListCart.svg" alt="<?=GetMessage("FAST_BUY_PRODUCT_LABEL")?>">
					<div class="fastBayLabel"><?=GetMessage("FAST_BUY_PRODUCT_LABEL")?></div>
					<div class="fastBayText"><?=GetMessage("FAST_BUY_PRODUCT_TEXT")?></div>
					<a href="#" class="show-always btn-simple btn-micro" id="fastBasketOrder"><?=GetMessage("FAST_BUY_PRODUCT_BTN_TEXT")?></a>
				</div>
			</div>
		</div>
	<?endif;?>
	<div class="clear"></div>
</div>