<?php

class DwProductEvents
{

	private static $siteLang = "ru";
	private static $lastId = 0;
	private static $availableLastId = 0;
	private static $brandPropertyCode = "ATT_BRAND";
	private static $collectionPropertyCode = "COLLECTION";
	private static $availablePropertyCode = "CML2_AVAILABLE";

	public static function productUpdate(\Bitrix\Main\Event $event){

		$arTemplateSettings = DwSettings::getInstance()->getSettingsFromOption();
		$arEventParams = $event->getParameters();

		if(!empty($arTemplateSettings)){
			self::availableAutoUpdate($arEventParams, $arTemplateSettings);
			self::deactivateProduct($arEventParams, $arTemplateSettings);
		}

	}

	public static function productAfterSave($arg1, $arg2 = false){

		$arTemplateSettings = DwSettings::getInstance()->getSettingsFromOption();

		if(!empty($arTemplateSettings)){

			self::sortPriceAutoUpdate($arg1, $arg2, $arTemplateSettings);
			self::collectionAutoUpdate($arg1, $arg2, $arTemplateSettings);
			self::brandsAutoUpdate($arg1, $arg2, $arTemplateSettings);

		}

	}

	public static function availableAutoUpdate($arEventParams, $arTemplateSettings = array()){

		if(empty($arTemplateSettings)){
			return false;
		}

		\Bitrix\Main\Loader::includeModule("iblock");
		\Bitrix\Main\Loader::includeModule("catalog");

		$productId = !empty($arEventParams["id"]) ? $arEventParams["id"] : false;
		$productIblockId = !empty($arEventParams["external_fields"]["IBLOCK_ID"]) ? $arEventParams["external_fields"]["IBLOCK_ID"] : false;
		$arCurrentSettings = array();

		if(self::$availableLastId == $productId){
			return false;
		}

		if(empty($productIblockId)){
			return false;
		}

		$rsIblock = CIBlock::GetSite($productIblockId);
		while($arIblockSites = $rsIblock->Fetch()){
			if(!empty($arIblockSites["LID"]) && !empty($arTemplateSettings[$arIblockSites["LID"]])){
				//set current settings from binding site id
				$arCurrentSettings = $arTemplateSettings[$arIblockSites["LID"]];
			}
		}

		if(!empty($arCurrentSettings["TEMPLATE_USE_AUTO_AVAILABLE_PRODUCTS"]) && $arCurrentSettings["TEMPLATE_USE_AUTO_AVAILABLE_PRODUCTS"] == "Y"){

			$skuResult = CCatalogSku::GetProductInfo($productId);
			if(is_array($skuResult)){

				$productId = $skuResult["ID"];
				$productIblockId = $skuResult["IBLOCK_ID"];

				$isSku = true;

			}

			else{
				$isSku = CCatalogSku::IsExistOffers($productId, $productIblockId);
			}

			$arFilter = array(
				">CATALOG_QUANTITY" => 0
			);

			if($isSku === true){
				$arFilter["PROPERTY_CML2_LINK"] = $productId;
			}else{
				$arFilter["ID"] = $productId;
			}

			$rsElements = CIBlockElement::GetList(
				array(),
				$arFilter,
				false,
				false,
				array("ID", "CODE", "IBLOCK_ID")
			);

			$property_enums = CIBlockPropertyEnum::GetList(array(), array("IBLOCK_ID" => $productIblockId, "CODE" => self::$availablePropertyCode));
			while($enum_fields = $property_enums->GetNext()){
				$arPropertyEnums[$enum_fields["XML_ID"]] = $enum_fields["ID"];
			}

			if(!empty($arPropertyEnums) && $arPropertyEnums["Y"]){

				$elementAvailable = intval($rsElements->SelectedRowsCount()) > 0 ? $arPropertyEnums["Y"] : "";

				self::$availableLastId = $productId;

				return CIBlockElement::SetPropertyValueCode($productId, self::$availablePropertyCode, array("VALUE" => $elementAvailable));

			}

			return false;

		}

	}

