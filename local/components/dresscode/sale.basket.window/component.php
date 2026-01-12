<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \DigitalWeb\Basket as DwBasket;

if(empty(intval($arParams["PRODUCT_ID"]))){
	return false;
}

if(!\Bitrix\Main\Loader::includeModule("dw.deluxe")){
	return false;
}

if(empty($arParams["SITE_ID"])){
	$arParams["SITE_ID"] = \Bitrix\Main\Context::getCurrent()->getSite();
}

if(empty($arParams["HIDE_MEASURES"])){
	$arParams["HIDE_MEASURES"] = "N";
}

$basket = DwBasket::getInstance();
$currencyCode = $basket->getCurrencyCode();
$arBasketItems = $basket->getBasketItems();
$arProducts = $basket->addProductsInfo($arBasketItems);
$arProducts = $basket->addProductPrices($arProducts);

foreach($arProducts as $basketId => $arNextProduct){
	if($arNextProduct["PRODUCT_ID"] == $arParams["PRODUCT_ID"]){
		$arResult = $arNextProduct; break(1);
	}
}

if(!empty($arResult)){

	if($arParams["HIDE_MEASURES"] != "Y"){
		$arResult["MEASURES"] = $basket->getMeasures();
	}

	$arResult["BASE_SUM_FORMATED"] = \CCurrencyLang::CurrencyFormat(($arResult["BASE_PRICE"] * $arResult["QUANTITY"]), $currencyCode);
	$arResult["SUM_FORMATED"] = \CCurrencyLang::CurrencyFormat(($arResult["PRICE"] * $arResult["QUANTITY"]), $currencyCode);

}

$this->IncludeComponentTemplate();
