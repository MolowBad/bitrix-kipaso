<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if (
	!CModule::IncludeModule("sale") ||
	!CModule::IncludeModule("catalog") ||
	!CModule::IncludeModule("iblock") ||
	!CModule::IncludeModule("dw.deluxe") ||
	!CModule::IncludeModule("highloadblock")
){
	return;
}

if(empty($arParams["PRODUCT_ID"]) || empty($arParams["IBLOCK_ID"])){
	return;
}

if (!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 36000000;
}

if (!isset($arParams["SALES_COUNT"])){
	$arParams["SALES_COUNT"] = 1;
}

global $USER;

$cacheID = $USER->GetGroups() . " / " . $arParams["PRODUCT_ID"];

if ($this->StartResultCache($arParams["CACHE_TIME"], $cacheID)){

	$arSales = array();

	$arSelect = Array("ID", "NAME", "IBLOCK_ID", "IBLOCK_TYPE", "PREVIEW_TEXT", "DETAIL_TEXT", "PREVIEW_PICTURE", "DETAIL_PICTURE", "DATE_ACTIVE_FROM", "DETAIL_PAGE_URL");
	$arFilter = Array("IBLOCK_ID" => IntVal($arParams["IBLOCK_ID"]), "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "PROPERTY_PRODUCTS_REFERENCE" => $arParams["PRODUCT_ID"]);
	$rsSales = CIBlockElement::GetList(Array("SORT" => "ASC"), $arFilter, false, Array("nTopCount" => $arParams["SALES_COUNT"]), $arSelect);
	while($oSales = $rsSales->GetNextElement()){

		$arNextSale = $oSales->GetFields();
		$arNextSale["PROPERTIES"] = $oSales->GetProperties();

		$arButtons = CIBlock::GetPanelButtons(
			$arNextSale["IBLOCK_ID"],
			$arNextSale["ID"],
			0,
			array("SECTION_BUTTONS" => true,
					"SESSID" => true,
					"CATALOG" => true
			)
		);

		$arNextSale["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
		$arNextSale["PARENT_PRODUCT"]["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];

		$arResult["ITEMS"][$arNextSale["ID"]] = $arNextSale;

	}

	$this->setResultCacheKeys(array());
	$this->IncludeComponentTemplate();

}
