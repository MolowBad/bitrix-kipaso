<?

	//check include core
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

	//composite mode
	$this->setFrameMode(true);

	//vars
	$deliveryIteration = 0;
	$groupIteration = 0;
	$maxElements = 3;

?>
<?if(!empty($arResult["DELIVERY_ITEMS"])):?>
	<div class="detail-deliveries-container">
		<div class="detail-deliveries-heading"><?=GetMessage("DELIVERY_CITY_LABEL")?><?if(!empty($_SESSION["USER_GEO_POSITION"]["city"])):?><?=GetMessage("DELIVERY_CITY_IN_LABEL")?> <a href="#" class="user-geo-position-value-link"><?=$_SESSION["USER_GEO_POSITION"]["city"]?></a><?endif;?></div>
		<?if(!empty($arResult["DELIVERY_GROUPS"])):?>
			<div class="detail-deliveries-tabs-container">
				<div class="detail-deliveries-tabs-scroll">
					<div class="detail-deliveries-tabs">
						<?foreach($arResult["DELIVERY_GROUPS"] as $nextDeliveryGroup):?>
							<div class="deliveries-tab-item<?if(++$groupIteration === 1):?> selected<?endif;?>">
								<a href="#" class="deliveries-tab-switcher" data-group-id="<?=$nextDeliveryGroup["ID"]?>"><?=$nextDeliveryGroup["NAME"]?></a>
							</div>
						<?endforeach;?>
					</div>
				</div>
			</div>
		<?endif;?>
		<div class="delivery-items-container">
			<div class="delivery-items">
				<?if(!empty($arResult["DELIVERY_GROUPS"]) && !($groupIteration = 0)):?>
					<?foreach($arResult["DELIVERY_GROUPS"] as $nextDeliveryGroup):?>
						<?if(!empty($nextDeliveryGroup["ITEMS"])):?> <?$deliveryIteration = 0;?>
							<div class="delivery-group-item<?if(++$groupIteration === 1):?> active<?endif;?>" data-group-id="<?=$nextDeliveryGroup["ID"]?>">
								<?foreach($nextDeliveryGroup["ITEMS"] as $nextDeliveryItem):?>
									<div class="delivery-item<?if(++$deliveryIteration > $maxElements):?> limit disabled<?endif;?>">
										<?printDeliveryItem($arResult["DELIVERY_ITEMS"][$nextDeliveryItem["ID"]], $arParams["PRODUCT_ID"], $templateFolder, $nextDeliveryGroup["ID"], $arParams);?>
									</div>
								<?endforeach;?>
								<?if($deliveryIteration > $maxElements):?>
									<div class="show-all-deliveries-container">
										<a href="#" class="show-all-deliveries btn-simple btn-small btn-border" data-state-text="<?=GetMessage("DELIVERY_HIDE_ITEMS");?>"><?=GetMessage("DELIVERY_SHOW_ALL_ITEMS");?></a>
									</div>
								<?endif;?>
							</div>
						<?endif;?>
					<?endforeach;?>
				<?else:?>
					<?foreach($arResult["DELIVERY_ITEMS"] as $ix => $arDeliveryItem):?>
						<div class="delivery-item<?if(++$deliveryIteration > $maxElements):?> limit disabled<?endif;?>">
							<?printDeliveryItem($arDeliveryItem, $arParams["PRODUCT_ID"], $templateFolder, 0, $arParams);?>
						</div>
					<?endforeach;?>
					<?if($deliveryIteration > $maxElements):?>
						<div class="show-all-deliveries-container">
							<a href="#" class="show-all-deliveries btn-simple btn-small btn-border" data-state-text="<?=GetMessage("DELIVERY_HIDE_ITEMS");?>"><?=GetMessage("DELIVERY_SHOW_ALL_ITEMS");?></a>
						</div>
					<?endif;?>
				<?endif;?>
			</div>
			<?$APPLICATION->IncludeFile(
				SITE_DIR."sect_detail_paysystems.php",
				array(),
				array(
					"NAME" => GetMessage("PAYSYSTEMS_TEXT_EDIT_TITLE"),
					"SHOW_BORDER" => true,
					"MODE" => "text"
				)
			);?>
		</div>
	</div>
<?endif;?>

