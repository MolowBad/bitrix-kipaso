<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

$shouldUsePhoneMask = isset($arParams["USE_PHONE_MASK"]) && $arParams["USE_PHONE_MASK"] === "Y";
$phoneMaskFormat = isset($arParams["PHONE_MASK_FORMAT"]) && is_string($arParams["PHONE_MASK_FORMAT"])
	? trim($arParams["PHONE_MASK_FORMAT"])
	: "+7 (999) 999-99-99";

$imageId = null;
$image = null;

if(isset($arResult["PRODUCT"]["DETAIL_PICTURE"]) && (int)$arResult["PRODUCT"]["DETAIL_PICTURE"] > 0) {
	$imageId = $arResult["PRODUCT"]["DETAIL_PICTURE"];
}
elseif(isset($arResult["PRODUCT"]["PROPERTIES"]["MORE_PHOTO"]["VALUE"])) {
	$morePhotoValue = $arResult["PRODUCT"]["PROPERTIES"]["MORE_PHOTO"]["VALUE"];
	if (is_array($morePhotoValue)) {
		$imageId = $morePhotoValue[array_key_first($morePhotoValue)] ?? null;
	}
}

if($imageId !== null) {
	$image = CFile::ResizeImageGet($imageId, ["width" => 270, "height" => 270], BX_RESIZE_IMAGE_PROPORTIONAL, true);
}

if($image === null) {
	$image = [
		'src' => SITE_TEMPLATE_PATH . "/images/empty.svg",
		'width' => 270,
		'height' => 270
	];
}

?>

<div class="request-price-form">
	<div class="request-price-form__inner">
		<div class="request-price-form__inner-proxy">
			<header class="request-price-form__header">
				<span class="request-price-form__heading">
					<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_FORM_TITLE")?>
				</span>
				<button
					type="button"
					class="request-price-form__close-button request-price-form__close-button--header"
				>
				</button>
			</header>
			<div class="request-price-form__states">
				<div class="request-price-form__state request-price-form__state--input request-price-form__state--active">
					<div class="request-price-form__product">
						<a
							href="<?=$arResult["PRODUCT"]["DETAIL_PAGE_URL"]?>"
							class="request-price-form__product-image-container"
							target="_blank"
						>
							<img
								src="<?=$image["src"]?>"
								class="request-price-form__product-image"
								width="<?=$image["width"]?>"
								height="<?=$image["height"]?>"
								alt="<?=$arResult["PRODUCT"]["NAME"]?>"
								title="<?=$arResult["PRODUCT"]["NAME"]?>"
							>
						</a>
						<a
							href="<?=$arResult["PRODUCT"]["DETAIL_PAGE_URL"]?>"
							class="request-price-form__product-name"
							target="_blank"
						>
							<?=$arResult["PRODUCT"]["NAME"]?>
						</a>
					</div>
					<div class="request-price-form__form-container">
						<span class="request-price-form__form-heading">
							<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_FORM_SUBTITLE")?>
						</span>
						<form action="#" class="request-price-form__form" method="post" autocomplete="off">
							<input
								type="text"
								name="name"
								value="<?=$arResult["USER_DATA"]["FULL_NAME"] ?? ''?>"
								placeholder="<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_FIELD_NAME")?>"
								autocomplete="off"
								class="request-price-form__input request-price-form__field"
							>
							<input
								type="text"
								name="telephone"
								value="<?=$arResult["USER_DATA"]["PERSONAL_MOBILE"] ?? ''?>"
								data-required="Y"
								placeholder="<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_FIELD_PHONE")?>"
								autocomplete="off"
								class="request-price-form__input request-price-form__input--phone request-price-form__field"
							>
							<input type="hidden" name="productId" value="<?=$arResult["PRODUCT"]["ID"]?>">
							<input type="hidden" name="languageId" value="<?=LANGUAGE_ID?>">
							<input type="hidden" name="siteId" value="<?=SITE_ID?>">
							<textarea
								name="comment"
								placeholder="<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_FIELD_COMMENT")?>"
								autocomplete="off"
								class="request-price-form__textarea request-price-form__field"
							></textarea>
							<label class="request-price-form__user-agreement request-price-form__field-label">
								<input
									type="checkbox"
									name="userAgreement"
									data-required="Y"
									value="Y"
									class="request-price-form__checkbox request-price-form__field"
								>
								<?=Loc::getMessage(
									"RP0_REQUEST_PRICE_FORM_MODAL_AGREEMENT_TEXT",
									[
										"#LINK#" => '<a href="#" class="userAgreement pilink">' .
											Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_AGREEMENT_LINK") . '</a>'
									]
								)?>*
							</label>
							<button type="submit" class="request-price-form__submit-button btn-simple btn-medium">
								<img
									src="<?=SITE_TEMPLATE_PATH?>/images/request.svg"
									alt="<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_SUBMIT_BUTTON")?>"
								> <?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_SUBMIT_BUTTON")?>
							</button>
						</form>
						<div class="request-price-form__errors"></div>
					</div>
				</div>
				<div class="request-price-form__success request-price-form__state request-price-form__state--success">
					<span class="request-price-form__success-heading">
						<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_SUCCESS_TITLE")?>
					</span>
					<p class="request-price-form__success-message">
						<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_SUCCESS_MESSAGE")?>
					</p>
					<button
						type="button"
						class="request-price-form__close-button request-price-form__close-button--success btn-simple btn-medium"
					>
						<?=Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_CLOSE_WINDOW")?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	(() => {
		const requestPriceForm = new DwRequestPriceForm();
		requestPriceForm.mount();
		<?if($shouldUsePhoneMask):?>
			$(".request-price-form__input--phone").mask("<?=$phoneMaskFormat?>");
		<?endif;?>
	})();
</script>
