<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if (
	!CModule::IncludeModule("sale") ||
	!CModule::IncludeModule("catalog") ||
	!CModule::IncludeModule("iblock")
) {
	return;
}

if(empty($arParams["PRODUCT_ID"])){
	return;
}

if (!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 36000000;
}

global $USER;

$cacheID = $USER->GetGroups()." / ".$arParams["PRODUCT_ID"];

if(!empty($arParams["PRODUCT_PRICE_CODE"])){
	$cacheID .= implode("", $arParams["PRODUCT_PRICE_CODE"]);
}

if(!empty($arParams["CURRENCY_ID"])){
	$cacheID .= $arParams["CURRENCY_ID"];
}

if ($this->StartResultCache($arParams["CACHE_TIME"], $cacheID)){

	$OPTION_CURRENCY = ((!empty($arParams["CURRENCY_ID"]) && $arParams["CURRENCY_ID"] != "undefined") ? $arParams["CURRENCY_ID"] : CCurrency::GetBaseCurrency());

	$arProductMeasure = \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure(intval($arParams["PRODUCT_ID"]));

	$arResult["PRICES"] = array();
	$arPriceID = array();
	$minPriceGroupID = 0;
	$minPriceID = 0;
	$minPrice = 0;

	$arResult["PRODUCT_PRICE_ALLOW_FILTER"] = array();

	if($arParams["PRODUCT_PRICE_CODE"][0] == "undefined"){
		unset($arParams["PRODUCT_PRICE_CODE"]);
	}

	if(!empty($arParams["PRODUCT_PRICE_CODE"])){
		$dbPriceType = CCatalogGroup::GetList(
			array("SORT" => "ASC"),
			array("NAME" => $arParams["PRODUCT_PRICE_CODE"])
		);
		while ($arPriceType = $dbPriceType->Fetch()){
			$arResult["PRODUCT_PRICE_ALLOW_FILTER"][] = $arPriceType["ID"];
		}
	}

	$arPriceFilter = array("PRODUCT_ID" => $arParams["PRODUCT_ID"], "CAN_ACCESS" => "Y");
	if(!empty($arResult["PRODUCT_PRICE_ALLOW_FILTER"])){
		$arPriceFilter["CATALOG_GROUP_ID"] = $arResult["PRODUCT_PRICE_ALLOW_FILTER"];
	}

	$dbPrice = CPrice::GetList(
		array("SORT" => "ASC"),
		$arPriceFilter,
		false,
		false,
		array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO", "CAN_BUY")
	);

	while ($arPrice = $dbPrice->Fetch()){

		$arDiscounts = CCatalogDiscount::GetDiscountByPrice(
			$arPrice["ID"],
			$USER->GetUserGroupArray(),
			"N",
			SITE_ID
		);

		$arPrice["DISCOUNT_PRICE"] = CCatalogProduct::CountPriceWithDiscount(
			$arPrice["PRICE"],
			$arPrice["CURRENCY"],
			$arDiscounts
		);

		$arPrice["PRICE"] = CCurrencyRates::ConvertCurrency($arPrice["PRICE"], $arPrice["CURRENCY"], $OPTION_CURRENCY);
		$arPrice["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($arPrice["DISCOUNT_PRICE"], $arPrice["CURRENCY"], $OPTION_CURRENCY);

		$arPrice["PRICE"] = \Bitrix\Catalog\Product\Price::roundPrice($arPrice["CATALOG_GROUP_ID"], $arPrice["PRICE"], $OPTION_CURRENCY);
		$arPrice["DISCOUNT_PRICE"] = \Bitrix\Catalog\Product\Price::roundPrice($arPrice["CATALOG_GROUP_ID"], $arPrice["DISCOUNT_PRICE"], $OPTION_CURRENCY);

		$arPrice["PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($arPrice["PRICE"], $OPTION_CURRENCY);
		$arPrice["DISCOUNT_PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($arPrice["DISCOUNT_PRICE"], $OPTION_CURRENCY);

		$arResult["PRICES"][$arPrice["CATALOG_GROUP_ID"]][$arPrice["ID"]] = $arPrice;
		$arPriceID[$arPrice["CATALOG_GROUP_ID"]] = $arPrice["CATALOG_GROUP_ID"];

	}

	$dbPriceType = CCatalogGroup::GetList(array("sort" => "desc"), array("ID" => $arPriceID, "CAN_ACCESS" => "Y"));
	while ($arPriceType = $dbPriceType->Fetch()){

		$currentIndex = 0;

		foreach ($arResult["PRICES"][$arPriceType["ID"]] as $arNextPriceVariant){

			if(empty($currentIndex)){
				$arResult["PRICES"][$arPriceType["ID"]][$arNextPriceVariant["ID"]]["NAME"] = $arPriceType["NAME_LANG"];
			}

			else{

				$arResult["PRICES"][$arPriceType["ID"]][$arNextPriceVariant["ID"]]["NAME"] = "";

				if(!empty($arNextPriceVariant["QUANTITY_FROM"])){
					$arResult["PRICES"][$arPriceType["ID"]][$arNextPriceVariant["ID"]]["NAME"] .= GetMessage("FAST_VIEW_PRICES_FROM").$arNextPriceVariant["QUANTITY_FROM"];
				}

				if(!empty($arNextPriceVariant["QUANTITY_TO"])){
					$arResult["PRICES"][$arPriceType["ID"]][$arNextPriceVariant["ID"]]["NAME"] .= GetMessage("FAST_VIEW_PRICES_TO").$arNextPriceVariant["QUANTITY_TO"];
				}

				if(!empty($arProductMeasure[$arParams["PRODUCT_ID"]]["MEASURE"]["SYMBOL_RUS"])){
					if(!empty($arNextPriceVariant["QUANTITY_FROM"]) || !empty($arNextPriceVariant["QUANTITY_TO"])){
						$arResult["PRICES"][$arPriceType["ID"]][$arNextPriceVariant["ID"]]["NAME"] .= " ".$arProductMeasure[$arParams["PRODUCT_ID"]]["MEASURE"]["SYMBOL_RUS"];
					}
				}

				$arResult["PRICES"][$arPriceType["ID"]][$arNextPriceVariant["ID"]]["INCLUDED"] = "Y";
				$arResult["EXTENTED_PRICES"] = "Y";

			}

			$currentIndex++;

		}

	}

	if(!empty($arResult["PRICES"])){
		foreach ($arResult["PRICES"] as $ipr => $arNextPrice){

			$firstPriceIndex = 0;

			foreach ($arNextPrice as $ipp => $arNextPriceVariant){

				if(empty($firstPriceIndex)){
					$tmpPriceID = $arNextPriceVariant["ID"];
				}

				if(empty($minPrice) || $minPrice >= $arNextPriceVariant["DISCOUNT_PRICE"] && $arNextPriceVariant["CAN_BUY"] == "Y"){
					$minPrice = $arNextPriceVariant["DISCOUNT_PRICE"];
					$minPriceID = $tmpPriceID;
					$minPriceGroupID = $arNextPriceVariant["CATALOG_GROUP_ID"];
				}

				$firstPriceIndex++;
			}
		}
	}

	if(!empty($minPriceID)){
		$arResult["PRICES"][$minPriceGroupID][$minPriceID]["MIN_AVAILABLE_PRICE"] = "Y";
	}

	$this->setResultCacheKeys(array());
	$this->IncludeComponentTemplate();

}
