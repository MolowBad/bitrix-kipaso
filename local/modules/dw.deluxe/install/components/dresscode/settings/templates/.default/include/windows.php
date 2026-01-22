<div class="txSwitcherWindow productCreatePropertiesWindow">
	<div class="txSwitcherWindowOffset">
		<div class="txSwitcherWindowContainer">
			<a href="#" class="txSwitcherWindowExit"><span class="txSwitcherWindowExitButton"></span></a>
			<div class="switcherStartContainer">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings1.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_WINDOW_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_WINDOW_DESCRIPTION")?></div>
				<div class="switcherWindowIblockArea"><?=GetMessage("SETTINGS_WINDOW_IBLOCK_LABEL")?><span class="switcherWindowIblockData">-</span></div>
				<a href="#" class="switcherWindowStartButton startCreateProductProperties btn-simple btn-medium"><?=GetMessage("SETTINGS_WINDOW_START_BUTTON")?></a>
			</div>
			<div class="switcherResultContainer hidden">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings2.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_HEADING")?></div>
				<div class="switcherProductResult hidden">
					<div class="switcherWindowHeading2"><?=GetMessage("SETTINGS_SUCCESS_PRODUCT_HEADING")?></div>
					<div class="switcherResultTable"></div>
				</div>
				<div class="switcherSectionResult hidden">
					<div class="switcherWindowHeading2"><?=GetMessage("SETTINGS_SUCCESS_SECTION_HEADING")?></div>
					<div class="switcherResultTable"></div>
				</div>
				<a href="#" class="switcherWindowExit btn-simple btn-medium"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_CLOSE")?></a>
			</div>
			<div class="switcherErrorContainer hidden">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings3.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_ERROR_WINDOW_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_ERROR_DESCRIPTION")?></div>
				<a href="#" class="switcherWindowExit btn-simple btn-medium"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_CLOSE")?></a>
			</div>
		</div>
		<div class="txSwitcherHolder">
			<div class="txSwitcherPreloader">
				<div></div><div></div><div></div><div></div><div></div>
				<div></div><div></div><div></div><div></div><div></div>
			</div>
		</div>
	</div>
</div>
<div class="txSwitcherWindow skuCreatePropertiesWindow">
	<div class="txSwitcherWindowOffset">
		<div class="txSwitcherWindowContainer">
			<a href="#" class="txSwitcherWindowExit"><span class="txSwitcherWindowExitButton"></span></a>
			<div class="switcherStartContainer">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings1.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_WINDOW_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_WINDOW_DESCRIPTION")?></div>
				<div class="switcherWindowIblockArea"><?=GetMessage("SETTINGS_WINDOW_IBLOCK_LABEL")?><span class="switcherWindowIblockData">-</span></div>
				<a href="#" class="switcherWindowStartButton startCreateSkuProperties btn-simple btn-medium"><?=GetMessage("SETTINGS_WINDOW_START_BUTTON")?></a>
			</div>
			<div class="switcherResultContainer hidden">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings2.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_HEADING")?></div>
				<div class="switcherSkuResult hidden">
					<div class="switcherWindowHeading2"><?=GetMessage("SETTINGS_SUCCESS_PRODUCT_HEADING")?></div>
					<div class="switcherResultTable"></div>
				</div>
				<a href="#" class="switcherWindowExit btn-simple btn-medium"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_CLOSE")?></a>
			</div>
			<div class="switcherErrorContainer hidden">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings3.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_ERROR_WINDOW_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_ERROR_DESCRIPTION")?></div>
				<a href="#" class="switcherWindowExit btn-simple btn-medium"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_CLOSE")?></a>
			</div>
		</div>
		<div class="txSwitcherHolder">
			<div class="txSwitcherPreloader">
				<div></div><div></div><div></div><div></div><div></div>
				<div></div><div></div><div></div><div></div><div></div>
			</div>
		</div>
	</div>
