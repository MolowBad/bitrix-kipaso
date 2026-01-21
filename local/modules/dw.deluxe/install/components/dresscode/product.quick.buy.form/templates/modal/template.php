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

<div class="product-quick-buy-form">
	<div class="product-quick-buy-form__inner">
		<div class="product-quick-buy-form__inner-proxy">
			<header class="product-quick-buy-form__header">
				<span class="product-quick-buy-form__heading">
					<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_FORM_TITLE")?>
				</span>
				<button
					type="button"
					class="product-quick-buy-form__close-button product-quick-buy-form__close-button--header"
				>
				</button>
			</header>
			<div class="product-quick-buy-form__states">
				<div class="product-quick-buy-form__state product-quick-buy-form__state--input product-quick-buy-form__state--active">
					<div class="product-quick-buy-form__product">
						<a
							href="<?=$arResult["PRODUCT"]["DETAIL_PAGE_URL"]?>"
							class="product-quick-buy-form__product-image-container"
							target="_blank"
						>
							<img
								src="<?=$image["src"]?>"
								class="product-quick-buy-form__product-image"
								width="<?=$image["width"]?>"
								height="<?=$image["height"]?>"
								alt="<?=$arResult["PRODUCT"]["NAME"]?>"
								title="<?=$arResult["PRODUCT"]["NAME"]?>"
							>
						</a>
						<a
							href="<?=$arResult["PRODUCT"]["DETAIL_PAGE_URL"]?>"
							class="product-quick-buy-form__product-name"
							target="_blank"
						>
							<?=$arResult["PRODUCT"]["NAME"]?>
						</a>
						<div class="product-quick-buy-form__product-prices">
							<span class="product-quick-buy-form__product-actual-price">
								<?=$arResult["PRODUCT"]["PRICE"]["ACTUAL_FORMATTED"]?>
							</span>
							<?if($arResult["PRODUCT"]["PRICE"]["HAS_DISCOUNT"]):?>
								<s class="product-quick-buy-form__product-discount-price">
									<?=$arResult["PRODUCT"]["PRICE"]["BASE_FORMATTED"]?>
								</s>
							<?endif;?>
						</div>
					</div>
					<div class="product-quick-buy-form__form-container">
						<span class="product-quick-buy-form__form-heading">
							<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_FORM_SUBTITLE")?>
						</span>
						<form action="#" class="product-quick-buy-form__form" method="post" autocomplete="off">
						<input
								type="text"
								name="name"
								value="<?=$arResult["USER_DATA"]["FULL_NAME"] ?? ''?>"
								placeholder="<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_FIELD_NAME")?>"
								autocomplete="off"
								class="product-quick-buy-form__input product-quick-buy-form__input--email product-quick-buy-form__field"
							>
							<input
								type="text"
								name="telephone"
								value="<?=$arResult["USER_DATA"]["PERSONAL_MOBILE"] ?? ''?>"
								data-required="Y"
								placeholder="<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_FIELD_PHONE")?>"
								autocomplete="off"
								class="product-quick-buy-form__input product-quick-buy-form__input--phone product-quick-buy-form__field"
							>
							<input
								type="text"
								name="email"
								value="<?=$arResult["USER_DATA"]["EMAIL"] ?? ''?>"
								placeholder="<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_FIELD_EMAIL")?>"
								autocomplete="off"
								class="product-quick-buy-form__input product-quick-buy-form__field"
							>
							<input type="hidden" name="productId" value="<?=$arResult["PRODUCT"]["ID"]?>">
							<input type="hidden" name="languageId" value="<?=LANGUAGE_ID?>">
							<input type="hidden" name="siteId" value="<?=SITE_ID?>">
							<textarea
								name="comment"
								placeholder="<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_FIELD_COMMENT")?>"
								autocomplete="off"
								class="product-quick-buy-form__textarea product-quick-buy-form__field"
							></textarea>
							<label class="product-quick-buy-form__user-agreement product-quick-buy-form__field-label">
								<input
									type="checkbox"
									name="userAgreement"
									data-required="Y"
									value="Y"
									class="product-quick-buy-form__checkbox product-quick-buy-form__field"
								>
								<?=Loc::getMessage(
									"PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_AGREEMENT_TEXT",
									[
										"#LINK#" => '<a href="#" class="userAgreement pilink">' .
											Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_AGREEMENT_LINK") . '</a>'
									]
								)?>*
							</label>
							<button type="submit" class="product-quick-buy-form__submit-button btn-simple btn-medium">
								<img
									src="<?=SITE_TEMPLATE_PATH?>/images/request.svg"
									alt="<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_SUBMIT_BUTTON")?>"
								> <?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_SUBMIT_BUTTON")?>
							</button>
						</form>
						<div class="product-quick-buy-form__errors"></div>
					</div>
				</div>
				<div class="product-quick-buy-form__success product-quick-buy-form__state product-quick-buy-form__state--success">
					<div class="product-quick-buy-form__success-inner">
						<div class="product-quick-buy-form__success-order-number-container">
							<div class="product-quick-buy-form__success-order-number-label">
								<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_SUCCESS_ORDER_LABEL")?>
							</div>
							<div class="product-quick-buy-form__success-order-number">xxx-xxx</div>
						</div>
						<div class="product-quick-buy-form__success-content">
							<span class="product-quick-buy-form__success-heading">
								<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_SUCCESS_TITLE")?>
							</span>
							<p class="product-quick-buy-form__success-message">
								<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_SUCCESS_MESSAGE")?>
							</p>
							<button
								type="button"
								class="product-quick-buy-form__close-button product-quick-buy-form__close-button--success btn-simple btn-medium"
							>
								<?=Loc::getMessage("PQ0_PRODUCT_QUICK_BUY_FORM_MODAL_CLOSE_WINDOW")?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	(() => {
		const productQuickBuyForm = new DwProductQuickBuyForm();
		productQuickBuyForm.mount();
		<?if($shouldUsePhoneMask):?>
			$(".product-quick-buy-form__input--phone").mask("<?=$phoneMaskFormat?>");
		<?endif;?>
	})();
</script>
