<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

$extractStringFromArray = function(array $array, string $key, $defaultValue = null) {
	$value = $array[$key] ?? null;

	if (!is_string($value)) {
		return $defaultValue;
	}

	$value = trim($value);

	if($value === '') {
		return $defaultValue;
	}

	return $value;
};

$heading = $extractStringFromArray(
	$arParams,
	'~HEADING',
	Loc::getMessage('CN0_COOKIE_NOTICE_DEFAULT_HEADING')
);

$privacyPolicyUrl = $extractStringFromArray($arParams, 'PRIVACY_POLICY_URL', '#SITE_DIR#privacy-policy/');
$privacyPolicyUrl = str_replace('#SITE_DIR#', SITE_DIR, $privacyPolicyUrl);

$defaultText = Loc::getMessage('CN0_COOKIE_NOTICE_DEFAULT_TEXT');

$text = $extractStringFromArray($arParams, '~TEXT', $defaultText);
$text = str_replace('#PRIVACY_POLICY_URL#', $privacyPolicyUrl, $text);

$confirmButtonText = $extractStringFromArray(
	$arParams,
	'~CONFIRM_BUTTON_TEXT',
	Loc::getMessage('CN0_COOKIE_NOTICE_DEFAULT_CONFIRM_BUTTON_TEXT')
);
?>

<div class="cookie-notice">
	<div class="cookie-notice__inner">
		<div class="cookie-notice__content">
			<span class="cookie-notice__heading"><?=$heading?></span>
			<p class="cookie-notice__text"><?=$text?></p>
		</div>
		<div class="cookie-notice__actions">
		<button
			type="button"
			class="cookie-notice__confirm-button btn-simple btn-medium"
		>
			<?=$confirmButtonText?>
		</button>
		</div>
	</div>
</div>

<script>
	(() => {
		const cookieNotice = new DwCookieNotice();
		cookieNotice.mount();
	})();
</script>