<?function printDeliveryItem($arDeliveryItem = array(), $productId = 0, $templateFolder, $groupId, $arParams){?>
	<?if(!empty($arDeliveryItem)):?>
		<div class="delivery-item-container">
			<div class="delivery-item-table">
				<?if($arParams["SHOW_DELIVERY_IMAGES"] == "Y" && !empty($arDeliveryItem["LOGOTIP"])):?>
					<div class="delivery-item-picture delivery-item-column">
						<div class="delivery-item-image-container"><img src="<?=$arDeliveryItem["LOGOTIP"]["src"]?>" alt="<?=$arDeliveryItem["NAME"]?>" title="<?=$arDeliveryItem["NAME"]?>"></div>
					</div>
				<?endif;?>
				<div class="delivery-item-special delivery-item-column">
					<div class="delivery-item-name"><?=$arDeliveryItem["NAME"]?></div>
					<div class="delivery-item-price">
						<?if(empty($arDeliveryItem["PRICE"])):?>
							<?=GetMessage("DELIVERY_PRICE_FREE")?><?if(!empty($arDeliveryItem["BASE_PRICE"])):?> <s><?=$arDeliveryItem["BASE_PRICE_FORMATED"]?></s><?endif;?>
						<?else:?>
							<?=$arDeliveryItem["PRICE_FORMATED"]?><?if($arDeliveryItem["BASE_PRICE"] != $arDeliveryItem["PRICE"]):?><s><?=$arDeliveryItem["BASE_PRICE_FORMATED"]?></s><?endif;?>
						<?endif;?>
					</div>
				</div>
				<div class="delivery-item-buttons delivery-item-column">
					<div class="delivery-item-price-mobile">
						<?if(empty($arDeliveryItem["PRICE"])):?>
							<s><?=$arDeliveryItem["BASE_PRICE_FORMATED"]?></s><?=GetMessage("DELIVERY_PRICE_FREE")?><?if(!empty($arDeliveryItem["BASE_PRICE"])):?><?endif;?>
						<?else:?>
							<s><?=$arDeliveryItem["BASE_PRICE_FORMATED"]?></s><?=$arDeliveryItem["PRICE_FORMATED"]?><?if($arDeliveryItem["BASE_PRICE"] != $arDeliveryItem["PRICE"]):?><?endif;?>
						<?endif;?>
					</div>
					<?if(!empty($arDeliveryItem["PERIOD_DESCRIPTION"])):?>
						<div class="delivery-item-period theme-color">
							<svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="delivery-item-period-icon">
								<title><?=GetMessage("DELIVERY_PERIOD_LABEL")?></title>
								<path class="theme-background-svg" d="M16.865 5.31567C16.8281 5.23608 16.7905 5.15733 16.7511 5.07914C16.3517 4.27926 15.8331 3.5497 15.2191 2.91352C14.5711 2.24161 13.8165 1.67405 12.9818 1.2367C11.7665 0.599953 10.3825 0.240234 8.91208 0.240234C7.46926 0.240234 6.08017 0.585328 4.84014 1.2367C4.60136 1.36214 4.36792 1.49855 4.14095 1.64648C4.13701 1.64817 4.13364 1.6507 4.1297 1.6538C3.62542 1.98314 3.15151 2.36733 2.71557 2.80298C2.68098 2.83758 2.65061 2.87442 2.62445 2.91323H2.66776H4.98779C6.1457 2.16483 7.49767 1.76348 8.91208 1.76348C10.36 1.76348 11.7069 2.18536 12.8361 2.91323C13.7032 3.47236 14.4423 4.21148 15.0003 5.07886C15.3339 5.59608 15.6025 6.15886 15.796 6.75595C16.0176 7.43826 16.1402 8.16586 16.1484 8.92158C16.1489 8.94773 16.1489 8.97389 16.1489 8.99976C16.1489 9.54933 16.0882 10.084 15.9726 10.5981C15.9191 10.8369 15.8542 11.0712 15.7771 11.3004C15.4995 12.1346 15.074 12.9001 14.532 13.567C13.207 15.1971 11.1846 16.236 8.91208 16.236C7.99042 16.236 7.09548 16.0653 6.26354 15.7391C5.57954 15.4719 4.93801 15.0992 4.3592 14.6295L4.65958 14.3292C5.07414 13.9146 4.88317 13.2059 4.31814 13.0545L2.93495 12.6844L1.94889 12.42C1.6502 12.3402 1.36501 12.4465 1.18417 12.6481C1.02245 12.8287 0.944263 13.0858 1.01598 13.353L1.65076 15.7225C1.80292 16.2915 2.51307 16.4757 2.92482 16.0639L3.27695 15.7118C4.85139 17.0373 6.82492 17.7599 8.91179 17.7599C11.4068 17.7599 13.6531 16.7243 15.2467 15.0585C16.7497 13.4871 17.6713 11.3544 17.6713 9.00005C17.6716 7.68267 17.383 6.43448 16.865 5.31567Z" fill="#232A32"/>
								<path class="theme-background-svg" d="M9.1671 5.07916C9.03238 4.9585 8.85435 4.88538 8.65888 4.88538C8.46341 4.88538 8.28538 4.9585 8.15066 5.07916C7.99485 5.21894 7.89697 5.42172 7.89697 5.64728V9.40056C7.89697 9.77097 8.16163 10.0803 8.51291 10.1481C8.55988 10.1574 8.60882 10.1622 8.65888 10.1622H11.0979C11.5186 10.1622 11.8592 9.82075 11.8592 9.40028C11.8592 9.21887 11.7959 9.05266 11.6902 8.92159C11.5504 8.74891 11.3369 8.63838 11.0979 8.63838H9.42079V5.647C9.42079 5.42172 9.32263 5.21894 9.1671 5.07916Z" fill="#232A32"/>
								<path class="theme-background-svg"d="M1.8525 8.79333H0.328125C0.3315 9.34909 0.388312 9.91018 0.502219 10.4701H2.06372C1.92562 9.91102 1.85672 9.34852 1.8525 8.79333Z" fill="#232A32"/>
								<path class="theme-background-svg" d="M2.92631 4.95093H1.19241C0.934502 5.48643 0.731439 6.04837 0.587158 6.62802H2.16835C2.34863 6.03852 2.604 5.4749 2.92631 4.95093Z" fill="#232A32"/>
							</svg>
							<div class="delivery-item-period-value" title="<?=GetMessage("DELIVERY_PERIOD_LABEL")?>"><?=$arDeliveryItem["PERIOD_DESCRIPTION"]?></div>
						</div>
					<?endif;?>
					<?if(!empty($arDeliveryItem["DESCRIPTION"])):?>
						<a href="#" class="delivery-item-question"></a>
					<?endif;?>
					<?if(!empty($productId)):?>
						<a href="#" class="delivery-item-buy btn-simple btn-small" data-product-id="<?=$productId?>" data-delivery-id="<?=$arDeliveryItem["ID"]?>"><?=getButtonLabelByGroupId($groupId, $arParams);?></a>
					<?endif;?>
				</div>
			</div>
			<?if(!empty($arDeliveryItem["DESCRIPTION"])):?>
				<div class="delivery-item-description"><?=$arDeliveryItem["DESCRIPTION"]?></div>
			<?endif;?>
		</div>
	<?endif;?>
<?}

