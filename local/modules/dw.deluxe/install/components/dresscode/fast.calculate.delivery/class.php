<?php

namespace DigitalWeb;

class CalculateDelivery {

	public static function getCalculatedItems($arParams = array()){

		//check product id
		if(empty($arParams["PRODUCT_ID"])){
			return false;
		}

		//check modules
		foreach(array("sale", "catalog", "iblock", "dw.deluxe") as $moduleName){
			if(!\Bitrix\Main\Loader::includeModule($moduleName)){
				return false;
			}
		}

		//params
		if(empty($arParams["CALC_ALL_PRODUCTS"])){
			$arParams["CALC_ALL_PRODUCTS"] = "N";
		}

		if(empty($arParams["PRODUCT_QUANTITY"])){
			$arParams["PRODUCT_QUANTITY"] = \DigitalWeb\Basket::getMeasureRatio($arParams["PRODUCT_ID"]);
		}

		//get basket instance
		$DwBasket = \DigitalWeb\Basket::getInstance();

		//set siteId
		if(!empty($arParams["SITE_ID"])){
			$DwBasket->setSiteId($arParams["SITE_ID"]);
		}

		//block basket events
		$DwBasket->disableEvents();

		//set currency code
		$arParams["CURRENCY_CODE"] = $DwBasket->getCurrencyCode();

		//get extra basket
		$extraBasket = self::getExtraBasket($arParams, $DwBasket->getFuserId(), $DwBasket->getSiteId());

		//set basket
		$DwBasket->setOrderBasket($extraBasket);

		//get deliveries
		$arReturn["DELIVERY_ITEMS"] = $DwBasket->getDeliveries();

		//return deliveries
		return $arReturn["DELIVERY_ITEMS"];

	}

	public static function getExtraBasket($arParams, $fuserId, $siteId){

		//vars
		$productBasketExist = false;

		//laad current basket
		if($arParams["CALC_ALL_PRODUCTS"] == "Y"){
			$extraBasket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, $siteId)->getOrderableItems();
			$extraBasketItems = $extraBasket->getBasketItems();
		}

		//create new virtual basket
		$basket = \Bitrix\Sale\Basket::create($siteId);
		$basket->setFUserId($fuserId);
		$basket->isLoadForFUserId = true;

		//copy basket
		if(!empty($extraBasketItems)){

			//each products
			foreach($extraBasketItems as $ix => $obExtraBasketItem){

				//get data
				$extraProductId = $obExtraBasketItem->getProductId();
				$extraQuantity = $obExtraBasketItem->getQuantity();

				//set exist flag && set current qty from input
				if($extraProductId == $arParams["PRODUCT_ID"]){
					$extraQuantity = $arParams["PRODUCT_QUANTITY"];
					$productBasketExist = true;
				}

				$item = $basket->createItem("catalog", $extraProductId);
				$item->setFields([
					"PRODUCT_PROVIDER_CLASS" => "\Bitrix\Catalog\Product\CatalogProvider",
					"CURRENCY" => $arParams["CURRENCY_CODE"],
					"QUANTITY" => $extraQuantity,
					"LID" => $siteId
				]);

			}

		}

		//add basket item
		if(!$productBasketExist){
			$item = $basket->createItem("catalog", $arParams["PRODUCT_ID"]);
			$item->setFields([
				"PRODUCT_PROVIDER_CLASS" => "\Bitrix\Catalog\Product\CatalogProvider",
				"QUANTITY" => $arParams["PRODUCT_QUANTITY"],
				"CURRENCY" => $arParams["CURRENCY_CODE"],
				"LID" => $siteId
			]);
		}

		return $basket;

	}

}?>