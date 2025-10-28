<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true){
	die();
}

//include module
\Bitrix\Main\Loader::includeModule("dw.deluxe");

//get template settings
$arTemplateSettings = DwSettings::getInstance()->getCurrentSettings();

//check settings
if(!empty($arTemplateSettings)){

	//set params
	$arParams["USE_MASKED_INPUT"] = !empty($arTemplateSettings["TEMPLATE_USE_MASKED_INPUT"]) ? $arTemplateSettings["TEMPLATE_USE_MASKED_INPUT"] : "N";

	//get masked input format
	if($arParams["USE_MASKED_INPUT"] == "Y"){
		$arParams["MASKED_INPUT_FORMAT"] = !empty($arTemplateSettings["TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT"]) ? $arTemplateSettings["TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT"] : $arTemplateSettings["TEMPLATE_MASKED_INPUT_FORMAT"];
	}

}

?>

<h1><?=GetMessage("FORGOT_TITLE")?></h1>

<ul id="authMenu">
	<li><a href="<?=SITE_DIR?>auth/" rel="nofollow"><?=GetMessage("AUTH_TITLE")?></a></li>
	<li><a href="<?=SITE_DIR?>auth/?register=yes" rel="nofollow"><?=GetMessage("AUTH_REGISTER")?></a></li>
	<li><a href="<?=SITE_DIR?>auth/?forgot_password=yes" rel="nofollow" class="selected"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a></li>
</ul>

<div class="bx-auth">
	<?if(empty($arParams["~AUTH_RESULT"]["TYPE"]) || $arParams["~AUTH_RESULT"]["TYPE"] != "OK"):?>
		<form name="bform" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>" class="bx-auth-form">
			<div><?=GetMessage("AUTH_FORGOT_PASSWORD_1")?></div>
			<?if($arResult["BACKURL"] <> ''):?>
					<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
			<?endif?>
			<input type="hidden" name="AUTH_FORM" value="Y">
			<input type="hidden" name="TYPE" value="SEND_PWD">
			<div class="bx-auth-form-line">
				<div class="bx-authform-label-container"><?echo GetMessage("AUTH_LOGIN_EMAIL")?></div>
				<div class="bx-authform-input-container">
					<input type="text" name="USER_LOGIN" maxlength="255" value="<?=$arResult["LAST_LOGIN"]?>" class="bx-auth-one-required"/>
					<input type="hidden" name="USER_EMAIL" />
				</div>
			</div>
			<?if($arResult["PHONE_REGISTRATION"]):?>
				<div class="bx-auth-form-line">
					<div><?=GetMessage("sys_forgot_pass_phone")?></div>
					<div><input type="text" name="USER_PHONE_NUMBER" value="" class="forgot-user-phone-field bx-auth-one-required" /></div>
				</div>
				<div class="bx-auth-form-line"><?=GetMessage("sys_forgot_pass_note_phone")?></div>
				<?if(!empty($arParams["MASKED_INPUT_FORMAT"])):?>
					<script>
						$(function(){
							$(".forgot-user-phone-field").mask("<?=$arParams["MASKED_INPUT_FORMAT"]?>");
						});
					</script>
				<?endif;?>
			<?endif;?>
			<?if($arResult["USE_CAPTCHA"]):?>
				<div class="bx-auth-captha-container">
					<div class="bx-auth-input-line">
						<div class="bx-authform-input-container">
							<input type="hidden" name="captcha_sid" value="<?=$arResult["CAPTCHA_CODE"]?>" />
							<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
						</div>
					</div>
					<div class="bx-auth-input-line">
						<div class="bx-authform-label-container"><?=GetMessage("system_auth_captcha")?><span class="starrequired">*</span></div>
						<div class="bx-authform-input-container">
							<input type="text" name="captcha_word" maxlength="50" value="" class="bx-auth-captcha-field" />
						</div>
					</div>
				</div>
			<?endif?>

			<?if(!empty($arParams["~AUTH_RESULT"])):
				$text = str_replace(array("<br>", "<br />"), "\n", $arParams["~AUTH_RESULT"]["MESSAGE"]);
			?>
				<div class="alert small <?=($arParams["~AUTH_RESULT"]["TYPE"] == "OK"? "alert-success":"alert-danger")?>"><?=nl2br(htmlspecialcharsbx($text))?></div>
			<?endif?>

			<div>
				<input type="submit" class="btn btn-primary submit" name="send_account_info" value="<?=GetMessage("AUTH_SEND")?>" />
			</div>

		</form>
	<?else:?>
		<?$arMessages = explode(".", $arParams["~AUTH_RESULT"]["MESSAGE"]);?>
		<div class="bx-auth-success-heading h2 ff-medium"><?=GetMessage("AUTH_SEND_SUCCESS")?></div>
		<?if(!empty($arMessages)):?>
			<div class="alert-success">
				<?foreach($arMessages as $nextMessage):?>
					<?$nextMessage = nl2br(htmlspecialcharsbx(str_replace(array("<br>", "<br />"), "", $nextMessage)));?>
					<?if(!empty($nextMessage)):?>
						<div class="alert-success-item"><?=$nextMessage?>.</div>
					<?endif;?>
				<?endforeach;?>
			</div>
		<?endif;?>
		<div class="bx-auth-forgot-buttons">
			<a href="<?=SITE_DIR?>auth/?change_password=yes" class="btn-simple btn-small bx-auth-change-password-link"><?=GetMessage("AUTH_WRITE_STRING")?></a>
			<a href="<?=SITE_DIR?>auth/" class="btn-simple btn-small btn-black-border"><?=GetMessage("AUTH_TITLE")?></a>
		</div>
	<?endif;?>

</div>

<script>
	document.bform.onsubmit = function(){document.bform.USER_EMAIL.value = document.bform.USER_LOGIN.value;};
	document.bform.USER_LOGIN.focus();
</script>