	public static function deactivateProduct($arEventParams, $arTemplateSettings = array()){

		if(empty($arTemplateSettings)){
			return false;
		}

		\Bitrix\Main\Loader::includeModule("iblock");

		$productId = !empty($arEventParams["id"]) ? $arEventParams["id"] : false;
		$productIblockId = !empty($arEventParams["external_fields"]["IBLOCK_ID"]) ? $arEventParams["external_fields"]["IBLOCK_ID"] : false;
		$arProcessProductsId = array();
		$arCurrentSettings = array();

		if(self::$lastId == $productId){
			return false;
		}

		if(empty($productIblockId)){
			return false;
		}

		$rsIblock = CIBlock::GetSite($productIblockId);
		while($arIblockSites = $rsIblock->Fetch()){
			if(!empty($arIblockSites["LID"]) && !empty($arTemplateSettings[$arIblockSites["LID"]])){
				$arCurrentSettings = $arTemplateSettings[$arIblockSites["LID"]];
			}
		}

		if(!empty($arCurrentSettings["TEMPLATE_USE_AUTO_DEACTIVATE_PRODUCTS"]) && $arCurrentSettings["TEMPLATE_USE_AUTO_DEACTIVATE_PRODUCTS"] == "Y"){

			$skuResult = CCatalogSku::GetProductInfo($productId);
			if(is_array($skuResult)){
				$productId = $skuResult["ID"];
				$productIblockId = $skuResult["IBLOCK_ID"];
			}

			$offersExist = CCatalogSKU::getExistOffers(array($productId), $productIblockId);
			if(!empty($offersExist[$productId])){

				$skuIblockInfo = CCatalogSKU::GetInfoByProductIBlock($productIblockId);
				if(is_array($skuIblockInfo)){

					$productNotDiactivate = false;

					$rsOffers = CIBlockElement::GetList(
						array(),
						array(
							"IBLOCK_ID" => $skuIblockInfo["IBLOCK_ID"],
							"PROPERTY_".$skuIblockInfo["SKU_PROPERTY_ID"] => $productId,
						),
						false,
						false,
						array("ID", "IBLOCK_ID", "ACTIVE", "CATALOG_QUANTITY", "CATALOG_AVAILABLE")
					);

					while($arOffer = $rsOffers->Fetch()){

						if($arOffer["CATALOG_AVAILABLE"] != "Y"){
							if($arOffer["ACTIVE"] == "Y"){
								$arProcessProductsId[$arOffer["ID"]] = false;
							}
						}

						else{
							if($arOffer["ACTIVE"] == "N"){
								$arProcessProductsId[$arOffer["ID"]] = true;
							}
							$productNotDiactivate = true;
						}

					}

					$arProcessProductsId[$productId] = $productNotDiactivate;

				}
			}

			else{

				$rsElement = CIBlockElement::GetList(
					array(),
					array(
						"ID" => $productId,
					),
					false,
					false,
					array("ID", "IBLOCK_ID", "ACTIVE", "CATALOG_QUANTITY", "CATALOG_AVAILABLE")
				);

				if($arNextElement = $rsElement->Fetch()){

					if($arNextElement["CATALOG_AVAILABLE"] != "Y"){
						if($arNextElement["ACTIVE"] == "Y"){
							$arProcessProductsId[$productId] = false;
						}
					}

					else{
						if($arNextElement["ACTIVE"] == "N"){
							$arProcessProductsId[$productId] = true;
						}
					}

				}

			}

			if(!empty($arProcessProductsId)){

				foreach($arProcessProductsId as $nextProductId => $productActiveFlag){

					self::$lastId = $nextProductId;

					$updateElement = new CIBlockElement;
					if(!$updateElement->Update($nextProductId, array("ACTIVE" => !empty($productActiveFlag) ? "Y" : "N"))){
						file_put_contents($_SERVER["DOCUMENT_ROOT"]."/events_error.txt", $updateElement->LAST_ERROR);
					}

					unset($updateElement);

				}

			}

		}

	}

