<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//check phone registration
if($arResult["PHONE_REGISTRATION"]){
	CJSCore::Init("phone_auth");
}
?>
<h1><?=GetMessage("AUTH_CHANGE_PASSWORD")?></h1>
<ul id="authMenu">
	<li><a href="<?=SITE_DIR?>auth/" rel="nofollow"><?=GetMessage("AUTH_TITLE")?></a></li>
	<li><a href="<?=SITE_DIR?>auth/?register=yes" rel="nofollow"><?=GetMessage("AUTH_REGISTER")?></a></li>
	<li><a href="<?=SITE_DIR?>auth/?forgot_password=yes" rel="nofollow" class="selected"><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></a></li>
</ul>

<div class="bx-auth">
	<?if($arResult["SHOW_FORM"]):?>
		<form method="post" action="<?=$arResult["AUTH_FORM"]?>" name="bform" class="bx-auth-change-pass-form">

			<div id="bx_chpass_error" style="display:none"><?ShowError("error")?></div>
			<div id="bx_chpass_resend"></div>

			<?if(strlen($arResult["BACKURL"]) > 0):?>
				<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
			<?endif;?>

			<input type="hidden" name="AUTH_FORM" value="Y">
			<input type="hidden" name="TYPE" value="CHANGE_PWD">

			<?if($arResult["PHONE_REGISTRATION"]):?>

				<div class="bx-auth-input-line">
					<div class="bx-authform-label-container"><?=GetMessage("sys_auth_chpass_phone_number")?></div>
					<div class="bx-authform-input-container">
						<input type="text" value="<?=htmlspecialcharsbx($arResult["USER_PHONE_NUMBER"])?>" class="bx-auth-input" disabled="disabled"/>
						<input type="hidden" name="USER_PHONE_NUMBER" value="<?=htmlspecialcharsbx($arResult["USER_PHONE_NUMBER"])?>" />
					</div>
				</div>

				<div class="bx-auth-input-line">
					<div class="bx-authform-label-container"><?=GetMessage("sys_auth_chpass_code")?><span class="starrequired">*</span></div>
					<div class="bx-authform-input-container">
						<input type="text" name="USER_CHECKWORD" maxlength="50" value="<?=$arResult["USER_CHECKWORD"]?>" class="bx-auth-input" autocomplete="off" data-required="Y" />
					</div>
				</div>

			<?else:?>

				<div class="bx-auth-input-line">
					<div class="bx-authform-label-container"><?=GetMessage("AUTH_LOGIN")?><span class="starrequired">*</span></div>
					<div class="bx-authform-input-container">
						<input type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["LAST_LOGIN"]?>" class="bx-auth-input" data-required="Y"/>
						<input type="hidden" name="USER_PHONE_NUMBER" value="<?=htmlspecialcharsbx($arResult["USER_PHONE_NUMBER"])?>" />
					</div>
				</div>

				<div class="bx-auth-input-line">
					<div class="bx-authform-label-container"><?=GetMessage("AUTH_CHECKWORD")?><span class="starrequired">*</span></div>
					<div class="bx-authform-input-container">
						<input type="text" name="USER_CHECKWORD" maxlength="50" value="<?=$arResult["USER_CHECKWORD"]?>" class="bx-auth-input" autocomplete="off" data-required="Y" />
					</div>
				</div>

			<?endif?>

			<div class="bx-auth-input-line">
				<div class="bx-authform-label-container"><?=GetMessage("AUTH_NEW_PASSWORD_REQ")?><span class="starrequired">*</span></div>
				<div class="bx-authform-input-container">
					<input type="password" name="USER_PASSWORD" maxlength="255" value="<?=$arResult["USER_PASSWORD"]?>" class="bx-auth-input" autocomplete="off" data-required="Y" />
				</div>
			</div>

			<?if($arResult["SECURE_AUTH"]):?>
				<span class="bx-auth-secure" id="bx_auth_secure" title="<?=GetMessage("AUTH_SECURE_NOTE")?>" style="display:none">
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
			<?endif?>

			<div class="bx-auth-input-line">
				<div class="bx-authform-label-container"><?=GetMessage("AUTH_NEW_PASSWORD_CONFIRM")?><span class="starrequired">*</span></div>
				<div class="bx-authform-input-container">
					<input type="password" name="USER_CONFIRM_PASSWORD" maxlength="255" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>" class="bx-auth-input" autocomplete="off" data-required="Y" />
				</div>
			</div>

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
							<input type="text" name="captcha_word" maxlength="50" value="" data-required="Y" />
						</div>
					</div>
				</div>
			<?endif?>
			<div class="alert small"><?=strip_tags(nl2br(htmlspecialcharsbx(str_replace(array(".", "<br>", "<br />"), "\n", ShowMessage($arParams["~AUTH_RESULT"])))), "<br>")?></div>
			<div class="bx-auth-input-line">
				<input type="submit" name="change_pwd" value="<?=GetMessage("AUTH_CHANGE")?>" class="btn-simple btn-medium" />
			</div>

		</form>

		<p><b><?=$arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"];?></b></p>
		<p><b><span class="starrequired">*</span><?=GetMessage("AUTH_REQ")?></b></p>

		<?if($arResult["PHONE_REGISTRATION"]):?>
			<script>
				new BX.PhoneAuth({
					containerId: 'bx_chpass_resend',
					errorContainerId: 'bx_chpass_error',
					interval: <?=$arResult["PHONE_CODE_RESEND_INTERVAL"]?>,
					data:
						<?=CUtil::PhpToJSObject([
							'signedData' => $arResult["SIGNED_DATA"]
						])?>,
					onError:
						function(response)
						{
							var errorDiv = BX('bx_chpass_error');
							var errorNode = BX.findChildByClassName(errorDiv, 'errortext');
							errorNode.innerHTML = '';
							for(var i = 0; i < response.errors.length; i++)
							{
								errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
							}
							errorDiv.style.display = '';
						}
				});
			</script>
		<?endif?>
	<?endif?>
</div>