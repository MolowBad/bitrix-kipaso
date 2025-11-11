<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<h1><?=GetMessage("REGISTER_TITLE")?></h1>
<ul id="authMenu">
  <li><a href="<?=SITE_DIR?>auth/" rel="nofollow"><?=GetMessage("AUTH_TITLE")?></a></li>
  <li><a href="<?=$arResult["AUTH_REGISTER_URL"]?>" rel="nofollow" class="selected"><?=GetMessage("AUTH_REGISTER")?></a></li>
  <li><a href="<?=SITE_DIR?>auth/?forgot_password=yes" rel="nofollow"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a></li>
</ul>
<?
//check phone registration
if($arResult["SHOW_SMS_FIELD"] == true){
	CJSCore::Init("phone_auth");
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
<div class="bx-auth">

	<noindex>

		<?if($arResult["SHOW_SMS_FIELD"] == true):?>

			<form method="post" action="<?=$arResult["AUTH_URL"]?>" name="regform" class="bx-auth-register-form">
				<input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult["SIGNED_DATA"])?>" />
				<div class="bx-auth-form-line-container">
					<div class="bx-auth-form-line">
						<div class="bx-authform-label-container"><?=GetMessage("main_register_sms_code")?><span class="starrequired">*</span></div>
						<div class="bx-authform-input-container">
							<input size="30" type="text" name="SMS_CODE" value="<?=htmlspecialcharsbx($arResult["SMS_CODE"])?>" autocomplete="off" data-required="Y" />
						</div>
					</div>
				</div>
				<div class="alert small"><?=strip_tags(nl2br(htmlspecialcharsbx(str_replace(array(".", "<br>", "<br />"), "\n", ShowMessage($arParams["~AUTH_RESULT"])))), "<br>")?></div>
				<div class="bx-auth-submit-container">
					<input type="submit" name="code_submit_button" value="<?echo GetMessage("main_register_sms_send")?>" class="btn btn-primary submit" />
				</div>
			</form>

			<script>
				new BX.PhoneAuth({
					containerId: 'bx_register_resend',
					errorContainerId: 'bx_register_error',
					interval: <?=$arResult["PHONE_CODE_RESEND_INTERVAL"]?>,
					data:
						<?=CUtil::PhpToJSObject(array(
							'signedData' => $arResult["SIGNED_DATA"],
						))?>,
					onError:
						function(response){
							var errorDiv = BX('bx_register_error');
							var errorNode = BX.findChildByClassName(errorDiv, 'errortext');
							errorNode.innerHTML = '';
							for(var i = 0; i < response.errors.length; i++){
								errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
							}
							errorDiv.style.display = '';
						}
				});
			</script>

			<div class="bx-register-phone-messages">
				<div id="bx_register_error" style="display:none"><?ShowError("error")?></div>
				<div id="bx_register_resend"></div>
			</div>

		<?elseif(!$arResult["SHOW_EMAIL_SENT_CONFIRMATION"]):?>
			<div class="registerText"><?=GetMessage("REGISTER_TEXT")?></div>
			<form method="post" action="<?=$arResult["AUTH_URL"]?>" name="bform" enctype="multipart/form-data" class="bx-auth-register-form">

				<input type="hidden" name="AUTH_FORM" value="Y" />
				<input type="hidden" name="TYPE" value="REGISTRATION" />
				<div class="bx-auth-form-line-container">
					<div class="bx-auth-form-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_NAME")?></div>
						<div class="bx-authform-input-container">
							<input type="text" name="USER_NAME" maxlength="50" value="<?=$arResult["USER_NAME"]?>" class="bx-auth-input" />
						</div>
					</div>

					<div class="bx-auth-form-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_LAST_NAME")?></div>
						<div class="bx-authform-input-container">
							<input type="text" name="USER_LAST_NAME" maxlength="50" value="<?=$arResult["USER_LAST_NAME"]?>" class="bx-auth-input" />
						</div>
					</div>

					<div class="bx-auth-form-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_LOGIN_MIN")?><span class="starrequired">*</span></div>
						<div class="bx-authform-input-container">
							<input type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["USER_LOGIN"]?>" class="bx-auth-input" data-required="Y" />
						</div>
					</div>

					<?if($arResult["EMAIL_REGISTRATION"]):?>

						<div class="bx-auth-form-line">
							<div class="bx-authform-label-container"><?=GetMessage("AUTH_EMAIL")?><?if($arResult["EMAIL_REQUIRED"]):?><span class="starrequired">*</span><?endif?></div>
							<div class="bx-authform-input-container">
								<input type="text" name="USER_EMAIL" maxlength="255" value="<?=$arResult["USER_EMAIL"]?>" class="bx-auth-input"<?if($arResult["EMAIL_REQUIRED"]):?> data-required="Y" <?endif;?> />
							</div>
						</div>

					<?endif?>

					<?if($arResult["PHONE_REGISTRATION"]):?>

						<div class="bx-auth-form-line">
							<div class="bx-authform-label-container"><?echo GetMessage("main_register_phone_number")?><?if($arResult["PHONE_REQUIRED"]):?><span class="starrequired">*</span><?endif?></div>
							<div class="bx-authform-input-container">
								<input type="text" name="USER_PHONE_NUMBER" maxlength="255" value="<?=$arResult["USER_PHONE_NUMBER"]?>" class="bx-auth-input register-user-phone-field" <?if($arResult["PHONE_REQUIRED"]):?> data-required="Y" <?endif;?>/>
							</div>
						</div>
						<?if(!empty($arParams["MASKED_INPUT_FORMAT"])):?>
							<script>
								$(function(){
									$(".register-user-phone-field").mask("<?=$arParams["MASKED_INPUT_FORMAT"]?>");
								});
							</script>
						<?endif;?>
					<?endif?>

					<div class="bx-auth-form-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_PASSWORD_REQ")?><span class="starrequired">*</span></div>
						<div class="bx-authform-input-container">
							<input type="password" name="USER_PASSWORD" maxlength="255" value="<?=$arResult["USER_PASSWORD"]?>" class="bx-auth-input" autocomplete="off" data-required="Y" />
						</div>
					</div>

					<?if($arResult["SECURE_AUTH"]):?>
						<div class="bx-auth-form-line">
							<span class="bx-auth-secure" id="bx_auth_secure" title="<?echo GetMessage("AUTH_SECURE_NOTE")?>" style="display:none">
								<div class="bx-auth-secure-icon"></div>
							</span>
							<noscript>
							<span class="bx-auth-secure" title="<?echo GetMessage("AUTH_NONSECURE_NOTE")?>">
								<div class="bx-auth-secure-icon bx-auth-secure-unlock"></div>
							</span>
							</noscript>
							<script>
								document.getElementById('bx_auth_secure').style.display = 'inline-block';
							</script>
						</div>
					<?endif?>

					<div class="bx-auth-form-line">
						<div class="bx-authform-label-container"><?=GetMessage("AUTH_CONFIRM")?><span class="starrequired">*</span></div>
						<div class="bx-authform-input-container">
							<input type="password" name="USER_CONFIRM_PASSWORD" maxlength="255" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>" class="bx-auth-input" autocomplete="off" data-required="Y" />
						</div>
					</div>

					<?if($arResult["USER_PROPERTIES"]["SHOW"] == "Y"):?>

						<div class="bx-auth-form-line">
							<div class="bx-authform-label-container"><?=strlen(trim($arParams["USER_PROPERTY_NAME"])) > 0 ? $arParams["USER_PROPERTY_NAME"] : GetMessage("USER_TYPE_EDIT_TAB")?></div>
						</div>

						<?foreach ($arResult["USER_PROPERTIES"]["DATA"] as $FIELD_NAME => $arUserField):?>
							<div class="bx-auth-form-line">
								<div class="bx-authform-label-container"><?if($arUserField["MANDATORY"]=="Y"):?><span class="starrequired">*</span><?endif;?><?=$arUserField["EDIT_FORM_LABEL"]?>:</div>
								<div class="bx-authform-input-container">
									<?$APPLICATION->IncludeComponent(
										"bitrix:system.field.edit",
										$arUserField["USER_TYPE"]["USER_TYPE_ID"],
										array("bVarsFromForm" => $arResult["bVarsFromForm"], "arUserField" => $arUserField, "form_name" => "bform"),
										null,
										array("HIDE_ICONS"=>"Y")
									);?>
								</div>
							</div>
						<?endforeach;?>

					<?endif;?>
					<?if(!empty($arResult["USE_CAPTCHA"]) && $arResult["USE_CAPTCHA"] == "Y"):?>
						<div class="bx-auth-form-line">
							<div class="bx-auth-captha-container">
								<div class="bx-auth-input-line">
									<div class="bx-authform-label-container"><?=GetMessage("CAPTCHA_REGF_PROMT")?><span class="starrequired">*</span></div>
									<div class="bx-authform-input-container">
										<input type="hidden" name="captcha_sid" value="<?=$arResult["CAPTCHA_CODE"]?>" />
										<div class="bx-auth-form-captha-table">
											<div class="bx-auth-form-captha-field">
												<input type="text" name="captcha_word" maxlength="50" value="" class="bx-auth-captcha-field" data-required="Y" />
											</div>
											<div class="bx-auth-form-captha-image">
												<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?endif?>
				</div>

				<div class="bx-authform-formgroup-container-line">
					<div class="bx-authform-formgroup-container">
						<div class="bx-authform-input-container">
							<input type="checkbox" name="USER_AGREEMENT" maxlength="255" value="Y" data-required="Y" id="userPersonalInfoReg" /><label for="userPersonalInfoReg"><?=GetMessage("USER_PERSONAL_INFO")?>*</label>
						</div>
					</div>
				</div>

				<div class="alert small"><?=strip_tags(nl2br(htmlspecialcharsbx(str_replace(array(".", "<br>", "<br />"), "\n", ShowMessage($arParams["~AUTH_RESULT"])))), "<br>")?></div>
				<div class="bx-auth-submit-container"><input type="submit" name="Register" value="<?=GetMessage("AUTH_REGISTER")?>" class="btn btn-primary submit" /></div>

			</form>

			<?if($arResult["SHOW_EMAIL_SENT_CONFIRMATION"]):?>
				<p class="bx-auth-info-message"><?echo GetMessage("AUTH_EMAIL_SENT")?></p>
			<?endif;?>
			<?if(!$arResult["SHOW_EMAIL_SENT_CONFIRMATION"] && $arResult["USE_EMAIL_CONFIRMATION"] === "Y"):?>
				<p class="bx-auth-info-message"><b><?echo GetMessage("AUTH_EMAIL_WILL_BE_SENT")?></b></p>
			<?endif?>
			<p class="bx-auth-info-message"><?echo $arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"];?></p>
			<p class="bx-auth-info-message"><span class="starrequired">*</span><?=GetMessage("AUTH_REQ")?></p>
			<script>document.bform.USER_NAME.focus();</script>

		<?else:?>
			<div class="bx-auth-success-heading h2 ff-medium"><?=strip_tags(nl2br(htmlspecialcharsbx(str_replace(array(".", "<br>", "<br />"), "\n", ShowMessage($arParams["~AUTH_RESULT"])))), "<br>")?></div>
			<?if($arResult["SHOW_EMAIL_SENT_CONFIRMATION"]):?>
				<p class="bx-auth-info-message"><?echo GetMessage("AUTH_EMAIL_SENT")?></p>
			<?endif;?>
			<?if(!$arResult["SHOW_EMAIL_SENT_CONFIRMATION"] && $arResult["USE_EMAIL_CONFIRMATION"] === "Y"):?>
				<p class="bx-auth-info-message"><?echo GetMessage("AUTH_EMAIL_WILL_BE_SENT")?></p>
			<?endif?>
			<a href="<?=SITE_DIR?>auth/" class="btn-simple btn-small bx-auth-button-link"><?=GetMessage("AUTH_TITLE")?></a>
		<?endif?>

	</noindex>

</div>
