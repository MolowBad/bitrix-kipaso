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
							$__modName = '';
							$__tryReadVal = function($p){
								if (isset($p['DISPLAY_VALUE']) && is_string($p['DISPLAY_VALUE']) && trim($p['DISPLAY_VALUE']) !== '') return trim($p['DISPLAY_VALUE']);
								$v = $p['VALUE'] ?? '';
								if (is_array($v)) {
									// common shapes: ['VALUE' => '...'] or ['NAME' => '...'] or first scalar
									if (isset($v['VALUE']) && is_string($v['VALUE'])) return trim($v['VALUE']);
									if (isset($v['NAME']) && is_string($v['NAME'])) return trim($v['NAME']);
									foreach ($v as $vv) { if (is_string($vv) && trim($vv) !== '') return trim($vv); }
									return '';
								}
								return is_string($v) ? trim($v) : '';
							};
							// 1) PROPS
							if (empty($__modName) && !empty($arElement['PROPS']) && is_array($arElement['PROPS'])) {
								foreach ($arElement['PROPS'] as $__p) {
									$code = isset($__p['CODE']) ? strtoupper(trim((string)$__p['CODE'])) : '';
									$name = isset($__p['NAME']) ? trim((string)$__p['NAME']) : '';
									$match = ($code === 'MODIFICATION') || (mb_strtolower($name) === 'модификация');
									if ($match) {
										$val = $__tryReadVal($__p);
										if ($val !== '') { $__modName = $val; break; }
									}
								}
							}
							// 2) PROPERTIES
							if (empty($__modName) && !empty($arElement['PROPERTIES']) && is_array($arElement['PROPERTIES'])) {
								foreach ($arElement['PROPERTIES'] as $__p) {
									$code = isset($__p['CODE']) ? strtoupper(trim((string)$__p['CODE'])) : '';
									$name = isset($__p['NAME']) ? trim((string)$__p['NAME']) : '';
									$match = ($code === 'MODIFICATION') || (mb_strtolower($name) === 'модификация');
									if ($match) {
										$val = $__tryReadVal($__p);
										if ($val !== '') { $__modName = $val; break; }
									}
								}
							}
							// 3) Direct field fallbacks
							if (empty($__modName)) {
								if (!empty($arElement['PROPERTY_MODIFICATION_VALUE']) && is_string($arElement['PROPERTY_MODIFICATION_VALUE'])) {
									$__modName = trim($arElement['PROPERTY_MODIFICATION_VALUE']);
								}
								elseif (!empty($arElement['MODIFICATION']) && is_string($arElement['MODIFICATION'])) {
									$__modName = trim($arElement['MODIFICATION']);
								}
							}

							// Ищем цену модификации
							$__modPrice = null;
							$__tryReadNum = function($p){
								$v = $p['VALUE'] ?? ($p['DISPLAY_VALUE'] ?? null);
								if (is_array($v)) {
									if (isset($v['VALUE']) && is_numeric($v['VALUE'])) return (float)$v['VALUE'];
									foreach ($v as $vv) { if (is_numeric($vv)) return (float)$vv; }
									return null;
								}
								return is_numeric($v) ? (float)$v : null;
							};
							if (!empty($arElement['PROPS']) && is_array($arElement['PROPS'])) {
								foreach ($arElement['PROPS'] as $__p) {
									$code = isset($__p['CODE']) ? strtoupper(trim((string)$__p['CODE'])) : '';
									$name = isset($__p['NAME']) ? trim((string)$__p['NAME']) : '';
									$match = ($code === 'MODIFICATION_PRICE') || (mb_strtolower($name) === 'цена модификации');
									if ($match) { $__modPrice = $__tryReadNum($__p); if ($__modPrice !== null) break; }
								}
							}
							if ($__modPrice === null && !empty($arElement['PROPERTIES']) && is_array($arElement['PROPERTIES'])) {
								foreach ($arElement['PROPERTIES'] as $__p) {
									$code = isset($__p['CODE']) ? strtoupper(trim((string)$__p['CODE'])) : '';
									$name = isset($__p['NAME']) ? trim((string)$__p['NAME']) : '';
									$match = ($code === 'MODIFICATION_PRICE') || (mb_strtolower($name) === 'цена модификации');
									if ($match) { $__modPrice = $__tryReadNum($__p); if ($__modPrice !== null) break; }
								}
							}
							if ($__modPrice === null) {
								if (isset($arElement['PROPERTY_MODIFICATION_PRICE_VALUE']) && is_numeric($arElement['PROPERTY_MODIFICATION_PRICE_VALUE'])) {
									$__modPrice = (float)$arElement['PROPERTY_MODIFICATION_PRICE_VALUE'];
								} elseif (isset($arElement['MODIFICATION_PRICE']) && is_numeric($arElement['MODIFICATION_PRICE'])) {
									$__modPrice = (float)$arElement['MODIFICATION_PRICE'];
								}
							}

							// 4) Резерв: читаем свойства позиции корзины по BASKET_ID
							if ((empty($__modName) || $__modPrice === null) && !empty($arElement['BASKET_ID'])) {
								if (class_exists('CSaleBasket')) {
									$propsRes = CSaleBasket::GetPropsList([], ["BASKET_ID" => $arElement['BASKET_ID']]);
									while ($prop = $propsRes->Fetch()) {
										$code = isset($prop['CODE']) ? strtoupper(trim((string)$prop['CODE'])) : '';
										if ($code === 'MODIFICATION' && empty($__modName)) {
											$val = (string)($prop['VALUE'] ?? '');
											if ($val !== '') { $__modName = $val; }
										}
										if ($code === 'MODIFICATION_PRICE' && $__modPrice === null) {
											$val = $prop['VALUE'] ?? null;
											if (is_numeric($val)) { $__modPrice = (float)$val; }
										}
									}
								}
							}
							if ($__modName !== ''): ?>
								<div class="itemModification">
									<div class="modificationName">Модификация: <span class="modValue"><?=htmlspecialcharsbx($__modName)?></span></div>
									<? if ($__modPrice !== null): ?>
										<div class="modificationPrice">Цена модификации: <?=FormatCurrency($__modPrice, isset($arResult['CURRENCY']['CODE']) ? $arResult['CURRENCY']['CODE'] : ($arElement['CURRENCY'] ?? \Bitrix\Currency\CurrencyManager::getBaseCurrency()));?></div>
									<? endif; ?>
								</div>
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