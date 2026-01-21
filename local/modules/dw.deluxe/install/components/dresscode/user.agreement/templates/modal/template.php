<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);
?>

<div class="user-agreement">
	<div class="user-agreement__inner">
		<div class="user-agreement__inner-proxy">
			<header class="user-agreement__header">
				<span class="user-agreement__heading"><?=$arResult['TITLE']?></span>
				<button
					type="button"
					class="user-agreement__close-button user-agreement__close-button--primary"
					title="<?=Loc::getMessage('UA0_USER_AGREEMENT_MODAL_CLOSE_BUTTON_TEXT')?>"
				></button>
			</header>
			<div class="user-agreement__content">
				<?=$arResult['IS_HTML'] ? $arResult['HTML'] : nl2br(htmlspecialcharsbx($arResult['TEXT']))?>
			</div>
			<footer class="user-agreement__footer">
				<button type="button" class="user-agreement__close-button btn-simple btn-small">
					<?=Loc::getMessage('UA0_USER_AGREEMENT_MODAL_CLOSE_BUTTON_TEXT')?>
				</button>
			</footer>
		</div>
	</div>
</div>
<script>
	(() => {
		const agreement = new DwUserAgreement();
		agreement.mount();
	})();
</script>
