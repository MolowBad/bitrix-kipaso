<div class="switcherEventsSettings switcherTab">
	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherEventIcon.jpg" alt="<?=GetMessage("SETTINGS_USE_AUTO_HEADING")?>" title="<?=GetMessage("SETTINGS_USE_AUTO_HEADING")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_USE_AUTO_HEADING")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_USE_AUTO_DESC")?></div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_AUTO_AVAILABLE_PRODUCTS")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_AUTO_AVAILABLE_PRODUCTS">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_auto_available):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_AUTO_DEACTIVATE_PRODUCTS")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_AUTO_DEACTIVATE_PRODUCTS">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_auto_deactivate):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_AUTO_SAVE_PRICE")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_AUTO_SAVE_PRICE">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_auto_save_price):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_AUTO_SAVE_BRAND")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_AUTO_BRAND">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_auto_brand):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<?if(!empty($arResult["PRODUCT_IBLOCKS"])):?>
		<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_BRAND">
			<div class="switcherHeading2"><?=GetMessage("SETTINGS_BRAND_IBLOCK_ID")?></div>
			<select class="switcherSelect brandIblockSelect" data-id="TEMPLATE_BRAND_IBLOCK_ID">
				<?foreach($arResult["PRODUCT_IBLOCKS"] as $iblockId => $arNextIblock):?>
					<option value="<?=$iblockId?>"<?if($iblockId == $brandIblockId):?> selected="selected"<?endif;?>><?=$arNextIblock["NAME"]?> [<?=$iblockId?>]</option>
				<?endforeach;?>
			</select>
		</div>
	<?endif;?>
	<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_BRAND">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_BRAND_PROPERTY_CODE")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_BRAND_PROPERTY_CODE">
			<input type="text" name="settingsBrandPropertyCode" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_BRAND_PROPERTY_CODE"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_BRAND_PROPERTY_CODE"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_AUTO_SAVE_COLLECTION")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_AUTO_COLLECTION">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_auto_collection):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<?if(!empty($arResult["PRODUCT_IBLOCKS"])):?>
		<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_COLLECTION">
			<div class="switcherHeading2"><?=GetMessage("SETTINGS_COLLECTION_IBLOCK_ID")?></div>
			<select class="switcherSelect collectionIblockSelect" data-id="TEMPLATE_COLLECTION_IBLOCK_ID">
				<?foreach($arResult["PRODUCT_IBLOCKS"] as $iblockId => $arNextIblock):?>
					<option value="<?=$iblockId?>"<?if($iblockId == $collectionIblockId):?> selected="selected"<?endif;?>><?=$arNextIblock["NAME"]?> [<?=$iblockId?>]</option>
				<?endforeach;?>
			</select>
		</div>
	<?endif;?>
	<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_COLLECTION">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_COLLECTION_PROPERTY_CODE")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_COLLECTION_PROPERTY_CODE">
			<input type="text" name="settingsCollectionPropertyCode" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_COLLECTION_PROPERTY_CODE"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_COLLECTION_PROPERTY_CODE"]?><?endif;?>">
		</div>
	</div>
</div>