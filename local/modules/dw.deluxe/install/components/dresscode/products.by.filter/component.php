<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if(
	!CModule::IncludeModule("iblock") ||
	!CModule::IncludeModule('highloadblock') ||
	!CModule::IncludeModule("catalog") ||
	!CModule::IncludeModule("sale") ||
	!CModule::IncludeModule("dw.deluxe")
){
	return;
}

if (!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 36000000;
}

$arParams["LAZY_LOAD_PICTURES"] = !empty($arParams["LAZY_LOAD_PICTURES"])
	? $arParams["LAZY_LOAD_PICTURES"]
	: "N";

if(empty($arParams["PROP_NAME"])){
	$arParams["PROP_NAME"] = "PRODUCT_DAY";
}

if(empty($arParams["ELEMENTS_COUNT"])){
	$arParams["ELEMENTS_COUNT"] = 10;
}

if(empty($arParams["FILTER_TYPE"])){
	$arParams["FILTER_TYPE"] = "BESTSELLERS";
}

if(empty($arParams["SORT_PROPERTY_NAME"])){
	$arParams["SORT_PROPERTY_NAME"] = "SORT";
}

if(empty($arParams["SORT_VALUE"])){
	$arParams["SORT_VALUE"] = "ASC";
}

$cacheID = $USER->GetGroups();
$cacheID .= SITE_ID;

if(!empty($arParams["PRODUCT_PRICE_CODE"])){
	$cacheID .= implode("", $arParams["PRODUCT_PRICE_CODE"]);
}

if(empty($arParams["PICTURE_WIDTH"])){
	$arParams["PICTURE_WIDTH"] = 200;
}

if(empty($arParams["PICTURE_HEIGHT"])){
	$arParams["PICTURE_HEIGHT"] = 180;
}

if(empty($arParams["IBLOCK_ID"])){
	return;
}

if ($this->StartResultCache($arParams["CACHE_TIME"], $cacheID)){

	$arResult["ITEMS"] = array();
	$arSelect = array("ID", "IBLOCK_ID", "IBLOCK_TYPE", "NAME", "DETAIL_PAGE_URL");
	$arFilter = array(
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y"
	);

	if($arParams["FILTER_TYPE"] == "BESTSELLERS"){

		$productIds = array();
		$productIterator = CSaleProduct::GetBestSellerList(
			"QUANTITY",
			array(),
			array(),
			$arParams["ELEMENTS_COUNT"]
		);

		while($product = $productIterator->fetch()){
			$productIds[] = $product["PRODUCT_ID"];
		}

		if(!empty($productIds)){
			$arFilter["ID"] = $productIds;
		}

	}

	elseif($arParams["FILTER_TYPE"] == "DISCOUNTS"){

		global $DB;

		$arDiscountElementID = array();
		$arDiscountSectionID = array();

		$dbProductDiscounts = CCatalogDiscount::GetList(
			array(
				"SORT" => "ASC"
			),
			array(
				"ACTIVE" => "Y",
				"!>ACTIVE_FROM" => $DB->FormatDate(date("Y-m-d H:i:s"), "YYYY-MM-DD HH:MI:SS", CSite::GetDateFormat("FULL")),
				"!<ACTIVE_TO" => $DB->FormatDate(date("Y-m-d H:i:s"), "YYYY-MM-DD HH:MI:SS", CSite::GetDateFormat("FULL")),
				"SITE_ID" => SITE_ID
			),
			false,
			false,
			array(
				"ID", "SITE_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO",
				"RENEWAL", "NAME", "SORT", "MAX_DISCOUNT", "VALUE_TYPE",
				"VALUE", "CURRENCY", "PRODUCT_ID", "SECTION_ID"
			)
		);

		while($arProductDiscounts = $dbProductDiscounts->Fetch()){

			if($rsDiscounts = CCatalogDiscount::GetDiscountProductsList(array(), array("DISCOUNT_ID" => $arProductDiscounts["ID"]), false, false, array())){
				while($nextDiscountElement = $rsDiscounts->GetNext()){
					if(!empty($nextDiscountElement["PRODUCT_ID"])){
						$arDiscountElementID[$nextDiscountElement["PRODUCT_ID"]] = $nextDiscountElement["PRODUCT_ID"];
					}
				}
			}

			if($rsDiscounts = CCatalogDiscount::GetDiscountSectionsList(array(), array("DISCOUNT_ID" => $arProductDiscounts["ID"]), false, false, array())){
				while($nextDiscountElement = $rsDiscounts->GetNext()){
					if(!empty($nextDiscountElement["SECTION_ID"])){
						$arDiscountSectionID[$nextDiscountElement["SECTION_ID"]] = $nextDiscountElement["SECTION_ID"];
					}
				}
			}

		}

		if(!empty($arDiscountElementID) || !empty($arDiscountSectionID)){
			$arFilter[] = array(
				"LOGIC" => "OR",
				array(
					"SECTION_ID" => $arDiscountSectionID,
					"INCLUDE_SUBSECTIONS" => "Y"
				),
				array("ID" => $arDiscountElementID),
			);
		}

	}

	elseif($arParams["FILTER_TYPE"] == "SECTION"){
		if(!empty($arParams["SECTION_ID"])){
			$arFilter["SECTION_ID"] = intval($arParams["SECTION_ID"]);
		}
	}

	elseif($arParams["FILTER_TYPE"] == "PROPERTY"){
		if(!empty($arParams["PROP_NAME"]) && !empty($arParams["PROP_VALUE"])){
			$arFilter["PROPERTY_".$arParams["PROP_NAME"]] = $arParams["PROP_VALUE"];
		}
	}

	if ($arParams["HIDE_NOT_AVAILABLE"] == "Y"){
		$arFilter[] = array(
			"LOGIC" => "OR",
			array(
				"=ID" => CIBlockElement::SubQuery(
					"PROPERTY_CML2_LINK",
					array(
						"=CATALOG_AVAILABLE" => "Y",
						"ACTIVE_DATE" => "Y",
						"ACTIVE" => "Y"
					)
				)
			),
			array(
				"LOGIC" => "AND",
				array(
					"!ID" => CIBlockElement::SubQuery(
						"PROPERTY_CML2_LINK",
						array("!ID" => false)
					)
				),
				array("=CATALOG_AVAILABLE" => "Y"),
			),
		);
	}

	$rsProducts = CIBlockElement::GetList(
		array($arParams["SORT_PROPERTY_NAME"] => $arParams["SORT_VALUE"]),
		$arFilter,
		false,
		array("nPageSize" => $arParams["ELEMENTS_COUNT"]),
		$arSelect
	);

	while($obProducts = $rsProducts->GetNextElement()){
		$arNextElement = $obProducts->GetFields();
		$arResult["ITEMS"][$arNextElement["ID"]] = $arNextElement;
	}

	$this->setResultCacheKeys(array());
	$this->IncludeComponentTemplate();

}