	public static function collectionAutoUpdate($arg1, $arg2 = false, $arTemplateSettings = array()){

		\Bitrix\Main\Loader::includeModule("catalog");
		\Bitrix\Main\Loader::includeModule("iblock");

		$productId = (!empty($arg1) && is_numeric($arg1) ? $arg1 : (!empty($arg1["ID"]) ? $arg1["ID"] : (!empty($arg2["PRODUCT_ID"]) ? $arg2["PRODUCT_ID"] : false)));
		$productIblockId = (!empty($arg1["IBLOCK_ID"]) ? $arg1["IBLOCK_ID"] : (!empty($arg2["IBLOCK_ID"]) ? $arg2["IBLOCK_ID"] : false));
		$arSettings = array();

		if(empty($arTemplateSettings) || empty($productIblockId) || empty($productId)){
			return false;
		}

		$rsIblock = CIBlock::GetSite($productIblockId);
		while($arIblockSites = $rsIblock->Fetch()){
			if(!empty($arIblockSites["LID"]) && !empty($arTemplateSettings[$arIblockSites["LID"]])){
				$arSettings = $arTemplateSettings[$arIblockSites["LID"]];
			}
		}

		if(empty($arSettings)){
			return false;
		}

		if(!empty($arSettings["TEMPLATE_USE_AUTO_COLLECTION"]) && $arSettings["TEMPLATE_USE_AUTO_COLLECTION"] == "Y"){

			if(!empty($arSettings["TEMPLATE_COLLECTION_IBLOCK_ID"]) && !empty($arSettings["TEMPLATE_COLLECTION_PROPERTY_CODE"])){

				$collectionIblockId = $arSettings["TEMPLATE_COLLECTION_IBLOCK_ID"];
				$collectionCode = $arSettings["TEMPLATE_COLLECTION_PROPERTY_CODE"];

				$skuResult = CCatalogSku::GetProductInfo($productId);
				if(is_array($skuResult)){
					$productId = $skuResult["ID"];
				}

				$rsElement = CIBlockElement::GetList(
					array(),
					array(
						"ID" => $productId,
					),
					false,
					false,
					array("ID", "CODE", "IBLOCK_ID")
				);

				if($nextElement = $rsElement->GetNextElement()){

					$arElement = $nextElement->GetFields();

					$rsProperty = CIBlockElement::GetProperty($arElement["IBLOCK_ID"], $arElement["ID"], array(), array("CODE" => $collectionCode));
					if($arProperty = $rsProperty->Fetch()){
						if(!empty($arProperty["VALUE_ENUM"])){
							if($collectionElementId = self::iblockBindUpdate($productId, $collectionIblockId, $arProperty["VALUE_ENUM"])){
								self::productBindUpdate($productId, self::$collectionPropertyCode, $collectionElementId, $collectionIblockId);
							}
						}
					}

				}

			}

		}

	}

	public static function brandsAutoUpdate($arg1, $arg2 = false, $arTemplateSettings = array()){

		\Bitrix\Main\Loader::includeModule("catalog");
		\Bitrix\Main\Loader::includeModule("iblock");

		$productId = (!empty($arg1) && is_numeric($arg1) ? $arg1 : (!empty($arg1["ID"]) ? $arg1["ID"] : (!empty($arg2["PRODUCT_ID"]) ? $arg2["PRODUCT_ID"] : false)));
		$productIblockId = (!empty($arg1["IBLOCK_ID"]) ? $arg1["IBLOCK_ID"] : (!empty($arg2["IBLOCK_ID"]) ? $arg2["IBLOCK_ID"] : false));
		$arSettings = array();

		if(empty($arTemplateSettings) || empty($productIblockId) || empty($productId)){
			return false;
		}

		$rsIblock = CIBlock::GetSite($productIblockId);
		while($arIblockSites = $rsIblock->Fetch()){
			if(!empty($arIblockSites["LID"]) && !empty($arTemplateSettings[$arIblockSites["LID"]])){
				$arSettings = $arTemplateSettings[$arIblockSites["LID"]];
			}
		}

		if(empty($arSettings)){
			return false;
		}

		if(!empty($arSettings["TEMPLATE_USE_AUTO_BRAND"]) && $arSettings["TEMPLATE_USE_AUTO_BRAND"] == "Y"){

			if(!empty($arSettings["TEMPLATE_BRAND_IBLOCK_ID"]) && !empty($arSettings["TEMPLATE_BRAND_PROPERTY_CODE"])){

				$brandIblockId = $arSettings["TEMPLATE_BRAND_IBLOCK_ID"];
				$brandCode = $arSettings["TEMPLATE_BRAND_PROPERTY_CODE"];

				$skuResult = CCatalogSku::GetProductInfo($productId);
				if(is_array($skuResult)){
					$productId = $skuResult["ID"];
				}

				$rsElement = CIBlockElement::GetList(
					array(),
					array(
						"ID" => $productId,
					),
					false,
					false,
					array("ID", "CODE", "IBLOCK_ID")
				);

				if($nextElement = $rsElement->GetNextElement()){

					$arElement = $nextElement->GetFields();

					$rsProperty = CIBlockElement::GetProperty($arElement["IBLOCK_ID"], $arElement["ID"], array(), array("CODE" => $brandCode));
					if($arProperty = $rsProperty->Fetch()){
						if(!empty($arProperty["VALUE_ENUM"])){
							if($brandElementId = self::iblockBindUpdate($productId, $brandIblockId, $arProperty["VALUE_ENUM"])){
								self::productBindUpdate($productId, self::$brandPropertyCode, $brandElementId, $brandIblockId);
							}
						}
					}

				}

			}

		}

	}

