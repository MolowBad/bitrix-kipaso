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