<div class="switcherContactsSettings switcherTab">
	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherContactsIcon.jpg" alt="<?=GetMessage("SETTINGS_CONTACTS_TITLE")?>" title="<?=GetMessage("SETTINGS_CONTACTS_TITLE")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_CONTACTS_TITLE")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_CONTACTS_DESC")?></div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_TELEPHONE_1_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_TELEPHONE_1">
			<input type="text" name="settingsTelephone1" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_TELEPHONE_1"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_TELEPHONE_1"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_TELEPHONE_2_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_TELEPHONE_2">
			<input type="text" name="settingsTelephone2" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_TELEPHONE_2"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_TELEPHONE_2"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_EMAIL_1_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_EMAIL_1">
			<input type="text" name="settingsEmail1" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_EMAIL_1"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_EMAIL_1"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_EMAIL_2_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_EMAIL_2">
			<input type="text" name="settingsEmail2" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_EMAIL_2"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_EMAIL_2"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_ADDRESS_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_ADDRESS">
			<input type="text" name="settingsAddress" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_ADDRESS"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_ADDRESS"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_FULL_ADDRESS_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_FULL_ADDRESS">
			<input type="text" name="settingsAddress" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_FULL_ADDRESS"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_FULL_ADDRESS"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_SLOGAN_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_SLOGAN">
			<input type="text" name="settingsSlogan" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_SLOGAN"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_SLOGAN"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WORKING_TIME_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WORKING_TIME">
			<input type="text" name="settingsOperatingMode" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WORKING_TIME"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WORKING_TIME"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WORKING_TIME_SHORT_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WORKING_TIME_SHORT">
			<input type="text" name="settingsOperatingMode" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WORKING_TIME_SHORT"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WORKING_TIME_SHORT"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_COPYRIGHT_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_COPYRIGHT">
			<input type="text" name="settingsOperatingMode" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_COPYRIGHT"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_COPYRIGHT"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherIcons">
			<img src="<?=$templateFolder?>/images/switcherSocialNetworksIcon.jpg" alt="<?=GetMessage("SETTINGS_SOCIAL_NETWORKS_TITLE")?>" title="<?=GetMessage("SETTINGS_SOCIAL_NETWORKS_TITLE")?>">
		</div>
		<div class="switcherHeading"><?=GetMessage("SETTINGS_SOCIAL_NETWORKS_TITLE")?></div>
		<div class="switcherDescription"><?=GetMessage("SETTINGS_SOCIAL_NETWORKS_DESC")?></div>
	</div>

	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_TELEGRAM_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_TELEGRAM_LINK">
			<input type="text" name="settingsTelegramLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_TELEGRAM_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_TELEGRAM_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_WHATSAPP_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_WHATSAPP_LINK">
			<input type="text" name="settingsWhatsappLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_WHATSAPP_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_WHATSAPP_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_RUTUBE_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_RUTUBE_LINK">
			<input type="text" name="settingsRutubeLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_RUTUBE_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_RUTUBE_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_DZEN_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_DZEN_LINK">
			<input type="text" name="settingsDzenLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_DZEN_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_DZEN_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_VIBER_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_VIBER_LINK">
			<input type="text" name="settingsViberLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_VIBER_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_VIBER_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_TIKTOK_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_TIKTOK_LINK">
			<input type="text" name="settingTiktokLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_TIKTOK_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_TIKTOK_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_VK_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_VK_LINK">
			<input type="text" name="settingsVkLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_VK_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_VK_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_INSTAGRAM_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_INSTAGRAM_LINK">
			<input type="text" name="settingsInstagramLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_INSTAGRAM_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_INSTAGRAM_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_FACEBOOK_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_FACEBOOK_LINK">
			<input type="text" name="settingsFacebookLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_FACEBOOK_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_FACEBOOK_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_YOUTUBE_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_YOUTUBE_LINK">
			<input type="text" name="settingsYoutubeLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_YOUTUBE_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_YOUTUBE_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_ODNOKLASSNIKI_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_ODNOKLASSNIKI_LINK">
			<input type="text" name="settingsOdnoklassnikiLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_ODNOKLASSNIKI_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_ODNOKLASSNIKI_LINK"]?><?endif;?>">
		</div>
	</div>
	<div class="switcherRowBlock">
		<div class="switcherHeading2"><?=GetMessage("SETTINGS_TWITTER_LABEL")?></div>
		<div class="switcherThemes switcherInputText" data-id="TEMPLATE_TWITTER_LINK">
			<input type="text" name="settingsTwitterLink" value="<?if(!empty($arResult["CURRENT_SETTINGS"]["TEMPLATE_TWITTER_LINK"])):?><?=$arResult["CURRENT_SETTINGS"]["TEMPLATE_TWITTER_LINK"]?><?endif;?>">
		</div>
	</div>
</div>