	public static function productBindUpdate($productId, $propertyCode, $bindElementId, $bindIblockId){

		if(empty($productId) || empty($bindElementId) || empty($bindIblockId) || empty($propertyCode)){
			return false;
		}

		return CIBlockElement::SetPropertyValueCode($productId, $propertyCode, array("VALUE" => $bindElementId));

	}

	public static function iblockBindUpdate($productId, $iblockId, $elementName){

		if(empty($productId) || empty($elementName) || empty($iblockId)){
			return false;
		}

		$elementCode = Cutil::translit($elementName, self::$siteLang);
		$returnId = false;

		$rsElement = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $iblockId,
				"CODE" => $elementCode
 			),
			false,
			false,
			array("ID", "CODE", "NAME", "IBLOCK_ID")
		);

		if(!$checkElement = $rsElement->GetNextElement()){

			$arFields = array(
				"PROPERTY_VALUES" => array(),
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => 0,
				"NAME" => $elementName,
				"CODE" => $elementCode,
				"DETAIL_TEXT" => "",
				"ACTIVE" => "Y",
			);

			$obElement = new CIBlockElement();
			$returnId = $obElement->Add($arFields, false, false, true);

		}

		else{

			$checkElementFields = $checkElement->GetFields();
			if(!empty($checkElementFields["ID"])){
				$returnId = $checkElementFields["ID"];
			}

		}

		return $returnId;

	}

	public static function sortPriceAutoUpdate($arg1, $arg2 = false, $arTemplateSettings = array()){

		\Bitrix\Main\Loader::includeModule("catalog");
		\Bitrix\Main\Loader::includeModule("iblock");
		\Bitrix\Main\Loader::includeModule("sale");

		$productId = (!empty($arg1) && is_numeric($arg1) ? $arg1 : (!empty($arg1["ID"]) ? $arg1["ID"] : (!empty($arg2["PRODUCT_ID"]) ? $arg2["PRODUCT_ID"] : false)));
		$productIblockId = (!empty($arg1["IBLOCK_ID"]) ? $arg1["IBLOCK_ID"] : (!empty($arg2["IBLOCK_ID"]) ? $arg2["IBLOCK_ID"] : false));
		$arSettings = array();

		if(empty($arTemplateSettings) || empty($productIblockId) || empty($productId)){
			return false;
		}

		$rsIblock = CIBlock::GetSite($productIblockId);
		while($arIblockSites = $rsIblock->Fetch()){
			if(!empty($arIblockSites["LID"]) && !empty($arTemplateSettings[$arIblockSites["LID"]])){
				$arSettings = $arTemplateSettings[$arIblockSites["LID"]];
			}
		}

		if(empty($arSettings)){
			return false;
		}

		if(!empty($arSettings["TEMPLATE_USE_AUTO_SAVE_PRICE"]) && $arSettings["TEMPLATE_USE_AUTO_SAVE_PRICE"] == "Y"){

			global $USER;

			$OFFERS_PROPERTY_ID = false;
			$OFFERS_IBLOCK_ID = false;
			$ELEMENT_ID = false;
			$IBLOCK_ID = false;

			if(\Bitrix\Main\Loader::includeModule("currency")){
				$strDefaultCurrency = CCurrency::GetBaseCurrency();
			}

			if(is_array($arg2) && !empty($arg2["PRODUCT_ID"])){

				$rsPriceElement = CIBlockElement::GetList(
					array(),
					array(
						"ID" => $arg2["PRODUCT_ID"],
					),
					false,
					false,
					array("ID", "IBLOCK_ID")
				);

				if($arPriceElement = $rsPriceElement->Fetch()){
					$arCatalog = CCatalog::GetByID($arPriceElement["IBLOCK_ID"]);

					if(is_array($arCatalog)){
						if($arCatalog["OFFERS"] == "Y"){
							$rsElement = CIBlockElement::GetProperty(
								$arPriceElement["IBLOCK_ID"],
								$arPriceElement["ID"],
								"sort",
								"asc",
								array("ID" => $arCatalog["SKU_PROPERTY_ID"])
							);
							$arElement = $rsElement->Fetch();
							if($arElement && !empty($arElement["VALUE"])){
								$ELEMENT_ID = $arElement["VALUE"];
								$IBLOCK_ID = $arCatalog["PRODUCT_IBLOCK_ID"];
								$OFFERS_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
								$OFFERS_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
							}
						}
						elseif(!empty($arCatalog["OFFERS_IBLOCK_ID"])){
							$ELEMENT_ID = $arPriceElement["ID"];
							$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
							$OFFERS_IBLOCK_ID = $arCatalog["OFFERS_IBLOCK_ID"];
							$OFFERS_PROPERTY_ID = $arCatalog["OFFERS_PROPERTY_ID"];
						}
						else{
							$ELEMENT_ID = $arPriceElement["ID"];
							$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
							$OFFERS_IBLOCK_ID = false;
							$OFFERS_PROPERTY_ID = false;
						}
					}
				}
			}

			elseif(is_array($arg1) && !empty($arg1["ID"]) && !empty($arg1["IBLOCK_ID"])){
				$arOffers = CIBlockPriceTools::GetOffersIBlock($arg1["IBLOCK_ID"]);
				$arCatalog = CCatalog::GetByID($arg1["IBLOCK_ID"]);
				if(is_array($arOffers)){
					$ELEMENT_ID = $arg1["ID"];
					$IBLOCK_ID = $arg1["IBLOCK_ID"];
					$OFFERS_IBLOCK_ID = $arOffers["OFFERS_IBLOCK_ID"];
					$OFFERS_PROPERTY_ID = $arOffers["OFFERS_PROPERTY_ID"];
				}
			}

			if($ELEMENT_ID){
				$arPropCache = array();
				if(!array_key_exists($IBLOCK_ID, $arPropCache)){

					$rsProperty = CIBlockProperty::GetByID("MINIMUM_PRICE", $IBLOCK_ID);
					$arProperty = $rsProperty->Fetch();

					if($arProperty){
						$arPropCache[$IBLOCK_ID] = $arProperty["ID"];
					}

					else{
						$arPropCache[$IBLOCK_ID] = false;
					}

				}

				if($arPropCache[$IBLOCK_ID]){

					if($OFFERS_IBLOCK_ID){
						$rsOffers = CIBlockElement::GetList(
							array(),
							array(
								"IBLOCK_ID" => $OFFERS_IBLOCK_ID,
								"PROPERTY_".$OFFERS_PROPERTY_ID => $ELEMENT_ID,
								"ACTIVE" => "Y"
							),
							false,
							false,
							array("ID")
						);
						while($arOffer = $rsOffers->Fetch()){
							$arProductID[] = $arOffer["ID"];
						}

						if (!is_array($arProductID)){
							$arProductID = array($ELEMENT_ID);
						}
					}
					else{
						$arProductID = array($ELEMENT_ID);
					}

					$minPrice = false;
					$maxPrice = false;

					foreach($arProductID as $productID){

						$arDiscountPrice = CCatalogProduct::GetOptimalPrice($productID, 1, $USER->GetUserGroupArray(), false, false, $arCatalog["LID"]);

						if(!empty($strDefaultCurrency) && $strDefaultCurrency != $arDiscountPrice["RESULT_PRICE"]["CURRENCY"]){
							$arDiscountPrice["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($arDiscountPrice["DISCOUNT_PRICE"], $arDiscountPrice["RESULT_PRICE"]["CURRENCY"], $strDefaultCurrency);
						}

						if($minPrice === false || $minPrice > $arDiscountPrice["DISCOUNT_PRICE"]){
							$minPrice = $arDiscountPrice["DISCOUNT_PRICE"];
						}

						if($maxPrice === false || $maxPrice < $arDiscountPrice["DISCOUNT_PRICE"]){
							$maxPrice = $arDiscountPrice["DISCOUNT_PRICE"];
						}
					}

					if($minPrice !== false){
						CIBlockElement::SetPropertyValuesEx(
							$ELEMENT_ID,
							$IBLOCK_ID,
							array(
								"MINIMUM_PRICE" => $minPrice,
								"MAXIMUM_PRICE" => $maxPrice,
							)
						);
					}
				}
			}
		}
	}

}
