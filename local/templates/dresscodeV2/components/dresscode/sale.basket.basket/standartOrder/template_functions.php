<?function getHTMLDataAttrs($arProperty = array(), $dataAttr = ""){
	if($arProperty["IS_PROFILE_NAME"] == "Y") $dataAttr .= ' data-profile-name="Y"';
	if($arProperty["IS_EMAIL"] == "Y") $dataAttr .= ' data-mail="Y"';
	if($arProperty["IS_PAYER"] == "Y") $dataAttr .= ' data-payer="Y"';
	if($arProperty["IS_Bitrix\Main\Localization\LocATION4TAX"] == "Y") $dataAttr .= ' data-Bitrix\Main\Localization\Location4tax="Y"';
	if($arProperty["IS_FILTERED"] == "Y") $dataAttr .= ' data-filtred="Y"';
	if($arProperty["IS_ZIP"] == "Y") $dataAttr .= ' data-zip="Y"';
	if($arProperty["IS_PHONE"] == "Y") $dataAttr .= ' data-mobile="Y"';
	if($arProperty["IS_ADDRESS"] == "Y") $dataAttr .= ' data-address="Y"';
	return $dataAttr;
}?>

<?function printOrderPropertyHTML($arProperty, $attrList = "", $arParams = array()){
	$dataAttr = getHTMLDataAttrs($arProperty);
	$propId = randString(7);?>
	<li<?=$attrList?>>
		<?if(!empty($arProperty["TYPE"]) && $arProperty["TYPE"] != "Y/N"):?>
			<span class="label"><?=$arProperty["NAME"]?><?if($arProperty["REQUIRED"] === "Y"):?>*<?endif;?></span>
			<label><?=$arProperty["DESCRIPTION"]?></label>
		<?endif;?>
		<?if($arProperty["TYPE"] == "STRING" && (empty($arProperty["MULTILINE"]) || $arProperty["MULTILINE"] == "N")):?>
			<input type="text" name="ORDER_PROP_<?=$arProperty["ID"]?>" value="<?=$arProperty["CURRENT_VALUE"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="ORDER_PROP_<?=$arProperty["ID"]?>" data-prop-id="<?=$propId?>" data-id="<?=$arProperty["ID"]?>"<?=$dataAttr?>>
			<?if($arProperty["IS_PHONE"] == "Y" && $arParams["USE_MASKED"] == "Y" && !empty($arParams["MASKED_FORMAT"])):?>
				<script>
					$(function(){
						$('input[data-prop-id="<?=$propId?>"]').mask("<?=$arParams["MASKED_FORMAT"]?>");
					});
				</script>
			<?endif;?>
		<?elseif($arProperty["TYPE"] == "STRING" && $arProperty["MULTILINE"] == "Y"):?>
			<textarea name="ORDER_PROP_<?=$arProperty["ID"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="ORDER_PROP_<?=$arProperty["ID"]?>" data-id="<?=$arProperty["ID"]?>"<?=$dataAttr?>><?=$arProperty["CURRENT_VALUE"]?></textarea>
		<?elseif($arProperty["TYPE"] == "NUMBER"):?>
			<input type="text" name="ORDER_PROP_<?=$arProperty["ID"]?>" value="<?=$arProperty["CURRENT_VALUE"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$arProperty["ID"]?>" data-id="<?=$arProperty["ID"]?>" data-number="Y"<?=$dataAttr?>>
		<?elseif($arProperty["TYPE"] == "DATE"):?>
			<input type="text" name="ORDER_PROP_<?=$arProperty["ID"]?>" value="<?=$arProperty["CURRENT_VALUE"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$arProperty["ID"]?>" data-id="<?=$arProperty["ID"]?>" data-time="Y" onclick="BX.calendar({node: this, field: this, bHideTime: false, bTime: <?=(!empty($arProperty["ON_SETTINGS"]["TIME"]) && $arProperty["ON_SETTINGS"]["TIME"] == "Y") ? "true" : "false"?>});" class="timeField"<?=$dataAttr?>>
		<?elseif($arProperty["TYPE"] == "Y/N"):?>
			<label><?=$arProperty["DESCRIPTION"]?></label>
			<div class="propLine">
				<input type="checkbox" value="Y"<?if($arProperty["CURRENT_VALUE"] == "Y"):?> checked<?endif;?> data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$arProperty["ID"]?>" data-id="<?=$arProperty["ID"]?>" name="ORDER_PROP_<?=$arProperty["ID"]?>"<?=$dataAttr?>>
				<label for="<?=$arProperty["ID"]?>"><?=$arProperty["NAME"]?><?if($arProperty["REQUIRED"] === "Y"):?>*<?endif;?></label>
			</div>
		<?elseif($arProperty["TYPE"] == "ENUM" && $arProperty["MULTIPLE"] == "Y" && $arProperty["MULTIELEMENT"] == "Y" && !empty($arProperty["OPTIONS"])):?>
			<?foreach($arProperty["OPTIONS"] as $nextIndex => $nextValue):?>
				<div class="propLine">
					<input type="checkbox" name="ORDER_PROP_<?=$arProperty["ID"]?>" value="<?=$nextIndex?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$propId?>_<?=$arProperty["ID"]?>_<?=$nextIndex?>"<?=(is_array($arProperty["CURRENT_VALUE"]) && in_array($nextIndex, $arProperty["CURRENT_VALUE"]) ? " checked" : "")?> data-id="<?=$arProperty["ID"]?>"<?=$dataAttr?>>
					<label for="<?=$propId?>_<?=$arProperty["ID"]?>_<?=$nextIndex?>"><?=htmlspecialcharsbx($nextValue)?></label>
				</div>
			<?endforeach;?>
		<?elseif($arProperty["TYPE"] == "ENUM" && $arProperty["MULTIPLE"] == "N" && (empty($arProperty["MULTIELEMENT"]) || $arProperty["MULTIELEMENT"] == "N") && !empty($arProperty["OPTIONS"])):?>
			<div class="dropDown">
				<?foreach($arProperty["OPTIONS"] as $nextIndex => $nextValue):?>
					<?if($arProperty["CURRENT_VALUE"] == $nextIndex):?>
						<div class="dropDownSelected"><?=htmlspecialcharsbx($nextValue)?></div>
					<?endif;?>
				<?endforeach;?>
				<div class="dropDownItems">
					<?foreach($arProperty["OPTIONS"] as $nextIndex => $nextValue):?>
						<div class="dropDownItem<?if($arProperty["CURRENT_VALUE"] == $nextIndex):?> selected<?endif;?>" data-value="<?=$nextIndex?>"><?=htmlspecialcharsbx($nextValue)?></div>
					<?endforeach;?>
				</div>
		        <select name="ORDER_PROP_<?=$arProperty["ID"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" data-id="<?=$arProperty["ID"]?>"<?=$dataAttr?>>
			        <?foreach($arProperty["OPTIONS"] as $nextIndex => $nextValue):?>
			            <option value="<?=$nextIndex?>"<?=($arProperty["CURRENT_VALUE"] == $nextIndex ? " selected" : "")?>><?=htmlspecialcharsbx($nextValue)?></option>
			        <?endforeach;?>
		        </select>
		    </div>
		<?elseif($arProperty["TYPE"] == "ENUM" && $arProperty["MULTIPLE"] == "N" && $arProperty["MULTIELEMENT"] == "Y" && !empty($arProperty["OPTIONS"])):?>
			<?foreach($arProperty["OPTIONS"] as $nextIndex => $nextValue):?>
				<div class="propLine">
					<input type="radio" name="ORDER_PROP_<?=$arProperty["ID"]?>" value="<?=$nextIndex?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$propId?>_<?=$arProperty["ID"]?>_<?=$nextIndex?>"<?=($arProperty["CURRENT_VALUE"] == $nextIndex ? " checked" : "")?> data-id="<?=$arProperty["ID"]?>"<?=$dataAttr?>>
					<label for="<?=$propId?>_<?=$arProperty["ID"]?>_<?=$nextIndex?>"><?=htmlspecialcharsbx($nextValue)?></label>
				</div>
			<?endforeach;?>
		<?elseif($arProperty["TYPE"] == "ENUM" && $arProperty["MULTIPLE"] == "Y" && (empty($arProperty["MULTIELEMENT"]) || $arProperty["MULTIELEMENT"] == "N") && !empty($arProperty["OPTIONS"])):?>
			<select multiple name="ORDER_PROP_<?=$arProperty["ID"]?>" size="<?=((IntVal($arProperty["SIZE"]) > 0) ? $arProperty["SIZE"] : 5)?>" class="multi" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" data-id="<?=$arProperty["ID"]?>"<?=$dataAttr?>>
				<?foreach($arProperty["OPTIONS"] as $nextIndex => $nextValue):?>
					<option value="<?=$nextIndex?>"<?=(is_array($arProperty["CURRENT_VALUE"]) && in_array($nextIndex, $arProperty["CURRENT_VALUE"]) ? " selected" : "")?>><?=htmlspecialcharsbx($nextValue)?></option>
				<?endforeach;?>
			</select>
		<?elseif($arProperty["TYPE"] == "FILE"):?>
			<input type="file" name="ORDER_PROP_<?=$arProperty["ID"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$arProperty["ID"]?>" data-id="<?=$arProperty["ID"]?>" class="file" autocomplete="off" data-file="Y"<?if($arProperty["MULTIPLE"] == "Y"):?> multiple<?endif;?> value=""<?=$dataAttr?>>
		<?elseif($arProperty["TYPE"] == "LOCATION"):?>
			<input type="text" name="ORDER_PROP_<?=$arProperty["ID"]?>" value="<?=$arProperty["LOCATION"]["DISPLAY_VALUE"]?>" data-last-id="<?=$arProperty["LOCATION_ID"]?>" data-last-value="<?=$arProperty["LOCATION"]["DISPLAY_VALUE"]?>" data-required="<?if($arProperty["REQUIRED"] === "Y"):?>Y<?else:?>N<?endif;?>" id="<?=$arProperty["ID"]?>" data-id="<?=$arProperty["ID"]?>" class="location" autocomplete="off" data-location="<?=$arProperty["LOCATION_ID"]?>" <?=$dataAttr?>>
			<div class="locationSwitchContainer"></div>
		<?endif;?>
	</li>
<?}?>
<?function getExtraServicesHTML($arExtraServices = array(), $currencyCode = "RUB"){?>
	<?$extraServicesHTML = "";?>
	<?if(!empty($arExtraServices)):?>
		<?ob_start();?>
		<div class="extraServicesItemContainer">
			<?foreach($arExtraServices as $nextService):?>
				<div class="extraServicesItem" data-service-id="<?=$nextService["ID"]?>">
					<?printExtraServiceItemHTML($nextService, $currencyCode);?>
				</div>
			<?endforeach;?>
		</div>
	   <?$extraServicesHTML = ob_get_contents();?>
	   <?ob_end_clean();?>
	<?endif;?>
	<?return $extraServicesHTML;?>
<?}?>
<?function printExtraServiceItemHTML($extraServiceItem = array(), $currencyCode = "RUB"){

	if(!empty($extraServiceItem)){
		if($extraServiceItem["CLASS_NAME"] == "\Bitrix\Sale\Delivery\ExtraServices\Enum"){
			if(!empty($extraServiceItem["PARAMS"]["PRICES"])){?>
				<?if(!empty($extraServiceItem["NAME"])):?>
					<div class="serviceName"><?=$extraServiceItem["NAME"]?></div>
				<?endif;?>
				<?if(!empty($extraServiceItem["DESCRIPTION"])):?>
					<div class="serviceDescription"><?=$extraServiceItem["DESCRIPTION"]?></div>
				<?endif;?>
				<div class="serviceSelectItem">
					<div class="dropDown">
						<?foreach($extraServiceItem["PARAMS"]["PRICES"] as $serviceItemId => $nextServiceItem):?>
							<?if(empty($extraServiceItem["INIT_VALUE"]) || $extraServiceItem["INIT_VALUE"] == $serviceItemId):?>
								<div class="dropDownSelected"><?=$nextServiceItem["TITLE"]?> <?=FormatCurrency($nextServiceItem["PRICE"], $currencyCode);?></div> <?break(1);?>
							<?endif;?>
						<?endforeach;?>
						<div class="dropDownItems">
							<?foreach($extraServiceItem["PARAMS"]["PRICES"] as $serviceItemId => $nextServiceItem):?>
								<div class="dropDownItem<?if($extraServiceItem["INIT_VALUE"] == $serviceItemId):?> selected<?endif;?>" data-value="<?=$serviceItemId?>"><?=$nextServiceItem["TITLE"]?> <?=FormatCurrency($nextServiceItem["PRICE"], $currencyCode);?></div>
							<?endforeach;?>
						</div>
						<select name="extra_<?=$extraServiceItem["ID"]?>" class="extraServiceSwitch" data-id="<?=$extraServiceItem["ID"]?>" data-default="<?=$extraServiceItem["INIT_VALUE"]?>" data-last="<?=!empty($extraServiceItem["INIT_VALUE"]) ? $extraServiceItem["PARAMS"]["PRICES"][$extraServiceItem["INIT_VALUE"]]["PRICE"] : 0;?>">
							<?foreach($extraServiceItem["PARAMS"]["PRICES"] as $serviceItemId => $nextServiceItem):?>
								<option value="<?=$serviceItemId?>" data-price="<?=$nextServiceItem["PRICE"]?>"<?if($extraServiceItem["INIT_VALUE"] == $serviceItemId):?> selected<?endif;?>><?=$nextServiceItem["TITLE"]?> <?=FormatCurrency($nextServiceItem["PRICE"], $currencyCode);?></option>
							<?endforeach;?>
						</select>
					</div>
				</div>
			<?}
		}

		elseif($extraServiceItem["CLASS_NAME"] == "\Bitrix\Sale\Delivery\ExtraServices\Checkbox"){?>
			<?$extraId = \Bitrix\Main\Security\Random::getString(8);?>
			<div class="serviceBoxContainer">
				<input type="checkbox" value="Y" id="service_<?=$extraId?>" name="service_<?=$extraServiceItem["ID"]?>" class="extraServiceSwitch" data-id="<?=$extraServiceItem["ID"]?>" data-default="<?=$extraServiceItem["INIT_VALUE"]?>" data-price="<?=$extraServiceItem["PARAMS"]["PRICE"]?>"<?if($extraServiceItem["INIT_VALUE"] == "Y"):?> checked<?endif;?>>
				<label for="service_<?=$extraId?>"><?=$extraServiceItem["NAME"]?> <span class="servicePrice"><b>(<?=FormatCurrency($extraServiceItem["PARAMS"]["PRICE"], $currencyCode);?>)</b></span></label>
				<div class="serviceDescription"><?=$extraServiceItem["DESCRIPTION"]?></div>
			</div>
		<?}

		elseif($extraServiceItem["CLASS_NAME"] == "\Bitrix\Sale\Delivery\ExtraServices\Quantity"){?>
			<div class="extraServiceQuantity">
				<div class="serviceHeadingContainer">
					<div class="serviceName"><?=$extraServiceItem["NAME"]?></div>
					<div class="servicePrice"><?=\Bitrix\Main\Localization\Loc::getMessage("PRICE_FOR_PIECE");?> <b><?=FormatCurrency($extraServiceItem["PARAMS"]["PRICE"], $currencyCode);?></b></div>
					<div class="serviceTotalSum"><b><?=\Bitrix\Main\Localization\Loc::getMessage("TOTAL_SUM")?> (<span class="extraServiceItemSum"><?=FormatCurrency(($extraServiceItem["PARAMS"]["PRICE"] * $extraServiceItem["INIT_VALUE"]), $currencyCode);?></span>)</b></div>
				</div>
				<div class="serviceDescription"><?=$extraServiceItem["DESCRIPTION"]?></div>
				<input type="text" name="service_<?=$extraServiceItem["ID"]?>" value="<?=$extraServiceItem["INIT_VALUE"]?>" class="extraServiceSwitch" data-id="<?=$extraServiceItem["ID"]?>" data-default="<?=$extraServiceItem["INIT_VALUE"]?>" data-last="<?=$extraServiceItem["INIT_VALUE"]?>" data-price="<?=$extraServiceItem["PARAMS"]["PRICE"]?>">
			</div>
		<?}

	}

}?>