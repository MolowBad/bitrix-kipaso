<div class="switcherMoreSettings switcherTab">

	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherAuthIcon.jpg" alt="<?=GetMessage("SETTINGS_AUTH_HEADING")?>" title="<?=GetMessage("SETTINGS_AUTH_HEADING")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_AUTH_HEADING")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_AUTH_DESC")?></div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_AUTH_TYPE")?></div>
		<select class="switcherSelect authTypeSelect" data-id="TEMPLATE_AUTH_TYPE">
			<?foreach($arAuthTypes as $typeValue => $typeName):?>
				<option value="<?=$typeValue?>"<?if($typeValue == $template_auth_format):?> selected="selected"<?endif;?>><?=$typeName?> [<?=$typeValue?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherMaskIcon.jpg" alt="<?=GetMessage("SETTINGS_MASK_HEADING")?>" title="<?=GetMessage("SETTINGS_MASK_HEADING")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_MASK_HEADING")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_MASK_DESC")?></div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_MASKED_INPUT")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_MASKED_INPUT">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_masked_input):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_MASKED_INPUT": "Y"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_MASKED_INPUT_FORMAT")?></div>
		<select class="switcherSelect maskedInputFormatSelect" data-id="TEMPLATE_MASKED_INPUT_FORMAT">
			<?foreach($arMaskedFormates as $typeValue => $typeName):?>
				<option value="<?=$typeValue?>"<?if($typeValue == $template_masked_input_format):?> selected="selected"<?endif;?>><?=$typeName?> [<?=$typeValue?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_MASKED_INPUT": "Y"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_MASKED_INPUT_CUSTOM_FORMAT")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT">
			<input type="text" name="settingsMaskedInputFormat" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherWatermarkIcon.jpg" alt="<?=GetMessage("SETTINGS_WATERMARK_HEADING")?>" title="<?=GetMessage("SETTINGS_WATERMARK_HEADING")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_WATERMARK_HEADING")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_WATERMARK_DESC")?></div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_USE_AUTO_WATERMARK")?></div>
		<div class="switcherBool switchByLink" data-id="TEMPLATE_USE_AUTO_WATERMARK">
			<?foreach($arBoolButton as $ixn => $nextVariant):?>
				<div class="switcherBoolItem switchByLinkItem<?if($ixn == $template_use_watermark):?> selected<?endif;?>"><a href="#" class="switcherBoolButton" data-value="<?=$ixn?>" title="<?=$nextVariant?>"><?=$nextVariant?></a></div>
			<?endforeach;?>
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_WATERMARK">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_TYPE")?></div>
		<select class="switcherSelect watermarkTypeSelect" data-id="TEMPLATE_WATERMARK_TYPE">
			<?foreach($arWatermarkTypes as $typeValue => $typeName):?>
				<option value="<?=$typeValue?>"<?if($typeValue == $template_watermark_type):?> selected="selected"<?endif;?>><?=$typeName?> [<?=$typeValue?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_AUTO_WATERMARK": "Y", "TEMPLATE_WATERMARK_TYPE": "image"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_PICTURE")?></div>
		<div class="switcherThemes switcherFile" data-id="TEMPLATE_WATERMARK_PICTURE">
			<input type="file" id="settingsWatermarkPicture" name="settingsWatermarkPicture" accept="image/*"<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_PICTURE"])):?> data-value="<?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_PICTURE"]?>"<?endif;?>>
			<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_PICTURE"])):?>
				<div class="switcherImageContainer"><a href="<?=DwSettings::clearRootFilePath($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_PICTURE"])?>" target="_blank" class="switcherImageContainerLink"><img src="<?=DwSettings::clearRootFilePath($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_PICTURE"])?>" class="switcherImageContainerImage"></a></div>
			<?endif;?>
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_WATERMARK">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_SIZE")?></div>
		<select class="switcherSelect watermarkSizeSelect" data-id="TEMPLATE_WATERMARK_SIZE">
			<?foreach($arWatermarkSizes as $sizeValue => $sizeName):?>
				<option value="<?=$sizeValue?>"<?if($sizeValue == $template_watermark_size):?> selected="selected"<?endif;?>><?=$sizeName?> [<?=$sizeValue?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_AUTO_WATERMARK": "Y", "TEMPLATE_WATERMARK_TYPE": "image"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_FILL")?></div>
		<select class="switcherSelect watermarkFillSelect" data-id="TEMPLATE_WATERMARK_FILL">
			<?foreach($arWatermarkFill as $fillValue => $fillName):?>
				<option value="<?=$fillValue?>"<?if($fillValue == $template_watermark_fill):?> selected="selected"<?endif;?>><?=$fillName?> [<?=$fillValue?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_WATERMARK">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_POSITION")?></div>
		<select class="switcherSelect watermarkPositionSelect" data-id="TEMPLATE_WATERMARK_POSITION">
			<?foreach($arWatermarkPositions as $posValue => $posName):?>
				<option value="<?=$posValue?>"<?if($posValue == $template_watermark_position):?> selected="selected"<?endif;?>><?=$posName?> [<?=$posValue?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock" data-dependence="TEMPLATE_USE_AUTO_WATERMARK">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_COEFFICIENT")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WATERMARK_COEFFICIENT">
			<input type="number" name="settingsWatermarkCoefficient" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_COEFFICIENT"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_COEFFICIENT"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_AUTO_WATERMARK": "Y", "TEMPLATE_WATERMARK_TYPE": "image"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_ALPHA_LEVEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WATERMARK_ALPHA_LEVEL">
			<input type="number" name="settingsWatermarkAlphaLevel" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_ALPHA_LEVEL"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_ALPHA_LEVEL"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_AUTO_WATERMARK": "Y", "TEMPLATE_WATERMARK_TYPE": "text"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_TEXT")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WATERMARK_TEXT">
			<input type="text" name="settingsWatermarkText" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_TEXT"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_TEXT"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_AUTO_WATERMARK": "Y", "TEMPLATE_WATERMARK_TYPE": "text"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_FONT")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WATERMARK_FONT">
			<input type="text" name="settingsWatermarkFont" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_FONT"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_FONT"]?><?else:?><?=$_SERVER["DOCUMENT_ROOT"]?><?=SITE_TEMPLATE_PATH?>/fonts/roboto/roboto-regular.ttf<?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock" data-dependence='{"TEMPLATE_USE_AUTO_WATERMARK": "Y", "TEMPLATE_WATERMARK_TYPE": "text"}'>
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WATERMARK_COLOR")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WATERMARK_COLOR">
			<input type="text" name="settingsWatermarkColor" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_COLOR"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WATERMARK_COLOR"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherLegalIcon.jpg" alt="<?=GetMessage("SETTINGS_LEGAL_HEADING")?>" title="<?=GetMessage("SETTINGS_LEGAL_HEADING")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_LEGAL_HEADING")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_LEGAL_DESC")?></div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_AGREEMENT_ID")?></div>
		<div class="switcherDescription2"><?=GetMessage("SETTINGS_AGREEMENT_ID_DESC")?></div>
		<select class="switcherSelect productIblockSelect" data-id="TEMPLATE_AGREEMENT_ID">
			<?foreach($arResult["AGREEMENTS"] as $agreementId => $agreementName):?>
				<option value="<?=$agreementId?>"<?if($agreementId == $actualAgreementId):?> selected="selected"<?endif;?>><?=$agreementName?> [<?=$agreementId?>]</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_AGREEMENT_PERSONAL_DATA_CATEGORIES")?></div>
		<div class="switcherDescription2"><?=GetMessage("SETTINGS_AGREEMENT_PERSONAL_DATA_CATEGORIES_DESC")?></div>
		<div class="switcherThemes switcherTextarea" data-id="TEMPLATE_AGREEMENT_PERSONAL_DATA_CATEGORIES">
			<textarea
				name="settingsAgreementPersonalDataCategories"
				rows="10"
				placeholder="<?=GetMessage("SETTINGS_AGREEMENT_PERSONAL_DATA_CATEGORIES_PLACEHOLDER")?>"
			><?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_AGREEMENT_PERSONAL_DATA_CATEGORIES"])):?><?=htmlspecialcharsbx(implode("\n", $arResult["CURRENT_SETTINGS"]["TEMPLATE_AGREEMENT_PERSONAL_DATA_CATEGORIES"]))?><?endif;?></textarea>
		</div>
	</div>
</div>
