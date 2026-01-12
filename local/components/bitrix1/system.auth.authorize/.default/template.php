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
	$arParams["AUTH_BY"] = !empty($arTemplateSettings["TEMPLATE_AUTH_TYPE"]) ? $arTemplateSettings["TEMPLATE_AUTH_TYPE"] : "PASS";
	$arParams["USE_MASKED_INPUT"] = !empty($arTemplateSettings["TEMPLATE_USE_MASKED_INPUT"]) ? $arTemplateSettings["TEMPLATE_USE_MASKED_INPUT"] : "N";

	//get masked input format
	if($arParams["USE_MASKED_INPUT"] == "Y"){
		$arParams["MASKED_INPUT_FORMAT"] = !empty($arTemplateSettings["TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT"]) ? $arTemplateSettings["TEMPLATE_MASKED_INPUT_CUSTOM_FORMAT"] : $arTemplateSettings["TEMPLATE_MASKED_INPUT_FORMAT"];
	}
}

//get option by main module
$arParams["PHONE_AUTH"] = (COption::GetOptionString("main", "new_user_phone_auth", "N") == "Y");

?>
<?include($_SERVER["DOCUMENT_ROOT"]."/".$templateFolder."/include/functions.php");?>
<h1><?=GetMessage("AUTH_TITLE")?></h1>
<ul id="authMenu">
	<li><a href="<?=$arResult["AUTH_URL"]?>" rel="nofollow" class="selected"><?=GetMessage("AUTH_TITLE")?></a></li>
	<li><a href="<?=$arResult["AUTH_REGISTER_URL"]?>" rel="nofollow"><?=GetMessage("AUTH_REGISTER")?></a></li>
	<li><a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" rel="nofollow"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a></li>
