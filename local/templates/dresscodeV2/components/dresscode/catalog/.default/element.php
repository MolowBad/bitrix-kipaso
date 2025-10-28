<?
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

	$this->setFrameMode(true);
	$this->SetViewTarget("menuRollClass");?> menuRolled<?$this->EndViewTarget();
	$this->SetViewTarget("hiddenZoneClass");?>hiddenZone<?$this->EndViewTarget();

	$arCache = array(
		"SITE_ID" => SITE_ID,
		"CACHE_INDEX" => "DETAIL",
		"ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
		"CODE" => $arResult["VARIABLES"]["ELEMENT_CODE"],
		"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"SECTION_CODE_PATH" => $arResult["VARIABLES"]["SECTION_CODE_PATH"]
	);

	$cacheDir = implode(
		"/",
		[
			SITE_ID,
			'templates',
			'dresscodeV2',
			'dresscode.catalog',
			'.default',
			'element'
		]
	);

	$obCache = new CPHPCache();

	if($obCache->InitCache($arParams["CACHE_TIME"], serialize($arCache), $cacheDir)){
	   $arResult["EXTRA"] = $obCache->GetVars();
	}

	elseif($obCache->StartDataCache()){

		if(!empty($arResult["VARIABLES"]["ELEMENT_CODE"]) && CModule::IncludeModule("iblock")){

			$arSelect = Array("ID", "IBLOCK_ID", "NAME");
			$arFilter = Array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "IBLOCK_LID" => SITE_ID, "ACTIVE" => "Y");
			$arFilter["=CODE"] = $arResult["VARIABLES"]["ELEMENT_CODE"];

			//show deactivated products
			if(!empty($arParams["SHOW_DEACTIVATED"]) && $arParams["SHOW_DEACTIVATED"] == "Y"){
				$arFilter["ACTIVE"] = "";
			}

			//check for 404 error
			if(!empty($arResult["VARIABLES"]["SECTION_CODE"]) || !empty($arResult["VARIABLES"]["SECTION_CODE_PATH"])){
				$arFilter["=SECTION_CODE"] = $arResult["VARIABLES"]["SECTION_CODE"];
			}

			$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
			if($ob = $res->GetNextElement()){
				//save
				$arResult["EXTRA"] = $ob->getFields();
			}

			else{
				//abort cache
				$obCache->AbortDataCache();
			}

		}

		$obCache->EndDataCache($arResult["EXTRA"]);

	}