function getButtonLabelByGroupId($groupId, $arParams = array()){

	//check params
	if(!empty($arParams["GROUP_BUTTONS_LABELS"])){

		//each labels
		foreach($arParams["GROUP_BUTTONS_LABELS"] as $nextValue){

			//get property group name
			if(preg_match("/(\d{0,9})\[(.*)\]/", trim($nextValue), $groupName)){
				if(!empty($groupName[1]) && $groupName[1] == $groupId && !empty($groupName[2])){
					return $groupName[2];
				}
			}

		}

	}

	return  GetMessage("DELIVERY_BUY_WITH_CURRENT");

}?>

<?if($arParams["DEFERRED_MODE"] == "Y" && $arParams["PRODUCT_AVAILABLE"] == "Y"):?>
	<div class="detail-deliveries-loader">
		<div class="detail-deliveries-loader-image"><img src="<?=$templateFolder?>/images/loader-51px.svg" title="<?=GetMessage("DELIVERY_LOADER_IMAGE_ALT")?>" alt="<?=GetMessage("DELIVERY_LOADER_IMAGE_ALT")?>"></div>
		<div class="detail-deliveries-loader-heading"><?=GetMessage("DELIVERY_LOADER_HEADING")?><?if(!empty($_SESSION["USER_GEO_POSITION"]["city"])):?><?=GetMessage("DELIVERY_LOADER_HEADING_IN_CITY")?> <a href="#" class="user-geo-position-value-link"><?=$_SESSION["USER_GEO_POSITION"]["city"]?></a><?endif;?></div>
		<div class="detail-deliveries-loader-text"><?=GetMessage("DELIVERY_LOADER_TEXT")?></div>
	</div>
<?endif;?>

<script>
	//vars
	var deliveryAjaxDir = "<?=$templateFolder?>";
	var deliveryTemplatePath = "<?=$templateFolder?>";
	var deliveryParams = <?=\Bitrix\Main\Web\Json::encode($arParams);?>

	//check deferred
	<?if($arParams["DEFERRED_MODE"] == "Y" && $arParams["PRODUCT_AVAILABLE"] == "Y"):?>
		$(function(){
			//push delivery component (ajax)
			if(typeof $.getDeliveryComponent === "function"){
				$.getDeliveryComponent(<?=$arParams["PRODUCT_ID"]?>, <?=$arParams["PRODUCT_QUANTITY"]?>, "<?=$arParams["PRODUCT_AVAILABLE"]?>", "N");
			}
		});
	<?endif;?>
</script>
<?
	if($arParams["LOAD_SCRIPT"] == "Y"){
		$this->addExternalCss($templateFolder."/ajax_style.css");
		$this->addExternalJS($templateFolder."/ajax_script.js");
	}
?>