</ul>
<div class="bx-auth">
	<?if($arParams["PHONE_AUTH"] == "Y"):?>
		<div class="bx-auth-type-select">
			<div class="bx-auth-type-select-item selected"><a href="#" class="btn-simple btn-small bx-auth-type-select-link"><?=GetMessage("AUTH_BY_LOGIN")?></a></div>
			<div class="bx-auth-type-select-item"><a href="#" class="btn-simple btn-small btn-border bx-auth-type-select-link"><?=GetMessage("AUTH_BY_PHONE")?></a></div>
		</div>
	<?endif;?>
	<div class="leftContainer">
		<div class="bx-auth-type-items">
			<div class="bx-auth-type-item auth-by-login active">
				<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>" class="bx-auth-form">
					<div class="bx-auth-input-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_LOGIN")?>*</div>
						<div class="bx-authform-input-container">
							<input type="text" name="USER_LOGIN" maxlength="255" value="<?=$arResult["LAST_LOGIN"]?>" />
						</div>
					</div>
					<div class="bx-auth-input-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_PASSWORD")?>*</div>
						<div class="bx-authform-input-container">
							<?if($arResult["SECURE_AUTH"]):?>
								<div class="bx-authform-psw-protected" id="bx_auth_secure" style="display:none"><div class="bx-authform-psw-protected-desc"><span></span><?echo GetMessage("AUTH_SECURE_NOTE")?></div></div>
								<script>
									document.getElementById('bx_auth_secure').style.display = '';
								</script>
							<?endif?>
							<input type="password" name="USER_PASSWORD" maxlength="255" autocomplete="off" />
						</div>
					</div>
					<div class="bx-auth-captcha-container">
						<?if($arResult["CAPTCHA_CODE"]):?>
							<?=getCaptcha($arResult["CAPTCHA_CODE"], GetMessage("AUTH_CAPTCHA_PROMT"));//print captcha?>
						<?endif;?>
					</div>
					<?if($arResult["STORE_PASSWORD"] == "Y"):?>
						<div class="checkbox">
							<input type="checkbox" id="USER_REMEMBER_BY_LOGIN" name="USER_REMEMBER" value="Y" checked="checked" />
							<label class="bx-filter-param-label" for="USER_REMEMBER_BY_LOGIN"><?=GetMessage("AUTH_REMEMBER_ME")?></label>
						</div>
					<?endif?>
					<input type="hidden" name="ACTION_TYPE" value="AUTH" />
					<input type="hidden" name="AUTH_TYPE" value="LOGIN" />
					<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />
					<?if(!empty($arResult["BACKURL"])):?>
						<input type="hidden" name="BACKURL" value="<?=$arResult["BACKURL"]?>" />
					<?endif?>
					<div class="bx-auth-error-container"></div>
					<div class="auth-submit-container">
						<input type="submit" class="btn btn-primary submit sendForm" name="Login" value="<?=GetMessage("AUTH_AUTHORIZE")?>" />
						<a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" rel="nofollow" class="forgot"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a>
					</div>
				</form>
			</div>
			<?if($arParams["PHONE_AUTH"] == "Y"):?>
				<div class="bx-auth-type-item auth-by-phone">
					<div class="bx-auth-sms-container-first">
						<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>" class="bx-auth-form">
							<div class="bx-auth-input-line">
								<div class="bx-authform-label-container"><?=GetMessage("AUTH_PHONE")?>*</div>
								<div class="bx-authform-input-container">
									<input type="text" name="USER_PHONE" maxlength="255" value="" class="bx-auth-phone-field" />
								</div>
								<?if(!empty($arParams["MASKED_INPUT_FORMAT"])):?>
									<script>
										$(function(){
											$(".bx-auth-phone-field").mask("<?=$arParams["MASKED_INPUT_FORMAT"]?>");
										});
									</script>
								<?endif;?>
							</div>
							<?if(empty($arParams["AUTH_BY"]) || $arParams["AUTH_BY"] == "PASSWORD"):?>
								<div class="bx-auth-input-line">
									<div class="bx-authform-label-container"><?=GetMessage("AUTH_PASSWORD")?>*</div>
									<div class="bx-authform-input-container">
										<input type="password" name="USER_PASSWORD" maxlength="255" autocomplete="off" />
										<input type="hidden" name="AUTH_BY" value="PASSWORD" />
									</div>
								</div>
								<div class="bx-auth-captcha-container">
									<?if($arResult["CAPTCHA_CODE"]):?>
										<?=getCaptcha($arResult["CAPTCHA_CODE"], GetMessage("AUTH_CAPTCHA_PROMT"));//print captcha?>
									<?endif;?>
								</div>
							<?else:?>
								<input type="hidden" name="AUTH_BY" value="SMS_CODE" />
							<?endif;?>
							<input type="hidden" name="ACTION_TYPE" value="AUTH" />
							<input type="hidden" name="AUTH_TYPE" value="PHONE" />
							<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />
							<?if(!empty($arResult["BACKURL"])):?>
								<input type="hidden" name="BACKURL" value="<?=$arResult["BACKURL"]?>" />
							<?endif?>
							<?if(empty($arParams["AUTH_BY"]) || $arParams["AUTH_BY"] == "PASSWORD" && $arResult["STORE_PASSWORD"] == "Y"):?>
								<div class="checkbox">
									<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER_BY_PHONE" value="Y" checked="checked" />
									<label class="bx-filter-param-label" for="USER_REMEMBER_BY_PHONE"><?=GetMessage("AUTH_REMEMBER_ME")?></label>
								</div>
							<?endif?>
							<div class="auth-submit-container">
								<input type="submit" class="btn btn-primary submit sendForm" name="Login" value="<?if(!empty($arParams["AUTH_BY"]) && $arParams["AUTH_BY"] == "SMS"):?><?=GetMessage("AUTH_GET_SMS_CODE")?><?else:?><?=GetMessage("AUTH_AUTHORIZE")?><?endif;?>" />
								<a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" rel="nofollow" class="forgot"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a>
							</div>
							<div class="bx-auth-error-container"></div>
						</form>
					</div>
					<div class="bx-auth-sms-container-next hidden">
						<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>" class="bx-auth-form">
							<div class="bx-auth-sms-code-contaier">
								<div class="bx-authform-label-container"><?=GetMessage("AUTH_SMS_CODE")?>*</div>
								<div class="bx-authform-input-container">
									<input type="password" name="USER_SMS_CODE" maxlength="255" autocomplete="off" />
									<input type="hidden" name="AUTH_BY" value="SMS" />
								</div>
							</div>
							<div class="checkbox">
								<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER_BY_PHONE" value="Y" checked="checked" />
								<label class="bx-filter-param-label" for="USER_REMEMBER_BY_PHONE"><?=GetMessage("AUTH_REMEMBER_ME")?></label>
							</div>
							<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />
							<input type="hidden" name="ACTION_TYPE" value="SMS_REQUEST" />
							<?if(!empty($arResult["BACKURL"])):?>
								<input type="hidden" name="BACKURL" value="<?=$arResult["BACKURL"]?>" />
							<?endif?>
							<div class="bx-auth-error-container"></div>
							<div class="auth-submit-container">
								<input type="submit" class="btn btn-primary submit sendForm" name="Login" value="<?=GetMessage("AUTH_AUTHORIZE")?>" />
								<a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" rel="nofollow" class="forgot"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a>
							</div>
						</form>
					</div>
				</div>
			<?endif;?>
		</div>
	</div>
	<div class="rightContainer">
		<h3 class="bx-title"><?=GetMessage("AUTH_SERVICES_TITLE")?></h3>
		<?if($arResult["AUTH_SERVICES"]):?>
			<?
				$APPLICATION->IncludeComponent("bitrix:socserv.auth.form",
					"flat",
					array(
						"AUTH_SERVICES" => $arResult["AUTH_SERVICES"],
						"AUTH_URL" => $arResult["AUTH_URL"],
						"POST" => $arResult["POST"],
					),
					$component,
					array("HIDE_ICONS" => "Y")
				);
			?>
		<?endif?>
		<p><?=GetMessage("REGISTER_TEXT")?></p>
	</div>
</div>
<script>
	<?if(strlen($arResult["LAST_LOGIN"])>0):?>
		try{document.form_auth.USER_PASSWORD.focus();}catch(e){}
	<?else:?>
		try{document.form_auth.USER_LOGIN.focus();}catch(e){}
	<?endif?>
</script>
<script>
	var authAjaxPath = "<?=$templateFolder?>/ajax.php";
</script>