?>
<?$this->SetViewTarget("no_main_container");?>
<?$APPLICATION->IncludeComponent(
	"dresscode:catalog.item",
	"detail", #Было
	// "detail_with_sku_table", # Стало - копирую шаблон отображения товара 
	array(
		"PRODUCT_ID" => (!empty($arResult["VARIABLES"]["ELEMENT_ID"]) ? $arResult["VARIABLES"]["ELEMENT_ID"] : (!empty($arResult["EXTRA"]["ID"]) ? $arResult["EXTRA"]["ID"] : "-")),
		"ADD_ELEMENT_CHAIN" => (isset($arParams["ADD_ELEMENT_CHAIN"]) ? $arParams["ADD_ELEMENT_CHAIN"] : ""),
		"TAGS_DETAIL_SECTION_MAX_DELPH_LEVEL" => $arParams["CATALOG_TAGS_DETAIL_SECTION_MAX_DELPH_LEVEL"],
		"CALCULATE_DELIVERY_GROUP_BUTTONS" => $arParams["DETAIL_CALCULATE_DELIVERY_GROUP_BUTTONS"],
		"OFFERS_TABLE_DISPLAY_PICTURE_COLUMN" => $arParams["OFFERS_TABLE_DISPLAY_PICTURE_COLUMN"],
		"CALCULATE_DELIVERY_SHOW_IMAGES" => $arParams["DETAIL_CALCULATE_DELIVERY_SHOW_IMAGES"],
		"ALLOW_ADD_REVIEW_NOT_REGISTER" => $arParams["DETAIL_ALLOW_ADD_REVIEW_NOT_REGISTER"],
		"TAGS_DETAIL_LINK_VARIANT" => $arParams["CATALOG_TAGS_DETAIL_LINK_VARIANT"],
		"MAX_VISIBLE_TAGS_DESKTOP" => $arParams["CATALOG_MAX_VISIBLE_TAGS_DESKTOP"],
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"DISABLE_PRINT_DIMENSIONS" => $arParams["DETAIL_DISABLE_PRINT_DIMENSIONS"],
		"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
		"MAX_VISIBLE_TAGS_MOBILE" => $arParams["CATALOG_MAX_VISIBLE_TAGS_MOBILE"],
		"ADVANTAGES_IBLOCK_TYPE" => $arParams["ADVANTAGES_IN_DETAIL_IBLOCK_TYPE"],
		"ADVANTAGES_IBLOCK_ID" => $arParams["ADVANTAGES_IN_DETAIL_IBLOCK_ID"],
		"OFFERS_TABLE_PAGER_COUNT" => $arParams["OFFERS_TABLE_PAGER_COUNT"],
		"SHOW_CALCULATE_DELIVERY" => $arParams["DETAIL_CALCULATE_DELIVERY"],
		"DISABLE_PRINT_WEIGHT" => $arParams["DETAIL_DISABLE_PRINT_WEIGHT"],
		"SECTION_CODE_PATH" => $arResult["VARIABLES"]["SECTION_CODE_PATH"],
		"COUNT_TOP_PROPERTIES" => $arParams["DETAIL_COUNT_TOP_PROPERTIES"],
		"HIDE_TAGS_ON_MOBILE" => $arParams["CATALOG_HIDE_TAGS_ON_MOBILE"],
		"PRODUCT_DISPLAY_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
		"DISPLAY_ADVANTAGES" => $arParams["SHOW_ADVANTAGES_IN_DETAIL"],
		"TAGS_SEARCH_PARAM" => $arParams["CATALOG_TAGS_SEARCH_PARAM"],
		"SET_CANONICAL_URL" => $arParams["DETAIL_SET_CANONICAL_URL"],
		"TAGS_SEARCH_PATH" => $arParams["CATALOG_TAGS_SEARCH_PATH"],
		"DISPLAY_OFFERS_TABLE" => $arParams["DISPLAY_OFFERS_TABLE"],
		"META_DESCRIPTION" => $arParams["DETAIL_META_DESCRIPTION"],
		"SERVICES_IBLOCK_TYPE" => $arParams["SERVICES_IBLOCK_TYPE"],
		"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"SERVICES_IBLOCK_ID" => $arParams["SERVICES_IBLOCK_ID"],
		"HIDE_AVAILABLE_TAB" => $arParams["HIDE_AVAILABLE_TAB"],
		"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
		"REVIEW_IBLOCK_TYPE" => $arParams["REVIEW_IBLOCK_TYPE"],
		"HIDE_DELIVERY_CALC" => $arParams["HIDE_DELIVERY_CALC"],
		"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
		"CATALOG_SHOW_TAGS" => $arParams["CATALOG_SHOW_TAGS"],
		"META_KEYWORDS" => $arParams["DETAIL_META_KEYWORDS"],
		"BROWSER_TITLE" => $arParams["DETAIL_BROWSER_TITLE"],
		"REVIEW_IBLOCK_ID" => $arParams["REVIEW_IBLOCK_ID"],
		"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
		"SALE_IBLOCK_TYPE" => $arParams["SALE_IBLOCK_TYPE"],
		"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
		"SHOW_DEACTIVATED" => $arParams["SHOW_DEACTIVATED"],
		"TAGS_SORT_FIELD" => $arParams["CATALOG_TAGS_SORT_FIELD"],
		"TAGS_SORT_TYPE" => $arParams["CATALOG_TAGS_SORT_TYPE"],
		"DISPLAY_CHEAPER" => $arParams["DISPLAY_CHEAPER"],
		"CHEAPER_FORM_ID" => $arParams["CHEAPER_FORM_ID"],
		"SALE_IBLOCK_ID" => $arParams["SALE_IBLOCK_ID"],
		"PRODUCT_PRICE_CODE" => $arParams["PRICE_CODE"],
		"PICTURE_HEIGHT" => $arParams["PICTURE_HEIGHT"],
		"SET_STATUS_404" => $arParams["SET_STATUS_404"],
		"SHOW_SERVICES" => $arParams["SHOW_SERVICES"],
		"PICTURE_WIDTH" => $arParams["PICTURE_WIDTH"],
		"HIDE_MEASURES" => $arParams["HIDE_MEASURES"],
		"MAX_TAGS" => $arParams["CATALOG_MAX_TAGS"],
		"CURRENCY_ID" => $arParams["CURRENCY_ID"],
		"MESSAGE_404" => $arParams["MESSAGE_404"],
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"USE_REVIEW" => $arParams["USE_REVIEW"],
		"SEF_FOLDER" => $arParams["SEF_FOLDER"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"SHOW_404" => $arParams["SHOW_404"],
		"FILE_404" => $arParams["FILE_404"],
		"USE_STORE" => $arParams["USE_STORE"],
		"STORES_LIST" => $arParams["STORES"],
		"STORES_USE_MIN_AMOUNT" => $arParams["USE_MIN_AMOUNT"],
		"STORES_FIELDS" => $arParams["FIELDS"],
		"STORES_MIN_AMOUNT" => $arParams["MIN_AMOUNT"],
		"STORES_SHOW_EMPTY_STORE" => $arParams["SHOW_EMPTY_STORE"],
		"STORES_SHOW_GENERAL_STORE_INFORMATION" => $arParams["SHOW_GENERAL_STORE_INFORMATION"],
		"STORES_STORE_PATH" => $arParams["STORE_PATH"],
		"USE_GIFTS" => $arParams['USE_GIFTS_DETAIL'],
		"CATALOG_VARIABLES" => $arResult,
		"SET_VIEWED_IN_COMPONENT" => "Y",
		"DISPLAY_MORE_PICTURES" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"DISPLAY_LAST_SECTION" => "Y",
		"DISPLAY_FILES_VIDEO" => "Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_BROWSER_TITLE" => "Y",
		"GET_MORE_PICTURES" => "Y", // more picture + detail picture
		"DISPLAY_RELATED" => "Y",
		"DISPLAY_SIMILAR" => "Y",
		"DETAIL_ELEMENT" => "Y",
		"ADD_OPEN_GRAPH" => "Y",
		"DISPLAY_BRAND" => "Y"
	),
	false,
	array("HIDE_ICONS" => "Y")
);?><?$this->EndViewTarget();?>