</div>
<div class="txSwitcherWindow customThemeCreateWindow" data-template-path="<?=SITE_TEMPLATE_PATH?>" data-current-theme="<?=$template_theme?>" data-current-background="<?=$template_background_name?>">
	<div class="txSwitcherWindowOffset">
		<div class="txSwitcherWindowContainer">
			<a href="#" class="txSwitcherWindowExit"><span class="txSwitcherWindowExitButton"></span></a>
			<div class="switcherStartContainer">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings4.jpg" class="switcherWindowHeadingImage" alt="<?=GetMessage("SETTINGS_NEW_THEME_WINDOW_HEADING")?>"></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_DESCRIPTION")?></div>
				<div class="switcherWindowFields">
					<div class="switcherWindowFieldLine">
						<span class="switcherWindowFieldLabel"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_BASE_COLOR")?></span>
						<input type="text" name="switcherWindowThemeBaseColor" class="swticher-color-picker switcherWindowThemeBaseColor"<?if(!empty($arThemeColors["BASE_COLOR"])):?> data-base-value="<?=$arThemeColors["BASE_COLOR"]?>" value="<?=$arThemeColors["BASE_COLOR"]?>"<?endif;?>>
					</div>
					<div class="switcherWindowFieldLine">
						<span class="switcherWindowFieldLabel"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_BASE_COLOR_HOVER")?></span>
						<input type="text" name="switcherWindowThemeBaseColorHover" class="swticher-color-picker switcherWindowThemeBaseColorHover"<?if(!empty($arThemeColors["BASE_COLOR_HOVER"])):?> data-base-value="<?=$arThemeColors["BASE_COLOR_HOVER"]?>" value="<?=$arThemeColors["BASE_COLOR_HOVER"]?>"<?endif;?>>
					</div>
					<div class="switcherWindowFieldLine">
						<span class="switcherWindowFieldLabel"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_NAME")?></span>
						<input type="text" name="switcherWindowThemeName" class="switcherWindowThemeName" value="custom">
					</div>
					<div class="switcherWindowFieldLine">
						<input type="checkbox" name="switcherWindowThemeReplace" class="switcherWindowThemeReplace" id="switcherWindowThemeReplace" checked value="Y">
						<label class="switcherWindowFieldLabel" for="switcherWindowThemeReplace"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_REPLACE")?></label>
					</div>
				</div>
				<a href="#" class="switcherWindowStartButton startCreateCustomTheme btn-simple btn-medium"><?=GetMessage("SETTINGS_NEW_THEME_WINDOW_START_BUTTON")?></a>
			</div>
			<div class="switcherResultContainer hidden">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings5.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_NEW_THEME_SUCCESS_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_NEW_THEME_SUCCESS_DESCRIPTION")?></div>
				<a href="#" class="switcherWindowExit btn-simple btn-medium"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_CLOSE")?></a>
			</div>
			<div class="switcherErrorContainer hidden">
				<div class="swicherWindowHeadingIcon"><img src="<?=$templateFolder?>/images/settings6.jpg" class="switcherWindowHeadingImage" alt=""></div>
				<div class="switcherWindowHeading"><?=GetMessage("SETTINGS_ERROR_WINDOW_HEADING")?></div>
				<div class="switcherWindowDescription"><?=GetMessage("SETTINGS_NEW_THEME_ERROR_DESCRIPTION")?></div>
				<a href="#" class="switcherWindowExit btn-simple btn-medium"><?=GetMessage("SETTINGS_SUCCESS_WINDOW_CLOSE")?></a>
			</div>
		</div>
		<div class="txSwitcherHolder">
			<div class="txSwitcherPreloader">
				<div></div><div></div><div></div><div></div><div></div>
				<div></div><div></div><div></div><div></div><div></div>
			</div>
		</div>
	</div>
</div>
<script>
	$(function(){
		$(".swticher-color-picker").ColorPicker({
			onSubmit: function(hsb, hex, rgb, cal){
				$(cal).val("#" + hex);
			},
			onChange: function(cal, hex){
				$(this.el).val("#" + hex);
			},
			onBeforeShow: function(){
				$(this).ColorPickerSetColor(this.value);
			}
		}).bind("keyup", function(){
			$(this).ColorPickerSetColor(this.value);
		});
	});
</script>