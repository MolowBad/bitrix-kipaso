<?

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

\Bitrix\Main\Loader::requireModule("sale");
\Bitrix\Main\Loader::requireModule("catalog");
\Bitrix\Main\Loader::requireModule("iblock");
\Bitrix\Main\Loader::requireModule("dw.deluxe");
\Bitrix\Main\Loader::requireModule("highloadblock");

global $USER;

if(!$USER->IsAdmin()){
	return false;
}

$arResult = [];

$dwSettings = DwSettings::getInstance();

$arResult["CURRENT_SETTINGS"] = $dwSettings->getCurrentSettings();

$arResult["TEMPLATES"]["SETTINGS"] = $dwSettings->scanTemplate($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH);
$arResult["TEMPLATES"]["HEADERS"] = $dwSettings->scanHeaders($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/headers/");
$arResult["TEMPLATES"]["THEMES"] = $dwSettings->scanThemes($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/themes/");
$arResult["TEMPLATES"]["THEMES_COLORS"] = $dwSettings->getThemeColors();

if(!empty($arResult["TEMPLATES"]["THEMES"])){
	$arResult["TEMPLATES"]["BACKGROUND_VARIANTS"] = $dwSettings->getBgVariantsByData($arResult["TEMPLATES"]["THEMES"]);
}

if(empty($arResult["TEMPLATES"]["BACKGROUND_VARIANTS"])){
	$arResult["TEMPLATES"]["THEMES"] = ["VARIANTS" => $arResult["TEMPLATES"]["THEMES"]];
}

$arResult["IBLOCKS"] = $dwSettings->getIblocksWithProperty();
$arResult["AGREEMENTS"] = $dwSettings->getActiveAgreements();
$arResult["PRICE_CODES"] = $dwSettings->getPriceCodes();

if(!empty($arResult["IBLOCKS"]["PRODUCT_IBLOCKS"])){
	$arResult["PRODUCT_IBLOCKS"] = $arResult["IBLOCKS"]["PRODUCT_IBLOCKS"];
}

if(!empty($arResult["IBLOCKS"]["SKU_IBLOCKS"])){
	$arResult["SKU_IBLOCKS"] = $arResult["IBLOCKS"]["SKU_IBLOCKS"];
}

$this->setResultCacheKeys([]);
$this->IncludeComponentTemplate();
