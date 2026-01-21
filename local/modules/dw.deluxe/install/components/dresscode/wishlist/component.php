<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(
	!CModule::IncludeModule("iblock") ||
	!CModule::IncludeModule("catalog")
){
	return;
}

$arParams["LAZY_LOAD_PICTURES"] = !empty($arParams["LAZY_LOAD_PICTURES"])
	? $arParams["LAZY_LOAD_PICTURES"]
	: "N";

global $arrFilter;

$arResult["SHOW_TEMPLATE"] = true;

if (empty($arParams["IBLOCK_ID"])) {
	return;
}

$arOffersSkuInfo = CCatalogSKU::GetInfoByProductIBlock($arParams["IBLOCK_ID"]);

if(!empty($_SESSION["WISHLIST_LIST"]["ITEMS"])){

	if(!empty($arOffersSkuInfo)){
		$arrFilter[] = array(
			"LOGIC" => "OR",
			array(
				"ID" => CIBlockElement::SubQuery(
					"ID",
					array(
						"IBLOCK_ID" => $arOffersSkuInfo["IBLOCK_ID"],
						"PROPERTY_" . $arOffersSkuInfo["SKU_PROPERTY_ID"] => $_SESSION["WISHLIST_LIST"]["ITEMS"]
					)
				)
			),
			array(
				"ID" => $_SESSION["WISHLIST_LIST"]["ITEMS"]
			)
		);
	}
	else{
		$arrFilter["ID"] = $_SESSION["WISHLIST_LIST"]["ITEMS"];
	}

}

else{
	$arResult["SHOW_TEMPLATE"] = false;
}

$arParams["FILTER_NAME"] = "arrFilter";

$this->IncludeComponentTemplate();
