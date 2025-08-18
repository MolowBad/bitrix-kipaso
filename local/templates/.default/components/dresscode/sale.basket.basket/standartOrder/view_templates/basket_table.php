<table class="productTable">
	<thead>
		<tr>
			<th><?=GetMessage("TOP_IMAGE")?></th>
			<th><?=GetMessage("TOP_NAME")?></th>
			<th><?=GetMessage("TOP_QTY")?></th>
			<th><?=GetMessage("TOP_AVAILABLE")?></th>
			<th><?=GetMessage("TOP_PRICE")?></th>
			<th><?=GetMessage("TOP_DELETE")?></th>
		</tr>
	</thead>
	<tbody>
		<?foreach ($arResult["ITEMS"] as $key => $arElement):?>
		<?$countPos += $arElement["QUANTITY"] ?>
			<tr class="basketItemsRow parent" data-product-iblock-id="<?=$arElement["IBLOCK_ID"]?>" data-id="<?=$arElement["ID"]?>" data-cart-id="<?=$arElement["ID"]?>">
				<td>
					<a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="pic" target="_blank">
				    	<img src="<?=!empty($arElement["PICTURE"]["src"]) ? $arElement["PICTURE"]["src"] : SITE_TEMPLATE_PATH."/images/empty.svg"?>" alt="<?=$arElement["NAME"]?>">
				    </a>
				</td>
				<td class="name">
					<a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="name" target="_blank"><?=$arElement["NAME"]?></a>
					<?
					// Определение модификации и цены модификации
					$__modName = '';
					$__modPrice = null;
					$__isRealModification = false; // Флаг, показывающий, является ли товар реальной модификацией

					// DEBUG: Выводим отладочную информацию о товаре в консоль браузера
					$basketId = isset($arElement['BASKET_ID']) ? $arElement['BASKET_ID'] : 'Нет BASKET_ID';
					$elementId = $arElement['ID'];
					$keysArray = array_keys($arElement);
					$propsFound = !empty($arElement['PROPS']) && is_array($arElement['PROPS']);
					$propsArray = $propsFound ? $arElement['PROPS'] : [];

					echo '<script>';
					echo "console.group('\u041e\u0442\u043b\u0430\u0434\u043a\u0430 \u043c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u0439 - ID \u0442\u043e\u0432\u0430\u0440\u0430: {$elementId} / \u041a\u043e\u0440\u0437\u0438\u043d\u0430 ID: {$basketId}');";
					echo "console.log('\u041a\u043b\u044e\u0447\u0438 \u044d\u043b\u0435\u043c\u0435\u043d\u0442\u0430:', ['" . implode("', '", $keysArray) . "']);";
					echo "console.log('\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 PROPS:', '" . ($propsFound ? "\u041d\u0410\u0419\u0414\u0415\u041d\u042b" : "\u041d\u0415 \u041d\u0410\u0419\u0414\u0415\u041d\u042b") . "');";
					
					if ($propsFound) {
						// Формируем массив в формате JavaScript
						echo "console.log('\u0421одержимое PROPS:');";
						echo "console.table([";
						$first = true;
						foreach ($propsArray as $prop) {
							if (!$first) {
								echo ",";
							}
							echo "{";
							$propFirst = true;
							foreach ($prop as $key => $value) {
								if (!$propFirst) {
									echo ",";
								}
								echo "\"$key\":\"" . addslashes($value) . "\"";
								$propFirst = false;
							}
							echo "}";
							$first = false;
						}
						echo "]);";
					}
					echo '</script>';


					// 1) Прямой доступ к свойствам корзины
					if (!empty($arElement['BASKET_ID']) && \Bitrix\Main\Loader::includeModule('sale')) {
						echo '<p>Загружаем свойства корзины из БД по BASKET_ID: ' . $arElement['BASKET_ID'] . '</p>';
						
						// Принудительная очистка кеша корзины для гарантированного получения актуальных данных
						try {
							\Bitrix\Main\Application::getInstance()->getTaggedCache()->clearByTag('sale_basket');
							\Bitrix\Main\Data\Cache::createInstance()->clean("sale_basket_".\Bitrix\Sale\Fuser::getId(), "/sale/basket");
						} catch (\Exception $e) {
							echo '<p style="color: orange;">Игнорируем ошибки очистки кеша: ' . $e->getMessage() . '</p>';
						}
						
						// Попытка 1: CSaleBasket - самый прямой способ получения свойств
						$basketItem = CSaleBasket::GetByID($arElement['BASKET_ID']);
						if ($basketItem && $basketItem['PRODUCT_ID'] == $arElement['PRODUCT_ID']) {
							echo '<p style="color: green;"><b>Найден текущий товар в корзине ID #' . $arElement['BASKET_ID'] . '</b></p>';
							
							// Получаем свойства корзины через CSaleBasket
							$basketProps = [];
							$dbBasketProps = CSaleBasket::GetPropsList(array("SORT" => "ASC"), array("BASKET_ID" => $arElement['BASKET_ID']));
							
							echo '<p><b>Свойства через CSaleBasket:</b></p><ul>';
							$propCount = 0;
							while ($prop = $dbBasketProps->Fetch()) {
								$basketProps[] = $prop;
								echo '<li>' . $prop['CODE'] . ' = ' . $prop['VALUE'] . '</li>';
								$propCount++;
								
								if ($prop['CODE'] == 'MODIFICATION' && !empty($prop['VALUE'])) {
									$__modName = $prop['VALUE'];
									$__isRealModification = true;
									echo '<li style="color: green; font-weight: bold;">НАЙДЕНА МОДИФИКАЦИЯ: ' . $__modName . '</li>';
								}
								if ($prop['CODE'] == 'MODIFICATION_PRICE' && !empty($prop['VALUE'])) {
									$__modPrice = (float)str_replace([' ', '\u00A0', "\xC2\xA0"], '', (string)$prop['VALUE']);
									echo '<li style="color: green; font-weight: bold;">НАЙДЕНА ЦЕНА МОДИФИКАЦИИ: ' . $__modPrice . '</li>';
								}
							}
							echo '</ul>';
							
							if ($propCount == 0) {
								echo '<p style="color: red;"><b>Свойства не найдены через CSaleBasket!</b></p>';
							}
						} else {
							echo '<p style="color: red;"><b>Элемент корзины не найден по ID: ' . $arElement['BASKET_ID'] . '</b></p>';
						}
						
						// Попытка 2: Напрямую из БД свойств корзины
						$basketProperties = [];
						$propsRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList([
							'filter' => [
								'BASKET_ID' => $arElement['BASKET_ID']
							],
							'select' => ['CODE', 'VALUE', 'ID', 'NAME']
						]);

						echo '<p><b>Свойства корзины из БД:</b></p>';
						echo '<ul>';
						$propCount = 0;
						while ($prop = $propsRes->fetch()) {
							$propCount++;
							echo '<li><b>' . $prop['CODE'] . '</b> (ID: ' . $prop['ID'] . ', NAME: ' . $prop['NAME'] . '): ' . $prop['VALUE'] . '</li>';
							$basketProperties[$prop['CODE']] = $prop['VALUE'];
						}
						
						if ($propCount === 0) {
							echo '<li style="color: red;">Нет свойств в БД!</li>';
						}
						echo '</ul>';

						if (!empty($basketProperties['MODIFICATION'])) {
							$__modName = (string)$basketProperties['MODIFICATION'];
							echo '<p style="color: green;"><b>НАЙДЕНА МОДИФИКАЦИЯ В БД:</b> ' . $__modName . '</p>';
						} else {
							echo '<p style="color: red;"><b>Модификация НЕ найдена в БД!</b></p>';
						}

						if (!empty($basketProperties['MODIFICATION_PRICE'])) {
							$__modPrice = (float)str_replace([' ', '\u00A0', "\xC2\xA0"], '', (string)$basketProperties['MODIFICATION_PRICE']);
							echo '<p style="color: green;"><b>НАЙДЕНА ЦЕНА МОДИФИКАЦИИ В БД:</b> ' . $__modPrice . '</p>';
						} else {
							echo '<p style="color: red;"><b>Цена модификации НЕ найдена в БД!</b></p>';
						}
					} else {
						echo '<p style="color: red;"><b>Нет ID корзины или модуль sale не подключен!</b></p>';
					}

					// 2) Поиск в PROPS (резерв)
					if (empty($__modName) && !empty($arElement["PROPS"])) {
						echo '<p><b>Поиск модификаций в PROPS:</b></p>';
						foreach ($arElement["PROPS"] as $property) {
							if ($property["CODE"] == "MODIFICATION") {
								$__modName = $property["VALUE"];
								echo '<p style="color: blue;"><b>НАЙДЕНА МОДИФИКАЦИЯ В PROPS:</b> ' . $__modName . '</p>';
							} elseif ($property["CODE"] == "MODIFICATION_PRICE") {
								$__modPrice = (float)str_replace([' ', '\u00A0', "\xC2\xA0"], '', (string)$property["VALUE"]);
								echo '<p style="color: blue;"><b>НАЙДЕНА ЦЕНА МОДИФИКАЦИИ В PROPS:</b> ' . $__modPrice . '</p>';
							}
						}
						if (empty($__modName)) {
							echo '<p style="color: red;"><b>Модификация НЕ найдена в PROPS!</b></p>';
						}
					}

					// Если в отладке видно, что свойства есть в БД, но не в PROPS, то попробуем получить их напрямую
					if (!empty($arElement['BASKET_ID']) && \Bitrix\Main\Loader::includeModule('sale')) {
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
									
									// Отладка для API модели
									echo '<script>';
									echo "console.log('API-модель корзины: Элемент найден #" . $arElement['BASKET_ID'] . "')";
									echo '</script>';
									
									// Ищем свойства
									$foundApiProps = false;
									foreach ($basketProperties as $property) {
										$foundApiProps = true;
										if ($property['CODE'] == 'MODIFICATION' && !empty($property['VALUE'])) {
											$__modName = $property['VALUE'];
											echo '<script>';
											echo "console.log('НАЙДЕНА модификация через API: " . addslashes($property['VALUE']) . "')";
											echo '</script>';
										}
									}
									
									if (!$foundApiProps) {
										echo '<script>';
										echo "console.log('API-модель: Свойства не найдены')";
										echo '</script>';
									}
								}
							} catch (\Exception $e) {
								echo '<script>';
								echo "console.log('Ошибка при работе с API корзины: " . addslashes($e->getMessage()) . "')";
								echo '</script>';
							}
						}
					}
					
					// Выводим свойства БД в консоль
					echo '<script>';
					echo "console.log('\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 \u0438\u0437 \u0411\u0414:', '" . (!empty($dbProps) ? "\u041d\u0410\u0419\u0414\u0415\u041d\u042b" : "\u041d\u0415 \u041d\u0410\u0419\u0414\u0415\u041d\u042b") . "');";
					if (!empty($dbProps)) {
						// Формируем массив в формате JavaScript
						echo "console.log('\u0421\u0432\u043e\u0439\u0441\u0442\u0432\u0430 \u0438\u0437 \u0411\u0414:');";
						echo "console.table([";
						$first = true;
						foreach ($dbProps as $prop) {
							if (!$first) {
								echo ",";
							}
							echo "{";
							$propFirst = true;
							foreach ($prop as $key => $value) {
								if (!$propFirst) {
									echo ",";
								}
								echo "\"$key\":\"" . addslashes($value) . "\"";
								$propFirst = false;
							}
							echo "}";
							$first = false;
						}
						echo "]);";
					}
					echo '</script>';
					
					// Выводим информацию о модификации, если она есть
					if(!empty($__modName)){
						echo '<span class="label">'.$__modName.'</span>';
						echo '<script>console.log("\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u043d\u0430\u0439\u0434\u0435\u043d\u0430 \u0438 \u043e\u0442\u043e\u0431\u0440\u0430\u0436\u0435\u043d\u0430 (\u0442\u0430\u0431\u043b\u0438\u0446\u0430): ' . addslashes($__modName) . '");</script>';
					} else {
						echo '<script>console.log("\u041c\u043e\u0434\u0438\u0444\u0438\u043a\u0430\u0446\u0438\u044f \u041d\u0415 \u043d\u0430\u0439\u0434\u0435\u043d\u0430 (\u0442\u0430\u0431\u043b\u0438\u0446\u0430)");</script>';
					}

					// 3) Поиск в PROPERTIES
					if ($__modName === '' && isset($arElement['PROPERTIES']['MODIFICATION']['VALUE'])) {
						$__modName = (string)$arElement['PROPERTIES']['MODIFICATION']['VALUE'];
					}
					if ($__modPrice === null && isset($arElement['PROPERTIES']['MODIFICATION_PRICE']['VALUE'])) {
						$__modPrice = (float)$arElement['PROPERTIES']['MODIFICATION_PRICE']['VALUE'];
					}

					// 4) Прямые поля
					if ($__modName === '' && !empty($arElement['MODIFICATION'])) {
						$__modName = (string)$arElement['MODIFICATION'];
					}
					if ($__modPrice === null && isset($arElement['MODIFICATION_PRICE'])) {
						$__modPrice = (float)$arElement['MODIFICATION_PRICE'];
					}

					if ($__modName !== ''): ?>
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
				</td>
				<td class="bQty">
					<div class="basketQty">
						<a href="#" class="minus" data-id="<?=$arElement["BASKET_ID"]?>"></a>
						<input name="qty" type="text" value="<?=$arElement["QUANTITY"]?>" class="qty"<?if($arElement["CATALOG_QUANTITY_TRACE"] == "Y" && $arElement["CATALOG_CAN_BUY_ZERO"] == "N"):?> data-last-value="<?=$arElement["QUANTITY"]?>" data-max-quantity="<?=$arElement["CATALOG_QUANTITY"]?>"<?endif;?> data-id="<?=$arElement["BASKET_ID"]?>" data-ratio="<?=$arElement["CATALOG_MEASURE_RATIO"]?>" />
						<a href="#" class="plus" data-id="<?=$arElement["BASKET_ID"]?>"></a>
					</div>
				</td>
				<td>
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
        		</td>
				<td>
					<a class="price">
						<span class="priceContainer" data-price="<?=$arElement["PRICE"];?>"><?=$arElement["PRICE_FORMATED"];?></span>
						<?if($arParams["HIDE_MEASURES"] != "Y" && !empty($arResult["MEASURES"][$arElement["CATALOG_MEASURE"]]["SYMBOL_RUS"])):?>
							<span class="measure"> / <?=$arResult["MEASURES"][$arElement["CATALOG_MEASURE"]]["SYMBOL_RUS"]?></span>
						<?endif;?>
	  					<s class="discount"><span class="discountContainer<?if(empty($arElement["DISCOUNT"])):?> hidden<?endif;?>"><?=$arElement["BASE_PRICE_FORMATED"]?></span></s>
	  				</a>
  				</td>
				<td class="elementDelete"><a href="#" class="delete" data-id="<?=$arElement["BASKET_ID"]?>"></a></td>
			</tr>
		<?endforeach;?>
	</tbody>
</table>