<?php
class DwPrices
{

    public static function getPriceInfo($arOpPriceCodes = array(), $iblock_id = 0){

    	global $USER;

		$arCache = array(
			"USER_GROUP" => $USER->GetUserGroupString(),
			"PRICE_CODES" => $arOpPriceCodes,
			"SITE_ID" => SITE_ID
		);

		$cacheTime = 36000000;
		$cacheID = serialize($arCache);
		$cacheDir = implode(
			"/",
			[
				'dw.deluxe',
				'classes',
				'prices',
				'price.info',
				SITE_ID,
			]
		);

		$obPriceCache = new CPHPCache();

		if($obPriceCache->InitCache($cacheTime, $cacheID, $cacheDir)){
			$arPrices = $obPriceCache->GetVars();
		}

		elseif($obPriceCache->StartDataCache()){

			if(
				   !\Bitrix\Main\Loader::includeModule("iblock")
				|| !\Bitrix\Main\Loader::includeModule("catalog")
				|| !\Bitrix\Main\Loader::includeModule("sale")
			){

				$obPriceCache->AbortDataCache();
				ShowError("modules not installed!");
				return 0;

			}

			$arPrices = array();
			$arPrices["ALLOW"] = array();
			$arPrices["ALLOW_FILTER"] = array();

			if(!empty($arOpPriceCodes)){

				$dbPriceType = CCatalogGroup::GetList(
			        array("SORT" => "ASC"),
			        array("NAME" => $arOpPriceCodes)
			    );

				while ($arPriceType = $dbPriceType->Fetch()){

					if($arPriceType["CAN_BUY"] == "Y"){
				    	$arPrices["ALLOW"][] = $arPriceType;
					}

				    $arPrices["ALLOW_FILTER"][] = $arPriceType["ID"];

				}

			}

			if(!empty($iblock_id)){

				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($cacheDir);
				$CACHE_MANAGER->RegisterTag("iblock_id_".$iblock_id);
				$CACHE_MANAGER->EndTagCache();

			}

			$obPriceCache->EndDataCache($arPrices);

			unset($obPriceCache);

		}

		return $arPrices;

    }

    public static function getPricesByProductId($productId = 0, $arPriceAllow = array(), $arPriceAllowFilter = array(), $arPriceCodes = array(), $iblock_id = 0, $opCurrency = null){

    	global $USER;

		if(
			   !\Bitrix\Main\Loader::includeModule("iblock")
			|| !\Bitrix\Main\Loader::includeModule("catalog")
			|| !\Bitrix\Main\Loader::includeModule("sale")
		){

			ShowError("modules not installed!");
			return 0;

		}

    	$arItemPrice = array();

		if(!empty($arPriceAllow)){
			$arOpPriceCodes = array();
			foreach($arPriceAllow as $ipc => $arNextAllowPrice){
				$dbPrice = CPrice::GetList(
			        array(),
			        array(
			            "PRODUCT_ID" => $productId,
			            "CATALOG_GROUP_ID" => $arNextAllowPrice["ID"]
			        )
			    );
				if($arPriceValues = $dbPrice->Fetch()){
					$arOpPriceCodes[] = array(
						"ID" => $arNextAllowPrice["ID"],
						"PRICE" => $arPriceValues["PRICE"],
						"CURRENCY" => $arPriceValues["CURRENCY"],
						"CATALOG_GROUP_ID" => $arNextAllowPrice["ID"]
					);
				}
			}
		}

		if(!empty($opCurrency)){
			CCatalogProduct::setUsedCurrency($opCurrency);
		}else{
			CCatalogProduct::clearUsedCurrency();
		}

		if(!empty($arPriceAllow) && !empty($arOpPriceCodes) || empty($arPriceCodes)){
			$arItemPrice = CCatalogProduct::GetOptimalPrice($productId, 1, $USER->GetUserGroupArray(), "N", $arOpPriceCodes);
		}

		$arPriceFilter = array("PRODUCT_ID" => $productId, "CAN_ACCESS" => "Y");
		if(!empty($arPriceAllowFilter)){
			$arPriceFilter["CATALOG_GROUP_ID"] = $arPriceAllowFilter;
		}

		$dbPrice = CPrice::GetList(
	        array(),
	        $arPriceFilter,
	        false,
	        false,
	        array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO", "CAN_BUY")
	    );

		if(!empty($arItemPrice)){
			$arItemPrice["COUNT_PRICES"] = $dbPrice->SelectedRowsCount();
		}

		if(!empty($arItemPrice["COUNT_PRICES"])){

			$arItemPrice["EXTENDED_PRICES"] = array();

			while ($arPrice = $dbPrice->Fetch()){

				if($arPrice["CATALOG_GROUP_ID"] == $arItemPrice["PRICE"]["CATALOG_GROUP_ID"] && $arPrice["CAN_BUY"] == "Y"){

				    if(!empty($arPrice["QUANTITY_TO"]) || !empty($arPrice["QUANTITY_FROM"])){

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

						$arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]] = array(
							"DISCOUNT_PRICE" => !empty($opCurrency) ? CCurrencyRates::ConvertCurrency($arPrice["DISCOUNT_PRICE"], $arPrice["CURRENCY"], $opCurrency) : $arPrice["DISCOUNT_PRICE"],
							"PRICE" => !empty($opCurrency) ? CCurrencyRates::ConvertCurrency($arPrice["PRICE"], $arPrice["CURRENCY"], $opCurrency) : $arPrice["PRICE"],
							"QUANTITY_FROM" => $arPrice["QUANTITY_FROM"],
							"QUANTITY_TO" => $arPrice["QUANTITY_TO"]
						);

						if($arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["PRICE"] > $arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["DISCOUNT_PRICE"]){
							$arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["OLD_PRICE"] = $arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["PRICE"];
						}

						if(!empty($arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["OLD_PRICE"])){
							$arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["ECONOMY"] = $arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["PRICE"] - $arItemPrice["EXTENDED_PRICES"][$arPrice["ID"]]["DISCOUNT_PRICE"];
						}

					}
				}

			}

		}

		return $arItemPrice;

	}

}
