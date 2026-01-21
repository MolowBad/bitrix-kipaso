<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(empty($arParams["PRODUCT_ID"])){
	return false;
}

if(!Bitrix\Main\Loader::includeModule("dw.deluxe")){
	return false;
}

$arResult = array();
$arOwnerless = array();

$arParams["SHOW_DELIVERY_IMAGES"] = !empty($arParams["SHOW_DELIVERY_IMAGES"]) ? $arParams["SHOW_DELIVERY_IMAGES"] : "Y";
$arParams["PRODUCT_AVAILABLE"] = !empty($arParams["PRODUCT_AVAILABLE"]) ? $arParams["PRODUCT_AVAILABLE"] : "Y";
$arParams["CONVERT_ENCODING"] = !empty($arParams["CONVERT_ENCODING"]) ? $arParams["CONVERT_ENCODING"] : "N";
$arParams["DEFERRED_MODE"] = !empty($arParams["DEFERRED_MODE"]) ? $arParams["DEFERRED_MODE"] : "N";
$arParams["LOAD_SCRIPT"] = !empty($arParams["LOAD_SCRIPT"]) ? $arParams["LOAD_SCRIPT"] : "Y";
$arParams["SITE_ID"] = !empty($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : SITE_ID;

$arResult["MEASURE_RATIO"] = \DigitalWeb\Basket::getMeasureRatio($arParams["PRODUCT_ID"]);
if(empty($arParams["PRODUCT_QUANTITY"])){
	$arParams["PRODUCT_QUANTITY"] = $arResult["MEASURE_RATIO"];
}

if($arParams["PRODUCT_AVAILABLE"] == "Y" && $arParams["DEFERRED_MODE"] != "Y"){
	$arResult["DELIVERY_ITEMS"] = \DigitalWeb\CalculateDelivery::getCalculatedItems($arParams);
	$arResult["DELIVERY_GROUPS"] = \DigitalWeb\Basket::getDeliveriesGroups();
}

if(!empty($arResult["DELIVERY_GROUPS"]) && !empty($arResult["DELIVERY_ITEMS"])){

	foreach($arResult["DELIVERY_ITEMS"] as $deliveryId => $nextDelivery){

		$findItem = false;

		foreach($arResult["DELIVERY_GROUPS"] as $nextGroup){

			if(($findItem = !empty($nextGroup["ITEMS"][$deliveryId]))){
				break(1);
			}

		}

		if($findItem == false){
			$arOwnerless[$deliveryId] = $nextDelivery;
		}

	}

	foreach($arResult["DELIVERY_GROUPS"] as $nextGroupId => $nextGroup){

		if(($kill = empty($nextGroup["ITEMS"])) !== true){
			foreach($nextGroup["ITEMS"] as $nextGroupItemId => $nextGroupItem){
				if(empty($arResult["DELIVERY_ITEMS"][$nextGroupItem["ID"]])){
					unset($arResult["DELIVERY_GROUPS"][$nextGroupId]["ITEMS"][$nextGroupItemId]);
				}
			}
		}

		if($kill || empty($arResult["DELIVERY_GROUPS"][$nextGroupId]["ITEMS"])){
			unset($arResult["DELIVERY_GROUPS"][$nextGroupId]);
		}

	}

	if(!empty($arOwnerless) && !empty($arResult["DELIVERY_GROUPS"])){
		$arResult["DELIVERY_GROUPS"]["_"] = array(
			"ITEMS" => $arOwnerless,
			"NAME" => GetMessage("DELIVERY_GROUPS_OWNERLESS"),
			"ID" => "_"
		);
	}

}

$this->IncludeComponentTemplate();
