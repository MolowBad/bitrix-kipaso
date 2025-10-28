<?
//push captcha
function getCaptcha($captchaCode, $captchaTitle){
	return '
		<input type="hidden" name="captcha_sid" value="'.$captchaCode.'>" />
		<div class="dbg_captha">
			<div class="bx-authform-label-container">'.$captchaTitle.'</div>
			<div class="bx-captcha"><img src="/bitrix/tools/captcha.php?captcha_sid='.$captchaCode.'" width="180" height="40" alt="CAPTCHA" class="bx-auth-captcha-picture" /></div>
			<div class="bx-authform-input-container">
				<input type="text" name="captcha_word" maxlength="50" autocomplete="off" value="" />
			</div>
		</div>
	';
}
?>