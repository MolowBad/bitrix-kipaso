<?php

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class DwSkuOffers
{
	public static $skuIblockProperties = array();

    public static function getSkuPropertiesFromIblock($arSkuIblockInfo){

    	if(!empty(self::$skuIblockProperties)){
    		return self::$skuIblockProperties;
    	}

    	global $USER, $DB;

    	$skuSortParams = 100;
    	$skuPictureParamsWidth = 36;
    	$skuPictureParamsHeight = 36;
    	$skuPictureParamsQuality = 100;

    	$arResult = array();

		if(!empty($arSkuIblockInfo)){

			$dbg = $DB->ShowSqlStat;

			$arSkuIblockInfo["USER_GROUP"] = $USER->GetUserGroupString();

			$cacheTime = 36000000;
			$cacheID = serialize($arSkuIblockInfo);
			$cacheDir = implode(
				"/",
				[
					'dw.deluxe',
					'classes',
					'sku.offers',
					'sku.properties',
					SITE_ID
				]
			);

			$obExtraCache = new CPHPCache();

			if($dbg){
				$DB->ShowSqlStat = false;
			}

			if($obExtraCache->InitCache($cacheTime, $cacheID, $cacheDir)){
				$arResult = self::$skuIblockProperties = $obExtraCache->GetVars();
			}

			elseif($obExtraCache->StartDataCache()){

				$arPropertiesSort = array(
					"SORT" => "ASC",
					"NAME" => "ASC"
				);

				$arPropertiesFilter = array(
					"IBLOCK_ID" => $arSkuIblockInfo["IBLOCK_ID"],
					"ACTIVE" => "Y"
				);

				$skuProperties = CIBlockProperty::GetList($arPropertiesSort, $arPropertiesFilter);

				while ($arNextProperty = $skuProperties->GetNext()){

					if(
						$arNextProperty["SORT"] <= $skuSortParams &&
						$arNextProperty["PROPERTY_TYPE"] == "L"
					){

						$propValues = array();
						$arNextProperty["HIGHLOAD"] = "N";

						$arPropertyValueSort = array(
							"SORT" => "ASC",
							"DEF" => "DESC"
						);

						$arPropertyValueFilter = array(
							"IBLOCK_ID" => $arSkuIblockInfo["IBLOCK_ID"],
							"CODE" => $arNextProperty["CODE"]
						);

						$rsPropertyValues = CIBlockPropertyEnum::GetList($arPropertyValueSort, $arPropertyValueFilter);
						while($arNextPropertyValue = $rsPropertyValues->GetNext()){

							$propValues[$arNextPropertyValue["VALUE"]] = array(
								"VALUE"  => $arNextPropertyValue["VALUE"],
								"DISPLAY_VALUE"  => $arNextPropertyValue["VALUE"],
								"SELECTED"  => "N",
								"DISABLED"  => "N",
								"HIGHLOAD" => "N"
							);

						}

						$arNextProperty["TYPE"] = "L";
						$arResult[$arNextProperty["CODE"]] = array_merge(
							$arNextProperty, array("VALUES" => $propValues)
						);

					}

					elseif($arNextProperty["SORT"] <= $skuSortParams && $arNextProperty["PROPERTY_TYPE"] == "S" && !empty($arNextProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"])){

						$propValues = array();
						$arNextProperty["HIGHLOAD"] = "Y";

						$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array("filter" => array("TABLE_NAME" => $arNextProperty["USER_TYPE_SETTINGS"]["TABLE_NAME"])))->fetch();

					    if(!empty($hlblock)){

							$hlblock_requests = HL\HighloadBlockTable::getById($hlblock["ID"])->fetch();
							$entity_requests = HL\HighloadBlockTable::compileEntity($hlblock_requests);
							$entity_requests_data_class = $entity_requests->getDataClass();

							$main_query_requests = new Entity\Query($entity_requests_data_class);
							$main_query_requests->setSelect(array("*"));
							$main_query_requests->setOrder(array("UF_SORT" => "ASC"));
							$result_requests = $main_query_requests->exec();
							$result_requests = new CDBResult($result_requests);

							while ($row_requests = $result_requests->Fetch()){

								if(!empty($row_requests["UF_FILE"])){
									$row_requests["UF_FILE"] = CFile::ResizeImageGet(
										$row_requests["UF_FILE"],
										array(
											"width" => $skuPictureParamsWidth,
											"height" => $skuPictureParamsHeight
										),
										BX_RESIZE_IMAGE_PROPORTIONAL,
										false,
										false,
										false,
										$skuPictureParamsQuality
									);
								}

								$propValues[$row_requests["UF_XML_ID"]] = array(
									"VALUE" => $row_requests["UF_XML_ID"],
									"DISPLAY_VALUE" => $row_requests["UF_NAME"],
									"SELECTED" => "N",
									"DISABLED" => "N",
									"UF_XML_ID" => $row_requests["UF_XML_ID"],
									"IMAGE" => $row_requests["UF_FILE"],
									"NAME" => $row_requests["UF_NAME"],
									"HIGHLOAD" => "Y"
								);

							}

							$arNextProperty["HIGHLOAD"] = "Y";
							$arNextProperty["TYPE"] = "H";
							$arResult[$arNextProperty["CODE"]] = array_merge(
								$arNextProperty, array("VALUES" => $propValues)
							);
						}
					}

					elseif(
						$arNextProperty["SORT"] <= $skuSortParams &&
						$arNextProperty["PROPERTY_TYPE"] == "E" &&
						!empty($arNextProperty["LINK_IBLOCK_ID"])
					){

						$arBindingElementSort = array();
						$arBindingElementFilter = array(
							"IBLOCK_ID" => $arNextProperty["LINK_IBLOCK_ID"],
							"ACTIVE" => "Y"
						);

						$arBindingElementSelect = array(
							"ID",
							"NAME",
							"DETAIL_PICTURE"
						);

						$rsLinkElements = CIBlockElement::GetList(
							$arBindingElementSort,
							$arBindingElementFilter,
							false,
							false,
							$arBindingElementSelect
						);

						while ($arNextLinkElement = $rsLinkElements->GetNext()){

							if(!empty($arNextLinkElement["DETAIL_PICTURE"])){
								$arNextLinkElement["UF_FILE"] = CFile::ResizeImageGet(
									$arNextLinkElement["DETAIL_PICTURE"],
									array(
										"width" => $skuPictureParamsWidth,
										"height" => $skuPictureParamsHeight
									),
									BX_RESIZE_IMAGE_PROPORTIONAL,
									false,
									false,
									false,
									$skuPictureParamsQuality
								);
							}

							$propValues[$arNextLinkElement["ID"]] = array(
								"VALUE" => $arNextLinkElement["ID"],
								"VALUE_XML_ID" => $arNextLinkElement["ID"],
								"DISPLAY_VALUE" => $arNextLinkElement["NAME"],
								"UF_XML_ID" => $arNextLinkElement["ID"],
								"IMAGE" => $arNextLinkElement["UF_FILE"],
								"NAME" => $arNextLinkElement["NAME"],
								"TYPE" => "E",
								"HIGHLOAD" => "N",
								"SELECTED" => "N",
								"DISABLED" => "N",
							);
						}

						$arNextProperty["TYPE"] = "E";
						$arResult[$arNextProperty["CODE"]] = array_merge(
							$arNextProperty, array("VALUES" => $propValues)
						);

					}

				}

				self::$skuIblockProperties = $arResult;

				global $CACHE_MANAGER;

				$CACHE_MANAGER->StartTagCache($cacheDir);
				$CACHE_MANAGER->RegisterTag("iblock_id_".$arSkuIblockInfo["IBLOCK_ID"]);
				$CACHE_MANAGER->RegisterTag("iblock_id_".$arSkuIblockInfo["PRODUCT_IBLOCK_ID"]);
				$CACHE_MANAGER->EndTagCache();

 				$obExtraCache->EndDataCache($arResult);

			}

			if($dbg){
				$DB->ShowSqlStat = true;
			}

		}

		return $arResult;

    }

    public static function getSkuFromProduct(
		$productId,
		$iblockId = 0,
		$offersFilterId = false,
		$firstSkuOfferId = false,
		$arSkuIblockInfo,
		$arParams,
		$opCurrency = null
	){

    	global $USER;

		$arParams["PICTURE_WIDTH"] = !empty($arParams["PICTURE_WIDTH"]) ? $arParams["PICTURE_WIDTH"] : 200;
		$arParams["PICTURE_HEIGHT"] = !empty($arParams["PICTURE_HEIGHT"]) ? $arParams["PICTURE_HEIGHT"] : 250;
		$arParams["PICTURE_QUALITY"] = !empty($arParams["PICTURE_QUALITY"]) ? $arParams["PICTURE_QUALITY"] : 80;
		$arParams["SHOW_DEACTIVATED"] = !empty($arParams["SHOW_DEACTIVATED"]) ? $arParams["SHOW_DEACTIVATED"] : "N";
    	$arParams["HIDE_NOT_AVAILABLE"] = !empty($arParams["HIDE_NOT_AVAILABLE"]) ? $arParams["HIDE_NOT_AVAILABLE"] : "N";
		$arParams["PRODUCT_PRICE_CODE"] = !empty($arParams["PRODUCT_PRICE_CODE"]) ? $arParams["PRODUCT_PRICE_CODE"] : array();

    	$skuPictureParamsWidth = 36;
    	$skuPictureParamsHeight = 36;

    	$colorPropertyName = "COLOR";

		$arElement["SKU_EXIST"] = CCatalogSKU::IsExistOffers($productId, $iblockId);

		if($arElement["SKU_EXIST"]){

			if (is_array($arSkuIblockInfo)){

				$arSkuProperties = DwSkuOffers::getSkuPropertiesFromIblock($arSkuIblockInfo);

				if(empty($arSkuProperties)){
					return false;
				}

				$arSkuPropertiesId = array();
				foreach ($arSkuProperties as $arNextSkuProperty){
					$arSkuPropertiesId[$arNextSkuProperty["ID"]] = $arNextSkuProperty["ID"];
				}

				$arOffersFilter = array(
					"IBLOCK_ID" => $arSkuIblockInfo["IBLOCK_ID"],
					"PROPERTY_".$arSkuIblockInfo["SKU_PROPERTY_ID"] => $productId,
					"INCLUDE_SUBSECTIONS" => "N",
					"ACTIVE" => "Y"
				);

				if(!empty($offersFilterId)){
					$arOffersFilter["ID"] = $offersFilterId;
				}

				if($arParams["HIDE_NOT_AVAILABLE"] == "Y"){
					$arOffersFilter["CATALOG_AVAILABLE"] = "Y";
				}

				if($arParams["SHOW_DEACTIVATED"] == "Y"){
					$arOffersFilter["ACTIVE"] = "";
					$arOffersFilter["ACTIVE_DATE"] = "";
				}

				if(!empty($arParams["FILTER"])){

					if(!empty($arParams["FILTER"]["OFFERS"])){
						$arOffersFilter = array_merge($arOffersFilter, $arParams["FILTER"]["OFFERS"]);
					}

					else{
						$arOffersFilter = array_merge($arOffersFilter, $arParams["FILTER"]);
					}

				}

				$arOffersSort = array(
					"SORT" => "ASC",
					"NAME" => "ASC"
				);

				$arOffersSelect = array(
					"ID",
					"IBLOCK_ID",
					"NAME",
					"CODE",
					"SORT",
					"DETAIL_TEXT",
					"PREVIEW_TEXT",
					"DETAIL_TEXT_TYPE",
					"PREVIEW_TEXT_TYPE",
					"DATE_CREATE",
					"DATE_MODIFY",
					"TIMESTAMP_X",
					"DATE_ACTIVE_TO",
					"DETAIL_PICTURE",
					"PREVIEW_PICTURE",
					"DATE_ACTIVE_FROM",
					"CATALOG_QUANTITY",
					"CATALOG_MEASURE",
					"CATALOG_AVAILABLE",
					"CATALOG_SUBSCRIBE",
					"CATALOG_QUANTITY_TRACE",
					"CATALOG_CAN_BUY_ZERO",
					"CANONICAL_PAGE_URL"
				);

				$arElement["SKU_OFFERS"] = array();
				$arElement["SKU_OFFERS_LINK"] = array();

				$rsOffersMx = CIBlockElement::GetList($arOffersSort, $arOffersFilter, false, false, $arOffersSelect);
				while($arSkuMx = $rsOffersMx->GetNextElement()){

					$arSkuFieldsMx = $arSkuMx->GetFields();
					$arSkuPropertiesMx = $arSkuMx->GetProperties(array("ID" => "ASC"), array("ID" => $arSkuPropertiesId, "EMPTY" => "N"));

					$arElement["SKU_OFFERS"][$arSkuFieldsMx["ID"]] = array_merge($arSkuFieldsMx, array("PROPERTIES" => $arSkuPropertiesMx));
					$arElement["SKU_OFFERS_LINK"][$arSkuFieldsMx["ID"]] = $arSkuMx;

				}

			}

		}

		if(!empty($arElement["SKU_OFFERS"])){

			$offersEnableSort = false;
			$offersLastSort = 500;

			if(!empty($firstSkuOfferId) && !empty($arElement["SKU_OFFERS"][$firstSkuOfferId])){

				$arTmpOffer["SKU_OFFERS"][$firstSkuOfferId] = $arElement["SKU_OFFERS"][$firstSkuOfferId];
				unset($arElement["SKU_OFFERS"][$firstSkuOfferId]);
				$arElement["SKU_OFFERS"] = $arTmpOffer["SKU_OFFERS"] + $arElement["SKU_OFFERS"];
				$offersEnableSort = true;
				$firstOfferIndex = $firstSkuOfferId;

			}

			else{

				$firstOfferIndex = key($arElement["SKU_OFFERS"]);

				if($arElement["SKU_OFFERS"][$firstOfferIndex]["SORT"] != $offersLastSort){
					$offersEnableSort = true;
				}

			}

		}

		if(!empty($arElement["SKU_OFFERS"]) && !empty($arSkuProperties)){

			$arElement["SKU_PROPERTIES"] = $arSkuProperties;

			foreach ($arElement["SKU_PROPERTIES"] as $ip => $arProp){
				foreach ($arProp["VALUES"] as $ipv => $arPropValue){

					$find = false;
					foreach ($arElement["SKU_OFFERS"] as $ipo => $arOffer){

						if(!empty($arProp["HIGHLOAD"]) && $arProp["HIGHLOAD"] == "Y"){

							if($arOffer["PROPERTIES"][$arProp["CODE"]]["VALUE"] == $arPropValue["UF_XML_ID"]){
								$find = true;
								break(1);
							}

						}
						else{

							if($arOffer["PROPERTIES"][$arProp["CODE"]]["VALUE"] == $arPropValue["VALUE"]){
								$find = true;
								break(1);
							}

						}

					}

					if(!$find){
						unset($arElement["SKU_PROPERTIES"][$ip]["VALUES"][$ipv]);
					}

				}

			}

			$arPropClean = array();
			$iter = 0;

			foreach ($arElement["SKU_PROPERTIES"] as $ip => $arProp){

				if(!empty($arProp["VALUES"])){

					$arKeys = array_keys($arProp["VALUES"]);
					$selectedUse = false;

					if($iter === 0){

						if($offersEnableSort){

							$arElement["SKU_PROPERTIES"][$ip]["VALUES"][$arElement["SKU_OFFERS"][$firstOfferIndex]["PROPERTIES"][$ip]["VALUE"]]["SELECTED"] = "Y";

							$arPropClean[$ip] = array(
								"PROPERTY" => $ip,
								"VALUE"    => $arElement["SKU_OFFERS"][$firstOfferIndex]["PROPERTIES"][$ip]["VALUE"],
								"HIGHLOAD" => $arProp["HIGHLOAD"]
							);

						}

						else{

							$arElement["SKU_PROPERTIES"][$ip]["VALUES"][$arKeys[0]]["SELECTED"] = "Y";

							$arPropClean[$ip] = array(
								"PROPERTY" => $ip,
								"VALUE"    => $arKeys[0],
								"HIGHLOAD" => $arProp["HIGHLOAD"]
							);

						}

					}else{

						foreach ($arKeys as $keyValue){

							$disabled = true;
							$checkValue = $arElement["SKU_PROPERTIES"][$ip]["VALUES"][$keyValue]["VALUE"];

							foreach ($arElement["SKU_OFFERS"] as $io => $arOffer){

								if($arOffer["PROPERTIES"][$ip]["VALUE"] == $checkValue){

									$disabled = false;
									$selected = true;

									foreach ($arPropClean as $ic => $arNextClean){

										if(strval($arOffer["PROPERTIES"][$arNextClean["PROPERTY"]]["VALUE"]) != strval($arNextClean["VALUE"])){

											if($ic == $ip){
												break(2);
											}

											$disabled = true;
											$selected = false;

											break(1);

										}

									}

									if($offersEnableSort && $disabled == false){
										break(1);
									}

									if(!$offersEnableSort){

										if($selected && !$selectedUse){

											$selectedUse = true;
											$arElement["SKU_PROPERTIES"][$ip]["VALUES"][$keyValue]["SELECTED"] = "Y";

											$arPropClean[$ip] = array(
												"PROPERTY" => $ip,
												"VALUE"    => $keyValue,
												"HIGHLOAD" => $arProp["HIGHLOAD"]
											);

											break(1);

										}

									}

								}

							}

							if($disabled){
								$arElement["SKU_PROPERTIES"][$ip]["VALUES"][$keyValue]["DISABLED"] = "Y";
							}

						}


						if($offersEnableSort){

							$arElement["SKU_PROPERTIES"][$ip]["VALUES"][$arElement["SKU_OFFERS"][$firstOfferIndex]["PROPERTIES"][$ip]["VALUE"]]["SELECTED"] = "Y";

							$arPropClean[$ip] = array(
								"PROPERTY" => $ip,
								"VALUE"    => $arElement["SKU_OFFERS"][$firstOfferIndex]["PROPERTIES"][$ip]["VALUE"],
								"HIGHLOAD" => $arProp["HIGHLOAD"]
							);

						}

					}

					$iter++;

				}

			}

			if(!empty($arElement["SKU_PROPERTIES"][$colorPropertyName])){
				foreach ($arElement["SKU_PROPERTIES"][$colorPropertyName]["VALUES"] as $ic => $arProperty){
					foreach ($arElement["SKU_OFFERS"] as $io => $arNextOffer){
						if($arNextOffer["PROPERTIES"][$colorPropertyName]["VALUE"] == $arProperty["VALUE"]){
							if(!empty($arNextOffer["DETAIL_PICTURE"])){
								$arPropertyImage = CFile::ResizeImageGet(
									$arNextOffer["DETAIL_PICTURE"],
									array(
										"width" => $skuPictureParamsWidth,
										"height" => $skuPictureParamsHeight
									),
									BX_RESIZE_IMAGE_PROPORTIONAL,
									false,
									false,
									false,
									100
								);
								$arElement["SKU_PROPERTIES"][$colorPropertyName]["VALUES"][$ic]["IMAGE"] = $arPropertyImage;
								break(1);
							}
						}
					}
				}
			}

			foreach ($arElement["SKU_OFFERS"] as $ir => $arOffer){

				$active = true;

				if(!$offersEnableSort){
					foreach ($arPropClean as $ic => $arNextClean){
						if($arOffer["PROPERTIES"][$arNextClean["PROPERTY"]]["VALUE"] != $arNextClean["VALUE"]){
							$active = false;
							break(1);
						}
					}
				}

				if($active){

					$arElement["~ID"] = $productId;
					$arElement["ID"] = $arOffer["ID"];

					$arElement["PRODUCT_PRICE_ALLOW"] = array();
					$arElement["PRODUCT_PRICE_ALLOW_FILTER"] = array();

					if(!empty($arParams["PRODUCT_PRICE_CODE"])){

						$arPricesInfo = DwPrices::getPriceInfo($arParams["PRODUCT_PRICE_CODE"], $arSkuIblockInfo["IBLOCK_ID"]);
						if(!empty($arPricesInfo)){
					    	$arElement["PRODUCT_PRICE_ALLOW"] = $arPricesInfo["ALLOW"];
						    $arElement["PRODUCT_PRICE_ALLOW_FILTER"] = $arPricesInfo["ALLOW_FILTER"];
						}

					}

					$arElement["PRICE"] = DwPrices::getPricesByProductId(
						$arElement["ID"],
						$arElement["PRODUCT_PRICE_ALLOW"],
						$arElement["PRODUCT_PRICE_ALLOW_FILTER"],
						$arParams["PRODUCT_PRICE_CODE"],
						$arElement["IBLOCK_ID"],
						$opCurrency
					);

					$arElement["EXTRA_SETTINGS"]["COUNT_PRICES"] = $arElement["PRICE"]["COUNT_PRICES"];

					if(!empty($arOffer["DETAIL_PICTURE"])){
						$arElement["PICTURE"] = CFile::ResizeImageGet(
							$arOffer["DETAIL_PICTURE"],
							array(
								"width" => $arParams["PICTURE_WIDTH"],
								"height" => $arParams["PICTURE_HEIGHT"]
							),
							BX_RESIZE_IMAGE_PROPORTIONAL,
							false,
							false,
							false,
							$arParams["PICTURE_QUALITY"]
						);
					}

					$arElement["EXTRA_SETTINGS"]["STORES_MAX_QUANTITY"] = 0;
					$rsStore = CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $arOffer["ID"]), false, false, array("ID", "AMOUNT"));
					while($arNextStore = $rsStore->GetNext()){
						$arElement["EXTRA_SETTINGS"]["STORES"][] = $arNextStore;
						if($arNextStore["AMOUNT"] > $arElement["EXTRA_SETTINGS"]["STORES_MAX_QUANTITY"]){
							$arElement["EXTRA_SETTINGS"]["STORES_MAX_QUANTITY"] = $arNextStore["AMOUNT"];
						}
					}

					$arOffer["PROPERTIES"] = $arElement["SKU_OFFERS_LINK"][$arOffer["ID"]]->GetProperties(
						array("ID" => "ASC"), array("EMPTY" => "N")
					);

					$arElement["CODE"] = $arOffer["CODE"];
					$arElement["SKU_INFO"] = $arSkuIblockInfo;
					$arElement["IBLOCK_ID"] = $arOffer["IBLOCK_ID"];
					$arElement["PROPERTIES"] = $arOffer["PROPERTIES"];
					$arElement["TIMESTAMP_X"] = $arOffer["TIMESTAMP_X"];
					$arElement["DATE_CREATE"] = $arOffer["DATE_CREATE"];
					$arElement["DETAIL_PICTURE"] = $arOffer["DETAIL_PICTURE"];
					$arElement["PREVIEW_PICTURE"] = $arOffer["PREVIEW_PICTURE"];
					$arElement["CATALOG_MEASURE"] = $arOffer["CATALOG_MEASURE"];
					$arElement["CATALOG_QUANTITY"] = $arOffer["CATALOG_QUANTITY"];
					$arElement["CATALOG_AVAILABLE"] = $arOffer["CATALOG_AVAILABLE"];
					$arElement["CATALOG_SUBSCRIBE"] = $arOffer["CATALOG_SUBSCRIBE"];
					$arElement["CANONICAL_PAGE_URL"] = $arOffer["CANONICAL_PAGE_URL"];
					$arElement["CATALOG_CAN_BUY_ZERO"] = $arOffer["CATALOG_CAN_BUY_ZERO"];
					$arElement["CATALOG_QUANTITY_TRACE"] = $arOffer["CATALOG_QUANTITY_TRACE"];

					if(!empty($arOffer["DETAIL_TEXT"])){
						$arElement["DETAIL_TEXT"] = $arOffer["DETAIL_TEXT"];
						$arElement["~DETAIL_TEXT"] = $arOffer["~DETAIL_TEXT"];
						$arElement["DETAIL_TEXT_TYPE"] = $arOffer["DETAIL_TEXT_TYPE"];
					}

					if(!empty($arOffer["PREVIEW_TEXT"])){
						$arElement["PREVIEW_TEXT"] = $arOffer["PREVIEW_TEXT"];
						$arElement["~PREVIEW_TEXT"] = $arOffer["~PREVIEW_TEXT"];
						$arElement["PREVIEW_TEXT_TYPE"] = $arOffer["PREVIEW_TEXT_TYPE"];
					}

					$arElement["EXTRA_SETTINGS"]["CURRENCY"] = empty($opCurrency) ? $arElement["PRICE"]["RESULT_PRICE"]["CURRENCY"] : $opCurrency;

					$rsMeasure = CCatalogMeasure::getList(
						array(),
						array(
							"ID" => $arElement["CATALOG_MEASURE"]
						),
						false,
						false,
					);

					while($arNextMeasure = $rsMeasure->Fetch()){
						$arElement["EXTRA_SETTINGS"]["MEASURES"][$arNextMeasure["ID"]] = $arNextMeasure;
					}

					$arElement["EXTRA_SETTINGS"]["BASKET_STEP"] = 1;

					$rsMeasureRatio = CCatalogMeasureRatio::getList(
						array(),
						array("PRODUCT_ID" => intval($arOffer["ID"])),
						false,
						false,
						array()
					);

					if($arProductMeasureRatio = $rsMeasureRatio->Fetch()){
						if(!empty($arProductMeasureRatio["RATIO"])){
							$arElement["EXTRA_SETTINGS"]["BASKET_STEP"] = $arProductMeasureRatio["RATIO"];
						}
					}

					$arButtons = CIBlock::GetPanelButtons(
						$arElement["IBLOCK_ID"],
						$arElement["ID"],
						0,
						array("SECTION_BUTTONS" => false,
							  "SESSID" => true,
							  "CATALOG" => false
						)
					);

					$arElement["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
					$arElement["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];


				}

				if($offersEnableSort){
					break(1);
				}

			}
		}

		else{
			return false;
		}

		return $arElement;

	}

}
