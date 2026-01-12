<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? $this->setFrameMode(false); ?>
<? if (!empty($arResult)): ?>
	<?
	// Определяем какое название выводить
	$displayName = $arResult["NAME"]; // По умолчанию название товара

	// Проверяем тип товара - если это торговое предложение (CATALOG_TYPE > 1)
	if ($arResult["CATALOG_TYPE"] > 1) {
		// Это торговое предложение - используем полное название из свойства "modific"
		$displayName = !empty($arResult["PROPERTIES"]["modific"]["VALUE"])
			? $arResult["PROPERTIES"]["modific"]["VALUE"]
			: $arResult["NAME"];
	}
	// Или проверяем наличие связи с основным товаром
	elseif (!empty($arResult["PROPERTIES"]["CML2_LINK"]["VALUE"])) {
		// Это также может быть торговым предложением
		$displayName = !empty($arResult["PROPERTIES"]["modific"]["VALUE"])
			? $arResult["PROPERTIES"]["modific"]["VALUE"]
			: $arResult["NAME"];
	}
	?>

	<div id="appBasket" data-load="<?= SITE_TEMPLATE_PATH ?>/images/picLoad.gif">
		<div id="appBasketContainer">
			<div class="heading"><?= GetMessage("BASKET_WINDOW_PRODUCT_ADDED") ?> <a href="#" class="close closeWindow"></a></div>
			<div class="container">
				<? if (!empty($arResult["PROPERTIES"]["OFFERS"]["VALUE"])): ?>
					<div class="markerContainer">
						<? foreach ($arResult["PROPERTIES"]["OFFERS"]["VALUE"] as $ifv => $marker): ?>
							<div class="marker" style="background-color: <?= strstr($arResult["PROPERTIES"]["OFFERS"]["VALUE_XML_ID"][$ifv], "#") ? $arResult["PROPERTIES"]["OFFERS"]["VALUE_XML_ID"][$ifv] : "#424242" ?>"><?= $marker ?></div>
						<? endforeach; ?>
					</div>
				<? endif; ?>
				<div class="rating">
					<i class="m" style="width:<?= (intval($arResult["PROPERTIES"]["RATING"]["VALUE"]) * 100 / 5) ?>%"></i>
					<i class="h"></i>
				</div>
				<div class="picture">
					<a href="<?= $arResult["DETAIL_PAGE_URL"] ?>" class="moreLink"><img src="<?= $arResult["PICTURE"]["src"] ?>" alt="<?= $displayName ?>" title="<?= $displayName ?>" class="image" data-load="<?= SITE_TEMPLATE_PATH ?>/images/picLoad.gif"></a>
				</div>
				<div class="information">
					<div class="wrapper">
						<a href="<?= $arResult["DETAIL_PAGE_URL"] ?>" class="name moreLink"><?= $displayName ?></a>
						<a class="price">
							<!-- <span class="priceContainer"><?= $arResult["PRICE_FORMATED"]; ?></span>
							<? if ($arParams["HIDE_MEASURES"] != "Y" && !empty($arResult["MEASURES"][$arResult["CATALOG_MEASURE"]]["SYMBOL_RUS"])): ?>
								<span class="measure"> / <?= $arResult["MEASURES"][$arResult["CATALOG_MEASURE"]]["SYMBOL_RUS"] ?></span>
							<? endif; ?> -->
							<? if (!empty($arResult["DISCOUNT"])): ?>
								<s class="discount"><?= $arResult["BASE_PRICE_FORMATED"] ?></s>
							<? endif; ?>
						</a>
						<!-- Для текстового отображения "В наличии" "Под заказ"  добавил Егор-->
						<div class="availabilityInfo <?= ($arResult["CATALOG_QUANTITY"] > 0) ? 'inStock' : (($arResult["CATALOG_AVAILABLE"] == 'Y') ? 'onOrder' : 'outOfStock') ?>">
							<?= ($arResult["CATALOG_QUANTITY"] > 0) ? 'В наличии:' . ' ' . $arResult["CATALOG_QUANTITY"] : (($arResult["CATALOG_AVAILABLE"] == 'Y') ? 'Под заказ' : 'Нет в наличии') ?>
						</div>
						<!-- Для текстового отображения "В наличии" "Под заказ"  добавил Егор-->
						<div class="qtyBlock">
							<label class="label"><?= GetMessage("BASKET_WINDOW_PRODUCT_QUANTITY") ?></label>
							<a href="#" class="minus" data-id="<?= $arResult["BASKET_ID"] ?>"></a><input name="qty" type="text" value="<?= $arResult["QUANTITY"] ?>" class="qty" <? if ($arResult["CATALOG_QUANTITY_TRACE"] == "Y" && $arResult["CATALOG_CAN_BUY_ZERO"] == "N"): ?> data-last-value="<?= $arResult["QUANTITY"] ?>" data-max-quantity="<?= $arResult["CATALOG_QUANTITY"] ?>" <? endif; ?> data-id="<?= $arResult["BASKET_ID"] ?>" data-ratio="<?= $arResult["CATALOG_MEASURE_RATIO"] ?>" /><a href="#" class="plus" data-id="<?= $arResult["BASKET_ID"] ?>"></a>
						</div>
						<div class="sum">
							<?= GetMessage("BASKET_WINDOW_PRODUCT_TOTAL") ?> <span class="allSum"><?= $arResult["SUM_FORMATED"]; ?><? if (!empty($arResult["DISCOUNT"])): ?><s class="discount"><?= $arResult["BASE_SUM_FORMATED"]; ?></s></span><? endif; ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="lower">
				<table class="tools">
					<tr>
						<!-- <td class="icon"><a href="#" class="addCompare" data-no-label="Y" data-id="<?= $arResult["PRODUCT_ID"] ?>"><span class="ico"></span></a></td> -->
						<!-- <td class="icon"><a href="#" class="addWishlist" data-no-label="Y" data-id="<?= $arResult["PRODUCT_ID"] ?>"><span class="ico"></span></a></td> -->
						<? if ($arResult["CATALOG_QUANTITY"] > 0): ?>
							<!-- <td class="icon"><a class="availability inStock"><span class="ico"></span></a></td> -->
						<? else: ?>
							<? if (!empty($arResult["CATALOG_AVAILABLE"]) && $arResult["CATALOG_AVAILABLE"] == "Y"): ?>
								<!-- <td class="icon"><a class="availability onOrder"><span class="ico"></span></a></td> -->
							<? else: ?>
								<!-- <td class="icon"><a class="availability outOfStock"><span class="ico"></span></a></td> -->
							<? endif; ?>
						<? endif; ?>
						<td class="icon"><a href="#" class="delete" data-id=<?= $arResult["BASKET_ID"] ?>><span class="ico"></span><span class="text"><?= GetMessage("BASKET_WINDOW_DELETE") ?: "Удалить из корзины" ?></span></a></td>
						<td class="continue"><a href="#" class="closeWindow"><img src="<?= SITE_TEMPLATE_PATH ?>/images/continue.png" alt=""><span class="text"><?= GetMessage("BASKET_WINDOW_PRODUCT_CONTINUE") ?></span></a></td>
						<td class="goToBasket"><a href="<?= SITE_DIR ?>personal/cart/"><img src="<?= SITE_TEMPLATE_PATH ?>/images/goToBasket.png" alt=""><span class="text"><?= GetMessage("BASKET_WINDOW_GO_TO_ORDER") ?></span></a></td>
					</tr>
				</table>
			</div>
		</div>
		<script>
			var appBasketAjaxDir = "<?= $componentPath ?>";
			var appBasketSiteId = "<?= $arParams["SITE_ID"] ?>";
		</script>
		<script src="<?= $templateFolder ?>/fast_script.js"></script>
	</div>
<? endif; ?>