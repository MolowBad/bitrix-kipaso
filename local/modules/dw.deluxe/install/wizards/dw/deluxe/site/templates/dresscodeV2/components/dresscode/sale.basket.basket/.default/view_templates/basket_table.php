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
				<td class="name"><a href="<?=$arElement["DETAIL_PAGE_URL"]?>" class="name" target="_blank"><?=$arElement["NAME"]?></a></td>
				<td class="bQty">
					<div class="basketQty">
						<a href="#" class="minus" data-id="<?=$arElement["BASKET_ID"]?>"></a>
						<input name="qty" type="text" value="<?=$arElement["QUANTITY"]?>" class="qty"<?if($arElement["CATALOG_QUANTITY_TRACE"] == "Y" && $arElement["CATALOG_CAN_BUY_ZERO"] == "N"):?> data-last-value="<?=$arElement["QUANTITY"]?>" data-max-quantity="<?=$arElement["CATALOG_QUANTITY"]?>"<?endif;?> data-id="<?=$arElement["BASKET_ID"]?>" data-ratio="<?=$arElement["CATALOG_MEASURE_RATIO"]?>" />
						<a href="#" class="plus" data-id="<?=$arElement["BASKET_ID"]?>"></a>
					</div>
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