<?php

namespace DigitalWeb;

use Bitrix\Main\Loader;


IncludeModuleLangFile(__FILE__);

class Basket
{
	private static $instance = null;
	protected static $basketUserId = 0;
	protected static $basketFuserId = 0;
	protected static $baseBasket = null;
	protected static $basketItems = null;
	protected static $basketSiteId = null;
	protected static $basketDiscounts = null;
	protected static $basketPersonTypeId = 0;
	protected static $basketPersonTypes = array();
	protected static $basketProperties = array();
	protected static $basketUserAccount = array();
	protected static $basketLocation = array();
	protected static $basketFirstPaySystem = array();
	protected static $basketFirstDelivery = array();
	protected static $basketInnerPayment = array();
	protected static $basketExtraServices = array();
	protected static $basketDeliveriesGroups = array();
	protected static $basketFirstPaySystemId = 0;
	protected static $basketFirstDeliveryId = 0;
	protected static $basketCurrencyCode = "";
	protected static $basketLocationCode = 0;
	protected static $basketLocationZip = 0;
	protected static $basketLocationId = 0;
	protected static $basketStoreId = 0;
	protected static $basketCurrency = array();
	protected static $staticEvents = false;
	protected static $enableEvents = true;
	protected static $order = null;
	protected static $orderSum = 0;
	protected static $orderInfo = null;
	protected static $orderPayments = array();
	protected static $orderDeliveries = array();
	protected static $orderPaymentServices = array();
	protected static $arSettings = array();
	protected static $arErrors = array();

	public function __construct()
	{
	}

	public static function getInstance()
	{

		if (is_null(self::$instance)) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function getBasketItems()
	{
		if (
			!Loader::includeModule("catalog") ||
			!Loader::includeModule("iblock") ||
			!Loader::includeModule("sale")
		) {
			return false;
		}

		//coupons
		\Bitrix\Sale\DiscountCouponsManager::init();

		//get basket for current user
		$basket = self::getBasket();

		//refresh basket
		$refreshStrategy = \Bitrix\Sale\Basket\RefreshFactory::create(\Bitrix\Sale\Basket\RefreshFactory::TYPE_FULL);
		$refreshResult = $basket->refresh($refreshStrategy);

		if ($refreshResult->isSuccess()) {
			$basket->save();
		}

		//arrays
		$arBasketItems = array();

		//get items
		if (!$basketItems = $basket->getBasketItems()) {
			return [];
		}

		//get items data
		foreach ($basketItems as $basketIndex => $nextBasketItem) {

			//check
			if ($nextBasketItem->canBuy() && !$nextBasketItem->isDelay()) {

				//get item basket id
				$basketId = $nextBasketItem->getId();

				//save fields
				$arBasketItems[$basketId] = array(
					"BASKET_ID" => $basketId,
					"PRODUCT_ID" => $nextBasketItem->getProductId(),
					"QUANTITY" => $nextBasketItem->getQuantity(),
					"WEIGHT" => $nextBasketItem->getWeight(),
					"NAME" => $nextBasketItem->getField("NAME"),
				);

			}

			//clear no available products
			else {

				//get item basket id
				$basketId = $nextBasketItem->getId();

				//delete product by basket id
				$basket->getItemById($basketId)->delete();

			}

		}

		$basket->save();

		//save basket values
		self::$basketItems = $basketItems;

		return $arBasketItems;

	}

	public static function addProductPrices($arItems)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arBasketItems = array();

		//special
		$basket = self::getBasket();
		$currencyCode = self::getCurrencyCode();

		//check basket
		if (empty($basket)) {
			return false;
		}

		$order = self::getOrder();
		$order->appendBasket(self::getBasketClone());

		$discounts = $order->getDiscount();
		$discounts->calculate();

		$arDiscounts = $discounts->getApplyResult(true);
		$arPrices = $arDiscounts["PRICES"]["BASKET"];

		//save price items
		if (!empty($arPrices)) {

			//save data
			foreach ($arPrices as $priceIndex => $nextPriceItem) {

				//check input item with index
				if (!empty($arItems[$priceIndex])) {

					//fill basket item fields
					$basketItem = $basket->getItemByBasketCode($priceIndex);
					$basketItem->setFieldNoDemand("PRICE", $nextPriceItem["PRICE"]);
					$basketItem->setFieldNoDemand("BASE_PRICE", $nextPriceItem["BASE_PRICE"]);
					$basketItem->setFieldNoDemand("DISCOUNT_PRICE", $nextPriceItem["DISCOUNT"]);

					//get formated
					$nextPriceItem["BASE_PRICE_FORMATED"] = \CCurrencyLang::CurrencyFormat($nextPriceItem["BASE_PRICE"], $currencyCode);
					$nextPriceItem["PRICE_FORMATED"] = \CCurrencyLang::CurrencyFormat($nextPriceItem["PRICE"], $currencyCode);

					//save values
					$arBasketItems[$priceIndex] = array_merge($arItems[$priceIndex], $nextPriceItem);

				}

			}

		}

		//save values
		self::$basketDiscounts = $arDiscounts;

		//return items
		return $arBasketItems;

	}

	public static function getDiscountListFull()
	{

		if (!empty(self::$basketDiscounts)) {

			//vars
			$arDiscounts = self::$basketDiscounts;

			if (!empty($arDiscounts["FULL_DISCOUNT_LIST"])) {
				return $arDiscounts["FULL_DISCOUNT_LIST"];
			}

		}

		return false;

	}


	public static function getAppliedDiscounts()
	{

		//vars
		$arAppliedDiscounts = array();

		//check discounts from static
		if (!empty(self::$basketDiscounts)) {

			//special
			$basket = self::getBasket();
			$arDiscounts = self::$basketDiscounts;

			if (!empty($arDiscounts["DISCOUNT_LIST"])) {
				foreach ($arDiscounts["DISCOUNT_LIST"] as $nextDiscount) {

					//check discounts by real id
					if (!empty($arDiscounts["FULL_DISCOUNT_LIST"][$nextDiscount["REAL_DISCOUNT_ID"]])) {

						//get discounts from full
						$arAppliedDiscounts[$nextDiscount["REAL_DISCOUNT_ID"]] = $arDiscounts["FULL_DISCOUNT_LIST"][$nextDiscount["REAL_DISCOUNT_ID"]];

						//check basket result
						if (empty($arAppliedDiscounts[$nextDiscount["REAL_DISCOUNT_ID"]]["RESULT"]["BASKET"])) {
							$arAppliedDiscounts[$nextDiscount["REAL_DISCOUNT_ID"]]["RESULT"]["BASKET"] = array();
						}

						//fill discounts
						$arAppliedDiscounts[$nextDiscount["REAL_DISCOUNT_ID"]]["RESULT"]["BASKET"] = array_merge(
							$arAppliedDiscounts[$nextDiscount["REAL_DISCOUNT_ID"]]["RESULT"]["BASKET"],
							self::getFormattedBasketItemsInDiscount($basket, $nextDiscount, $arDiscounts)
						);

					}
				}
			}

		}

		return $arAppliedDiscounts;

	}

	public static function getFormattedBasketItemsInDiscount($basket, $discountData, $arDiscounts)
	{

		//vars
		$arItems = array();

		foreach ($arDiscounts["PRICES"]["BASKET"] as $basketCode => $priceData) {
			if (empty($priceData["DISCOUNT"]) || !empty($priceData["PRICE"]) || empty($arDiscounts["RESULT"]["BASKET"][$basketCode])) {
				continue;
			}

			//special vars
			$found = false;

			foreach ($arDiscounts["RESULT"]["BASKET"][$basketCode] as $nextDiscount) {
				if ($nextDiscount["DISCOUNT_ID"] == $discountData["ID"]) {
					$found = true;
				}
			}

			//check flag
			if (empty($found)) {
				continue;
			}

			$basketItem = $basket->getItemByBasketCode($basketCode);
			if (empty($basketItem) || $basketItem->getField("MODULE") != "catalog") {
				continue;
			}

			$arItems[] = array(
				"PRODUCT_ID" => $basketItem->getProductId(),
				"VALUE_PERCENT" => "100",
				"MODULE" => "catalog",
			);
		}

		return $arItems;
	}

	public static function addProductsInfo($arItems)
	{

		//check modules
		if (!Loader::includeModule("catalog")) {
			return false;
		}

		//vars
		$arProducts = $arItems;
		$arProductsId = array();
		$arMatrix = array();

		//special
		//true == not set to order
		$basket = self::getBasket(true);

		//check data
		if (!empty($arItems)) {

			//each items
			foreach ($arItems as $itemIndex => $nextItem) {

				//check product id
				if (!empty($nextItem["PRODUCT_ID"])) {

					//save id for getlist filter
					$arProductsId[$nextItem["PRODUCT_ID"]] = $nextItem["PRODUCT_ID"];

					//check multi product & write matrix
					if (empty($arMatrix[$nextItem["PRODUCT_ID"]])) {
						$arMatrix[$nextItem["PRODUCT_ID"]] = array($nextItem["BASKET_ID"]);
					}

					//multi products(gift or basket properties)
					else {
						$arMatrix[$nextItem["PRODUCT_ID"]][] = $nextItem["BASKET_ID"];
					}

				}

			}

		}

		if (!empty($arProductsId)) {

			//get additonal info for products
			$arSelect = array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "ACTIVE", "NAME", "CATALOG_QUANTITY", "CATALOG_MEASURE", "CATALOG_AVAILABLE", "CATALOG_QUANTITY_TRACE");
			$arFilter = array("ID" => $arProductsId);
			$res = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
			while ($nextElement = $res->GetNextElement()) {

				//get info
				$arProduct = $nextElement->GetFields();

				//check active
				if ($arProduct["ACTIVE"] == "N") {

					//clear
					foreach ($arMatrix[$arProduct["ID"]] as $basketId) {
						//delete product by basket id
						$basket->getItemById($basketId)->delete();
						unset($arProducts[$basketId]);
						continue;
					}

				}

				//apply changes
				// $basket->save();

				//next
				$arProduct["PROPERTIES"] = $nextElement->GetProperties();
				$arProduct["CATALOG_MEASURE_RATIO"] = self::getMeasureRatio($arProduct["ID"]);
				$arProduct["COUNT_PRICES"] = self::getCountPrices($arProduct["ID"]);
				$arProduct["PICTURE"] = self::getProductPicture($arProduct["ID"]);
				$arProduct["STORES"] = self::getStoresByProductId($arProduct["ID"]);

				//save info
				foreach ($arMatrix[$arProduct["ID"]] as $basketId) {
					if (!empty($arProducts[$basketId])) {
						$arProducts[$basketId] = array_merge(
							$arProducts[$basketId],
							$arProduct
						);
					}
				}
			}

		}

		return $arProducts;

	}

	public static function getProductPicture($productId)
	{

		//check modules
		if (!Loader::includeModule("iblock") || !Loader::includeModule("catalog")) {
			return false;
		}

		$pictureWidth = !empty(self::$arSettings["BASKET_PICTURE_WIDTH"]) ? self::$arSettings["BASKET_PICTURE_WIDTH"] : 200;
		$pictureHeight = !empty(self::$arSettings["BASKET_PICTURE_HEIGHT"]) ? self::$arSettings["BASKET_PICTURE_HEIGHT"] : 200;

		//vars
		$arPicture = array();
		$pictureId = false;
		$skuId = false;

		if (!empty($productId)) {

			//get additonal info for products
			$arSelect = array("ID", "IBLOCK_ID", "NAME", "DETAIL_PICTURE");
			$arFilter = array("ID" => $productId, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
			$res = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
			if ($nextElement = $res->GetNextElement()) {

				//check detail picture
				$arProduct = $nextElement->GetFields();
				if (!empty($arProduct["DETAIL_PICTURE"])) {
					$pictureId = $arProduct["DETAIL_PICTURE"];
				}

				//check product to have sku offers
				else {

					//get parent product for current offer(if product id == offer id)
					$skuParentProduct = \CCatalogSku::GetProductInfo($arProduct["ID"]);
					if (!empty($skuParentProduct)) {

						//get product info
						$dbProduct = \CIBlockElement::GetByID($skuParentProduct["ID"]);
						if ($arParentProduct = $dbProduct->GetNext()) {
							if (!empty($arParentProduct["DETAIL_PICTURE"])) {
								$pictureId = $arParentProduct["DETAIL_PICTURE"];
							}
						}

					}

				}

			}

			//resize picture
			if (!empty($pictureId)) {
				$arPicture = \CFile::ResizeImageGet($pictureId, array("width" => $pictureWidth, "height" => $pictureHeight), BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 100);
			}

			//no photo
			if (empty($arPicture)) {
				$arPicture = array("src" => SITE_TEMPLATE_PATH . "/images/empty.svg");
			}

		}

		return $arPicture;

	}

	public static function getStores($arProducts = array())
	{

		//check modules
		if (!Loader::includeModule("catalog")) {
			return false;
		}

		//vars
		$arStores = array();

		//get stores
		$dbStores = \CCatalogStore::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			array("ISSUING_CENTER" => "Y", "+SITE_ID" => self::getSiteId(), "ACTIVE" => "Y"),
			false,
			false,
			array("ID", "TITLE", "ADDRESS", "DESCRIPTION", "IMAGE_ID", "PHONE", "SCHEDULE", "GPS_N", "GPS_S", "ISSUING_CENTER", "SITE_ID")
		);

		while ($arNextStore = $dbStores->Fetch()) {

			//get products status
			if (!empty($arProducts)) {
				$arNextStore["PRODUCTS_STATUS"] = \DigitalWeb\Basket::getStoreProductsStatus($arNextStore["ID"], $arProducts);
			}

			//push
			$arStores[$arNextStore["ID"]] = $arNextStore;

		}

		return $arStores;

	}

	public static function getStoresByProductId($productId)
	{

		//check modules
		if (!Loader::includeModule("catalog")) {
			return false;
		}

		//vars
		$arStores = array();

		if (!empty($productId)) {
			//stores information
			$rsStore = \CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $productId), false, false, array("ID", "AMOUNT", "STORE_ID"));
			while ($arNextStore = $rsStore->GetNext()) {
				$arStores[$arNextStore["STORE_ID"]] = $arNextStore;
			}
		}

		return $arStores;

	}

	public static function getCountPrices($productId)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$priceCount = 1;

		if (!empty($productId)) {

			//price count
			$dbPrice = \CPrice::GetList(
				array(),
				array("PRODUCT_ID" => $productId, "CAN_ACCESS" => "Y"),
				false,
				false,
				array("ID")
			);

			$priceCount = $dbPrice->SelectedRowsCount();

		}

		return $priceCount;

	}

	public static function getMeasures()
	{

		//check modules
		if (!Loader::includeModule("catalog")) {
			return false;
		}

		//vars
		$arMeasures = array();

		//get measures
		$rsMeasure = \CCatalogMeasure::getList();
		while ($arNextMeasure = $rsMeasure->Fetch()) {
			$arMeasures[$arNextMeasure["ID"]] = $arNextMeasure;
		}

		return $arMeasures;

	}

	public static function getMeasureRatio($productId)
	{

		//check modules
		if (!Loader::includeModule("catalog")) {
			return false;
		}

		//get measure ratio
		$productMeasureRatio = 1;
		$rsMeasureRatio = \CCatalogMeasureRatio::getList(
			array(),
			array("PRODUCT_ID" => $productId),
			false,
			false,
			array()
		);

		if ($arProductMeasureRatio = $rsMeasureRatio->Fetch()) {
			if (!empty($arProductMeasureRatio["RATIO"])) {
				$productMeasureRatio = $arProductMeasureRatio["RATIO"];
			}
		}

		return $productMeasureRatio;

	}

	//order functions
	public static function getOrderInfo()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arOrder = array();

		//special
		$order = self::getOrder();
		$basket = self::getBasket();

		//check special
		if (empty($order) || empty($basket)) {
			return false;
		}

		//currency
		$currencyCode = self::getCurrencyCode();

		//set location
		self::setOrderLocation();
		$order->setPersonTypeId(self::getPersonTypeId());

		if (!$order->getBasket()) {
			$order->appendBasket(self::getBasketClone());
		}

		//set currency
		$order->setFields(array("CURRENCY" => $currencyCode));

		//push
		$arOrder["PROPERTIES"] = self::getOrderProperties();
		$arOrder["DELIVERIES"] = self::getDeliveries();
		$arOrder["PAYSYSTEMS"] = self::getPaySystems();
		$arOrder["INNER_PAYMENT"] = self::getInnerPayment();
		$arOrder["PROPERTIES"] = self::checkPropertiesRelations();

		//save values
		self::$orderSum = $order->getPrice();
		self::$orderInfo = $arOrder;

		//execute
		if (self::$staticEvents === true) {
			self::executeEvent("OnSaleComponentOrderOneStepProcess");
		}

		return $arOrder;

	}

	public static function setOrderBasket($basket)
	{

		//check instance
		if ($basket instanceof \Bitrix\Sale\Basket) {

			//check modules
			if (
				!Loader::includeModule("catalog") ||
				!Loader::includeModule("iblock") ||
				!Loader::includeModule("sale")
			) {
				return false;
			}

			//coupons
			\Bitrix\Sale\DiscountCouponsManager::init();

			//virtual order
			$order = self::getOrder();

			//set person type
			$order->setPersonTypeId(self::getPersonTypeId());

			//set location
			self::setOrderLocation();

			//save to static
			self::$baseBasket = $basket;

			//set basket to order
			$order->setBasket($basket);

		}

		return false;
	}

	public static function setOrderLocation($order = null)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//special
		if (empty($order)) {
			$order = self::getOrder();
		}

		//location
		$locationId = self::getUserLocation();
		$locationZip = self::getUserLocationZip();
		$locationCode = self::getUserLocationCode();

		//get property collection
		$propertyCollection = $order->getPropertyCollection();

		//set location order user fileds
		foreach ($propertyCollection as $propertyItem) {

			//get propetry
			$arProperty = $propertyItem->getProperty();

			//set location
			if ($arProperty["IS_LOCATION"] === "Y") {
				$locationCode = empty($locationCode) && !empty($arProperty["DEFAULT_VALUE"]) ? $arProperty["DEFAULT_VALUE"] : $locationCode;
				$propertyItem->setValue($locationCode);
			}

			//set zip
			elseif ($arProperty["IS_ZIP"] === "Y" && !empty($locationZip)) {
				$propertyItem->setValue($locationZip);
			}

		}

		//set location order fields
		$order->setFields(
			array(
				"DELIVERY_LOCATION" => $locationCode,
				"DELIVERY_LOCATION_ZIP" => $locationZip
			)
		);

		return $locationCode;
	}

	public static function getOrderProperties()
	{

		//check static
		if (!empty(self::$basketProperties)) {
			return self::$basketProperties;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//globals
		global $USER, $DB;

		//vars
		$arResult = array(
			"PROPERTIES" => array(),
			"GROUPS" => array()
		);

		//other
		$arProperties = array();
		$arRelations = array();

		//get auth info
		$isAuthorized = $USER->IsAuthorized();

		//get current user ifno
		if ($isAuthorized) {
			$rsUser = $USER->GetByID($USER->GetID());
			$arUser = $rsUser->Fetch();
		}

		//special
		$order = \Bitrix\Sale\Order::create(self::getSiteId(), self::getUserId());

		//set location
		$locationCode = self::setOrderLocation($order);

		//get property collection
		$propertyCollection = $order->getPropertyCollection();

		//get group properties
		$arResult["GROUPS"] = $propertyCollection->getGroups();

		//get properties from db
		$propertiesSql = "SELECT * FROM b_sale_order_props WHERE ACTIVE = 'Y' AND UTIL = 'N' ORDER BY SORT ASC";

		//sql query
		$dbProperties = $DB->Query($propertiesSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		//get result
		while ($property = $dbProperties->Fetch()) {
			$arProperties[$property["ID"]] = $property;
		}

		//get relations properties from db
		$relationsSql =
			"SELECT ENTITY_ID, PR.ENTITY_TYPE, PROPERTY_ID " .
			"FROM b_sale_order_props_relation PR " .
			"LEFT JOIN b_sale_order_props SP ON (PR.PROPERTY_ID = SP.ID) " .
			"WHERE SP.ACTIVE = 'Y' AND SP.UTIL = 'N' ";

		//sql query
		$dbRelations = $DB->Query($relationsSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		//get result
		while ($relation = $dbRelations->Fetch()) {
			$arRelations[$relation["PROPERTY_ID"]][] = $relation;
		}

		//each properties
		foreach ($arProperties as $arProperty) {

			//current value
			$arProperty["CURRENT_VALUE"] = $arProperty["DEFAULT_VALUE"];

			//check enum property
			if ($arProperty["TYPE"] == "ENUM") {

				//get property variants
				$variantsSql =
					"SELECT NAME, VALUE " .
					"FROM b_sale_order_props_variant " .
					"WHERE ORDER_PROPS_ID ='" . $arProperty["ID"] . "' ";

				//sql query
				$dbVariants = $DB->Query($variantsSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

				//get result
				while ($variant = $dbVariants->Fetch()) {
					$arProperty["OPTIONS"][$variant["VALUE"]] = $variant["NAME"];
				}

				//set current value
				if (empty($arProperty["MULTIPLE"]) || $arProperty["MULTIPLE"] == "N") {
					if (!empty($arProperty["OPTIONS"]) && empty($arProperty["CURRENT_VALUE"])) {
						$arProperty["CURRENT_VALUE"] = array_key_first($arProperty["OPTIONS"]);
					}
				}

			}

			//check settings field
			if (!empty($arProperty["SETTINGS"])) {
				$arProperty["ON_SETTINGS"] = unserialize($arProperty["SETTINGS"]);
			}

			//push relations
			if (!empty($arRelations[$arProperty["ID"]])) {
				$arProperty["RELATION"] = $arRelations[$arProperty["ID"]];
			}

			//location
			if ($arProperty["TYPE"] == "LOCATION") {
				$arProperty["LOCATION_ID"] = !empty($locationCode) ? \CSaleLocation::getLocationIDbyCODE($locationCode) : self::getUserLocation();
				$arProperty["LOCATION"] = self::getLocationById($arProperty["LOCATION_ID"]);
			}

			//set fields by user profile
			if ($isAuthorized) {

				//email
				if ($arProperty["IS_EMAIL"] == "Y") {
					$arProperty["CURRENT_VALUE"] = $USER->GetEmail();
				}

				//name
				elseif ($arProperty["IS_PROFILE_NAME"] == "Y" || $arProperty["IS_PAYER"] == "Y") {

					//get username
					$arUserName = array(
						"LAST_NAME" => $USER->GetLastName(),
						"FIRST_NAME" => $USER->GetFirstName()
					);

					//check user fields
					if (!empty($arUserName)) {
						$arProperty["CURRENT_VALUE"] = implode(" ", $arUserName);
					}

				}

				//phone
				elseif ($arProperty["IS_PHONE"] == "Y") {
					if (!empty($arUser["PERSONAL_MOBILE"])) {
						$arProperty["CURRENT_VALUE"] = $arUser["PERSONAL_MOBILE"];
					}
				}

				//address
				elseif ($arProperty["IS_ADDRESS"] == "Y") {
					if (!empty($arUser["PERSONAL_STREET"])) {
						$arProperty["CURRENT_VALUE"] = $arUser["PERSONAL_STREET"];
					}
				}

				//zip
				elseif ($arProperty["IS_ZIP"] == "Y") {
					if (!empty($arUser["PERSONAL_ZIP"]) && empty($arProperty["CURRENT_VALUE"])) {
						$arProperty["CURRENT_VALUE"] = $arUser["PERSONAL_ZIP"];
					}
				}

			}

			//save result
			$arResult["PROPERTIES"][$arProperty["ID"]] = $arProperty;

		}

		//save static
		if (!empty($arResult)) {
			self::$basketProperties = $arResult;
		}

		return $arResult;

	}

	public static function getShipment()
	{

		//special
		$currencyCode = self::getCurrencyCode();
		$basket = self::getBasket();
		$order = self::getOrder();

		//create shipment
		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();

		//get item collection
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		//set currency
		$shipment->setFields(array("CURRENCY" => $currencyCode));

		//insert items to shipment collection
		foreach ($basket as $basketItem) {

			//set quantity
			$shipmentItem = $shipmentItemCollection->createItem($basketItem);
			$shipmentItem->setQuantity($basketItem->getQuantity());

			//set dimensions
			if (strlen($shipmentItem->getField("DIMENSIONS"))) {
				$shipmentItem->setField("DIMENSIONS", unserialize($shipmentItem->getField("DIMENSIONS")));
			}

		}

		return $shipment;
	}

	public static function getDeliveries()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arDeliveries = array();
		$arPushServices = array();

		//int
		$deliveryIteration = 0;

		//special
		$order = self::getOrder();
		$shipment = self::getShipment();
		$currencyCode = self::getCurrencyCode();

		//get shipment collection
		$shipmentCollection = $order->getShipmentCollection();

		//get deliveries by filter
		$arDeliveriesList = \Bitrix\Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);

		//check empty
		if (!empty($arDeliveriesList)) {

			//each delivery systems
			foreach ($arDeliveriesList as $arNextDelivery) {

				//vars
				$deliveryLogo = null;
				$deliveryParentId = null;
				$deliveryIsProfile = false;

				//arrays
				$arFirstDelivery = array();

				//is automatic delivery system
				if ($arNextDelivery->isProfile()) {
					$deliveryName = $arNextDelivery->getNameWithParent();
					$deliveryId = $arNextDelivery->getId();
					$deliveryIsProfile = true;
				}

				//is static delivery system
				else {
					$deliveryName = $arNextDelivery->getName();
					$deliveryId = $arNextDelivery->getId();
				}

				//other
				$deliverySort = $arNextDelivery->getSort();
				$deliveryCode = $arNextDelivery->getCode();
				$deliveryParentId = $arNextDelivery->getParentId();
				$deliveryStores = \Bitrix\Sale\Delivery\ExtraServices\Manager::getStoresList($deliveryId);

				//calc
				$shipment->calculateDelivery();
				$calcResult = $arNextDelivery->calculate($shipment);
				$deliveryPeriodDescription = $calcResult->getPeriodDescription();
				$deliveryDescriptionCalculate = $calcResult->getDescription();
				$deliveryBasePrice = \Bitrix\Sale\PriceMaths::roundPrecision($calcResult->getPrice());
				$deliveryBasePriceFormated = SaleFormatCurrency($deliveryBasePrice, $currencyCode);

				//get discounts from order
				$shipment->setFields(array("DELIVERY_ID" => $deliveryId));
				//set extra services
				if (!empty(self::$basketExtraServices) && !empty(self::$basketFirstDeliveryId)) {

					//check current delivery
					if (self::$basketFirstDeliveryId == $deliveryId) {

						//push values
						foreach (self::$basketExtraServices as $nextServices) {
							$arPushServices[$nextServices["id"]] = $nextServices["value"];
						}

						//set
						$shipment->setExtraServices($arPushServices);

					}

				}

				$shipmentCollection->calculateDelivery();
				$deliveryPrice = \Bitrix\Sale\PriceMaths::roundPrecision($order->getDeliveryPrice());
				$deliveryPriceFormated = SaleFormatCurrency($deliveryPrice, $currencyCode);

				//get description
				$deliveryDescription = $arNextDelivery->getDescription();

				//get logo
				if (!empty($arNextDelivery->getLogotip())) {
					$deliveryLogo = \CFile::ResizeImageGet($arNextDelivery->getLogotip(), array("width" => 120, "height" => 120), BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 100);
				}

				//set checked flag
				$deliveryChecked = ((self::$basketFirstDeliveryId == $deliveryId) ? "Y" : (empty(self::$basketFirstDeliveryId) && $deliveryIteration === 0 ? "Y" : "N"));

				//write to order array
				$arDeliveries[$deliveryId] = array(
					"ID" => $deliveryId,
					"SORT" => $deliverySort,
					"CODE" => $deliveryCode,
					"NAME" => $deliveryName,
					"INDEX" => $deliveryIteration,
					"PARENT_ID" => $deliveryParentId,
					"IS_PROFILE" => $deliveryIsProfile,
					"STORES" => $deliveryStores,
					"DESCRIPTION" => $deliveryDescription,
					"DESCRIPTION_CALCULATE" => $deliveryDescriptionCalculate,
					"BASE_PRICE" => $deliveryBasePrice,
					"BASE_PRICE_FORMATED" => $deliveryBasePriceFormated,
					"PRICE_FORMATED" => $deliveryPriceFormated,
					"PERIOD_DESCRIPTION" => $deliveryPeriodDescription,
					"PRICE" => $deliveryPrice,
					"LOGOTIP" => $deliveryLogo,
					"CHECKED" => $deliveryChecked
				);

				//count iterations
				$deliveryIteration++;

			}

		}

		//set first delivery system
		if (!empty($arDeliveries) && (empty(self::$basketFirstDeliveryId) || !empty($arDeliveries[self::$basketFirstDeliveryId]))) {

			//check static
			if (empty(self::$basketFirstDeliveryId) || empty($arDeliveries[self::$basketFirstDeliveryId])) {

				//get first delivery id
				$arFirstDelivery = array_slice($arDeliveries, 0, 1);

				//save static
				self::$basketFirstDeliveryId = $arFirstDelivery[0]["ID"];

				//set checked option
				$arDeliveries[self::$basketFirstDeliveryId]["CHECKED"] = "Y";

			}

			//for events
			$arEventResult["DELIVERY"] = &$arDeliveries;
			$arEventVars = self::getBasketVars();

			//execute event
			self::executeEvent("OnSaleComponentOrderOneStepDelivery", $arEventResult, $arEventVars);

			//check static
			if (!empty($arDeliveries[self::$basketFirstDeliveryId])) {
				$arCurrentDelivery = $arDeliveries[self::$basketFirstDeliveryId];
			}

			//get first
			else {
				$arCurrentDelivery = current($arDeliveries);
			}

			//set delivery id
			$shipment->setFields(
				array(
					"DELIVERY_ID" => $arCurrentDelivery["ID"],
					"DELIVERY_NAME" => $arCurrentDelivery["NAME"],
				)
			);

			//set store
			if (!empty(self::$basketStoreId)) {
				$shipment->setStoreId(self::$basketStoreId);
			}

			//final calculate
			$shipmentCollection->calculateDelivery();

		}

		//set empty system
		else {

			//get empty delivery system
			$emptyDeliverySystemId = \Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			$emptyDeliverySystem = \Bitrix\Sale\Delivery\Services\Manager::getById($emptyDeliverySystemId);

			//check exist
			if (!empty($emptyDeliverySystem)) {

				//set delivery id
				$shipment->setFields(
					array(
						"DELIVERY_ID" => $emptyDeliverySystem["ID"],
						"DELIVERY_NAME" => $emptyDeliverySystem["NAME"],
					)
				);

			}

		}

		//write static values
		self::$orderDeliveries = $arDeliveries;

		//get extra services for current delivery
		if (!empty($arCurrentDelivery)) {
			$arExtraServices = self::getExtraServices($arCurrentDelivery["ID"]);
			$arDeliveries[$arCurrentDelivery["ID"]]["EXTRA_SERVICES"] = $arExtraServices;
			$arCurrentDelivery["EXTRA_SERVICES"] = $arExtraServices;
		}

		//write static
		self::$basketFirstDelivery = $arCurrentDelivery;

		//result
		return $arDeliveries;
	}

	public static function getDeliveriesGroups()
	{

		//check static
		if (!empty(self::$basketDeliveryGroups)) {
			return self::$basketDeliveryGroups;
		}

		//vars
		$arGroups = array();
		$arDeliveries = array();

		//get groups
		$dbRes = \Bitrix\Sale\Delivery\Services\Table::getList(
			array(

				"filter" => array(
					"!=CLASS_NAME" => "\Bitrix\Sale\Delivery\Services\EmptyDeliveryService",
					"=ACTIVE" => "Y"
				),

				"select" => array(
					"ID",
					"NAME",
					"PARENT_ID",
					"CLASS_NAME"
				),

				"order" => array(
					"SORT" => "ASC",
					"PARENT_ID" => "ASC",
					"NAME" => "ASC"
				)

			)
		);

		//push to result
		while ($arService = $dbRes->fetch()) {

			//write groups
			if ($arService["CLASS_NAME"] == "\Bitrix\Sale\Delivery\Services\Group") {
				$arGroups[$arService["ID"]] = $arService;
			}

			//write deliveries
			else {
				$arDeliveries[$arService["ID"]] = $arService;
			}

		}

		//set groups
		if (!empty($arDeliveries)) {

			//each all deliveries
			foreach ($arDeliveries as $id => &$nextDelivery) {

				//check parent id
				if (!empty($nextDelivery["PARENT_ID"])) {

					//check group
					if (!empty($arGroups[$nextDelivery["PARENT_ID"]])) {
						$arGroups[$nextDelivery["PARENT_ID"]]["ITEMS"][$nextDelivery["ID"]] = $nextDelivery;
					}

					//search group id by parent
					else {
						if (!empty($arGroups[$arDeliveries[$nextDelivery["PARENT_ID"]]["PARENT_ID"]])) {
							$arGroups[$arDeliveries[$nextDelivery["PARENT_ID"]]["PARENT_ID"]]["ITEMS"][$nextDelivery["ID"]] = $nextDelivery;
						}
					}

				}

			}

		}

		//write static
		self::$basketDeliveriesGroups = $arGroups;

		//result
		return $arGroups;
	}

	public static function getExtraServices(int $deliveryId = 0)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arServices = array();

		//check argument vars
		if (!empty($deliveryId)) {

			//get service list
			$deliveryExtraServices = \Bitrix\Sale\Delivery\ExtraServices\Manager::getExtraServicesList($deliveryId);

			//push
			if (!empty($deliveryExtraServices)) {
				foreach ($deliveryExtraServices as $nextExtraService) {
					if (self::checkExtraServiceRights($nextExtraService)) {
						$arServices[$nextExtraService["ID"]] = $nextExtraService;
					}
				}
			}

		}

		//result
		return $arServices;
	}

	public static function getExtraServicesAll()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arServices = array();

		//special
		$order = \Bitrix\Sale\Order::create(self::getSiteId(), self::getUserId());

		//create shipment
		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();

		//delivery service list
		$arDeliveryServiceAll = \Bitrix\Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment, \Bitrix\Sale\Services\Base\RestrictionManager::MODE_MANAGER);

		//check deliveries
		if (!empty($arDeliveryServiceAll)) {

			//each delivery systems
			foreach ($arDeliveryServiceAll as $arNextDelivery) {

				//get delivery id
				$deliveryId = $arNextDelivery->getId();

				//get service list
				$deliveryExtraServices = \Bitrix\Sale\Delivery\ExtraServices\Manager::getExtraServicesList($deliveryId);

				//push
				if (!empty($deliveryExtraServices)) {
					$arServices[$deliveryId] = $deliveryExtraServices;
				}

			}

		}

		return $arServices;
	}

	public static function getPaySystems()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arPaySystems = array();

		//special
		$order = self::getOrder();
		$basket = self::getBasket();
		$currencyCode = self::getCurrencyCode();
		$innerPaymentId = \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();

		$paymentCollection = $order->getPaymentCollection();
		$payment = $paymentCollection->createItem();

		$payment->setField("SUM", $order->getPrice());
		$payment->setField("CURRENCY", $currencyCode);

		//get PaySystems info
		$arPaySystemServices = \Bitrix\Sale\PaySystem\Manager::getListWithRestrictions($payment);

		//check
		if (!empty($arPaySystemServices)) {

			//int
			$paySystemIteration = 0;

			//each paysystems
			foreach ($arPaySystemServices as $arNextPaySystem) {

				//check
				if (!empty($arNextPaySystem["ID"])) {

					//payment from personal account
					if ($arNextPaySystem["ID"] == $innerPaymentId) {
						continue;
					}

					//get resized logo
					if (!empty($arNextPaySystem["LOGOTIP"])) {
						$arNextPaySystem["LOGOTIP"] = \CFile::ResizeImageGet($arNextPaySystem["LOGOTIP"], array("width" => 100, "height" => 100), BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 100);
					}

					//push index for js sort
					$arNextPaySystem["INDEX"] = $paySystemIteration;

					//save to array
					$arPaySystems[$arNextPaySystem["ID"]] = $arNextPaySystem;

					//count iterations
					$paySystemIteration++;

				}

			}

		}

		//set first paySystem
		if (!empty($arPaySystems)) {

			//check static
			if (!empty($arPaySystems[self::$basketFirstPaySystemId])) {
				$arCurrentPaySystem = $arPaySystems[self::$basketFirstPaySystemId];
			}

			//first
			else {
				$arCurrentPaySystem = current($arPaySystems);
			}

			//set paysystem fields
			$payment->setFields(
				array(
					"PAY_SYSTEM_ID" => $arCurrentPaySystem["ID"],
					"PAY_SYSTEM_NAME" => $arCurrentPaySystem["NAME"]
				)
			);

		}

		//write static values
		if (!empty($arCurrentPaySystem)) {
			self::$basketFirstPaySystem = $arCurrentPaySystem;
			self::$basketFirstPaySystemId = $arCurrentPaySystem["ID"];
		}

		//for events
		$arEventParams = array("DELIVERY_TO_PAYSYSTEM" => "d2p");
		$arEventResult["DELIVERY"] = self::$orderDeliveries;
		$arEventVars = self::getBasketVars();

		//execute event
		self::executeEvent("OnSaleComponentOrderOneStepPaySystem", $arEventResult, $arEventVars, $arEventParams);

		//result
		return $arPaySystems;

	}

	public static function getMainPayment()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//special
		$order = self::getOrder();

		//get main payment
		foreach ($order->getPaymentCollection() as $payment) {
			if ($payment->getPaymentSystemId() != \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()) {
				return $payment;
			}
		}

		return false;
	}

	public static function getInnerPayment()
	{

		//check static
		if (!empty(self::$basketInnerPayment)) {
			return self::$basketInnerPayment;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//globals
		global $USER;

		//vars
		$arInnerPayment = array();

		//special
		$order = self::getOrder();
		$arUserAccount = self::getUserAccount();
		$paymentCollection = $order->getPaymentCollection();

		//check balance
		if (!empty($arUserAccount["CURRENT_BUDGET"]) && $arUserAccount["CURRENT_BUDGET"] > 0 && $arUserAccount["LOCKED"] != "Y") {

			//check user auth
			if ($USER->IsAuthorized()) {

				//get inner paysystem id
				$innerPaySystemId = \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();
				$innerPayment = $paymentCollection->getInnerPayment();

				//create new entry in payment collection
				if (empty($innerPayment)) {
					$innerPayment = $paymentCollection->createInnerPayment();
				}

				//amounts limits
				$sumRange = \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::getPriceRange($innerPayment, $innerPaySystemId);

				//check min price restriction
				if (!empty($sumRange["MIN"] <= $arUserAccount["CURRENT_BUDGET"])) {

					//check other restrictions
					$arPaySystemServices = \Bitrix\Sale\PaySystem\Manager::getListWithRestrictions($innerPayment);

					//save to result
					if (!empty($arPaySystemServices[$innerPaySystemId])) {
						$arInnerPayment = array(
							"ID" => $innerPaySystemId,
							"RANGE" => $sumRange,
							"BALANCE" => $arUserAccount["CURRENT_BUDGET"]
						);
					}

				}

			}

		}

		//clear traces
		if ($innerPayment instanceof \Bitrix\Sale\Payment) {
			$innerPayment->delete();
		}

		//write static values
		if (!empty($arInnerPayment)) {
			self::$basketInnerPayment = $arInnerPayment;
		}

		return $arInnerPayment;

	}

	public static function getInnerPaymentExpend()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$spendSum = 0;

		//special
		$order = self::getOrder();

		//get inner payment spend sum
		foreach ($order->getPaymentCollection() as $payment) {
			if ($payment->getPaymentSystemId() == \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()) {
				$spendSum = $payment->getField("SUM");
				break;
			}
		}

		return $spendSum;
	}

	public static function initPayments($order)
	{

		//vars
		$arPayments = array();
		$arPaymentServices = array();

		//check instance
		if (!$order instanceof \Bitrix\Sale\Order) {
			return false;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get payments
		$paymentCollection = $order->getPaymentCollection();

		//processing
		foreach ($paymentCollection as $payment) {

			//check status
			if (!empty(intval($payment->getPaymentSystemId())) && !$payment->isPaid()) {

				//get payment fields
				$arPayments[$payment->getId()] = $payment->getFieldValues();

				//get payment service
				$paySystemService = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());

				//check empty
				if (!empty($paySystemService)) {

					//get fields
					$arPaySysAction = $paySystemService->getFieldsValues();

					//check inner
					if ($paySystemService->getField("NEW_WINDOW") === "N" || $paySystemService->getField("ID") == \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()) {

						//init template
						$initResult = $paySystemService->initiatePay($payment, null, \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);

						//get payment template
						if ($initResult->isSuccess()) {
							$arPaySysAction["BUFFERED_OUTPUT"] = $initResult->getTemplate();
						}

						//save errors
						else {
							$arPaySysAction["ERROR"] = $initResult->getErrorMessages();
						}

					}

					//get fields
					$arPaySysAction["NAME"] = htmlspecialcharsEx($arPaySysAction["NAME"]);
					$arPaySysAction["IS_AFFORD_PDF"] = $paySystemService->isAffordPdf();

					//get logo
					if (!empty($arPaySysAction["LOGOTIP"])) {
						$arPaySysAction["LOGOTIP"] = \CFile::ResizeImageGet($arPaySysAction["LOGOTIP"], array("width" => 100, "height" => 100), BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 100);
					}

					//push to result
					$arPaymentServices[$payment->getPaymentSystemId()] = $arPaySysAction;

				}

			}

		}

		//save payments
		if (!empty($arPayments)) {
			self::$orderPayments = $arPayments;
		}

		//save payment services
		if (!empty($arPaymentServices)) {
			self::$orderPaymentServices = $arPaymentServices;
		}

		return true;
	}

	public static function getPayments()
	{

		//check static
		if (!empty(self::$orderPayments)) {
			return self::$orderPayments;
		}

	}

	public static function getPaymentId()
	{

		//check static
		if (!empty(self::$orderPayments)) {

			//get first payment
			$arPayment = array_shift(self::$orderPayments);

			//check empty
			if (!empty($arPayment)) {
				return $arPayment["ID"];
			}

		}

		return false;
	}

	public static function getPaymentServices()
	{

		//check static
		if (!empty(self::$orderPaymentServices)) {
			return self::$orderPaymentServices;
		}

	}

	public static function clearPayments()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get order
		$order = self::getOrder();

		//get inner payment id
		$innerPaySystemId = \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();

		//get payments
		$paymentCollection = $order->getPaymentCollection();

		//processing
		foreach ($paymentCollection as $payment) {
			if ($payment->getId() != $innerPaySystemId) {
				$payment->delete();
			}
		}

	}

	public static function getUserAccount()
	{

		//check static
		if (!empty(self::$basketUserAccount)) {
			return self::$basketUserAccount;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arUserAccount = array();

		//get account info
		$dbAccount = \CSaleUserAccount::GetList(
			array(),
			array("USER_ID" => self::getUserId(), "CURRENCY" => self::getCurrencyCode()),
			false,
			false,
			array("ID", "USER_ID", "CURRENT_BUDGET", "CURRENCY", "NOTES", "LOCKED", "TIMESTAMP_X", "DATE_LOCKED")
		);

		if ($arAccount = $dbAccount->Fetch()) {

			//check budget
			if (empty($arAccount["CURRENT_BUDGET"])) {
				$arAccount["CURRENT_BUDGET"] = 0;
			}

			//save values
			$arAccount["PRINT_CURRENT_BUDGET"] = SaleFormatCurrency($arAccount["CURRENT_BUDGET"], self::getCurrencyCode());
			$arUserAccount = $arAccount;

		}

		//save values
		if (!empty($arUserAccount)) {
			self::$basketUserAccount = $arUserAccount;
		}

		return $arUserAccount;

	}

	public static function getCurrency($currencyCode = "")
	{

		//check static
		if (!empty(self::$basketCurrency)) {
			return self::$basketCurrency;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arCurrency = array();

		//check currency code from params
		if (empty($currencyCode)) {

			//get from static
			$currencyCode = self::getCurrencyCode();

		}

		//get currency format
		$arCurrency = \CCurrencyLang::GetFormatDescription($currencyCode);

		//check result
		if (!empty($arCurrency)) {

			//modify data
			$arCurrency["CODE"] = $arCurrency["CURRENCY"];
			$arCurrency["SEPARATORS"] = \CCurrencyLang::GetSeparators();

			//save to static
			self::$basketCurrency = $arCurrency;

		}

		return $arCurrency;

	}

	public static function getCurrencyCode()
	{

		//check static
		if (!empty(self::$basketCurrencyCode)) {
			return self::$basketCurrencyCode;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get currency for current site
		$currencyCode = \Bitrix\Sale\Internals\SiteCurrencyTable::getSiteCurrency(self::getSiteId());

		//save values
		if (!empty($currencyCode)) {
			self::$basketCurrencyCode = $currencyCode;
		}

		return $currencyCode;

	}

	public static function getUserLocation()
	{

		//check static
		if (!empty(self::$basketLocationId)) {
			return self::$basketLocationId;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$locationId = false;

		//check location in session
		if (!empty($_SESSION["USER_GEO_POSITION"]["locationID"])) {
			$locationId = $_SESSION["USER_GEO_POSITION"]["locationID"];
		}

		//get location by bitrix api
		else {

			//get ip address
			$ipAddress = \Bitrix\Main\Service\GeoIp\Manager::getRealIp();

			//check
			if (!empty($ipAddress)) {
				//get location id
				if ($geoLocationId = \Bitrix\Sale\Location\GeoIp::getLocationId($ipAddress, LANGUAGE_ID)) {
					$locationId = $geoLocationId;
				}

			}

		}

		//save values
		if (!empty($locationId)) {
			self::$basketLocationId = $locationId;
		}

		return $locationId;

	}

	public static function getLocationById($locationId = 0)
	{

		//check static
		if (!empty(self::$basketLocation[$locationId])) {
			return self::$basketLocation[$locationId];
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$arLocation = array();

		//check location id
		if (!empty($locationId)) {
			$arLocation = \Bitrix\Sale\Location\LocationTable::getById($locationId)->fetch();
			$arLocation["DISPLAY_VALUE"] = \Bitrix\Sale\Location\Admin\LocationHelper::getLocationPathDisplay($arLocation["CODE"]);
		}

		//save values
		if (!empty($arLocation)) {
			self::$basketLocation[$locationId] = $arLocation;
		}

		return $arLocation;

	}

	public static function getLocationByCode($locationCode)
	{

		//check code
		if (!empty($locationCode)) {
			return \CSaleLocation::getLocationIDbyCODE($locationCode);
		}

		return false;

	}

	public static function getUserLocationCode()
	{

		//check static
		if (!empty(self::$basketLocationCode)) {
			return self::$basketLocationCode;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$locationCode = false;

		//get location id
		$locationId = self::getUserLocation();

		//get location code
		if (!empty($rsLocationCode = \CSaleLocation::getLocationCODEbyID($locationId))) {
			$locationCode = $rsLocationCode;
		}

		//save values
		if (!empty($locationCode)) {
			self::$basketLocationCode = $locationCode;
		}

		return $locationCode;

	}

	public static function getUserLocationZip()
	{

		//check static
		if (!empty(self::$basketLocationZip)) {
			return self::$basketLocationZip;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$locationZip = false;

		//get location id
		$locationId = self::getUserLocation();

		//get location zip
		$obZipLocs = \CSaleLocation::GetLocationZIP($locationId);
		if ($arZipLocs = $obZipLocs->Fetch()) {
			if (!empty($arZipLocs["ZIP"])) {
				$locationZip = $arZipLocs["ZIP"];
			}
		}

		//save values
		if (!empty($locationZip)) {
			self::$basketLocationZip = $locationZip;
		}

		return $locationZip;

	}

	public static function getStoreProductsStatus($storeId, $arItems)
	{

		//vars
		$missing = false;
		$part = false;
		$all = false;

		//check params
		if (!empty($arItems) && !empty($storeId)) {

			//scan stores available
			foreach ($arItems as $nextItem) {

				//check stores from product by store id
				if (!empty($nextItem["STORES"][$storeId])) {

					//check current store quantity
					if (empty($nextItem["STORES"][$storeId]["AMOUNT"])) {

						//set part flag
						if ($all === true) {
							$part = true;
						}

						//set missing flag
						else {
							$missing = true;
						}

					} else {

						//check missings
						if (!empty($missing)) {
							//set part flag
							$part = true;
						} else {
							//set "all" flag in current iteration
							$all = true;
						}

					}

				}

				//if not filled
				else {

					//set part flag
					if ($all === true) {
						$part = true;
					}

					//set missing flag
					else {
						$missing = true;
					}

				}

			}

		}

		//get lang labels
		$arLangValues = self::getStoresStatusLangValues();

		//check
		if (!empty($arLangValues)) {

			//check state
			//some products available in current store*
			if ($part === true) {
				return $arLangValues["PART_STORES_AVAILABLE"];
			}

			//all products available in current store*
			elseif ($all === true) {
				return $arLangValues["ALL_STORES_AVAILABLE"];
			}

			//not products available in current store*
			else {
				return $arLangValues["NO_STORES_AVAILABLE"];
			}

		}

	}

	public static function getStoresStatusLangValues()
	{
		return array(
			"PART_STORES_AVAILABLE" => !empty(self::$arSettings["PART_STORES_AVAILABLE"]) ? self::$arSettings["PART_STORES_AVAILABLE"] : getMessage("PART_STORES_AVAILABLE"),
			"ALL_STORES_AVAILABLE" => !empty(self::$arSettings["ALL_STORES_AVAILABLE"]) ? self::$arSettings["ALL_STORES_AVAILABLE"] : getMessage("ALL_STORES_AVAILABLE"),
			"NO_STORES_AVAILABLE" => !empty(self::$arSettings["NO_STORES_AVAILABLE"]) ? self::$arSettings["NO_STORES_AVAILABLE"] : getMessage("NO_STORES_AVAILABLE")
		);
	}

	public static function checkGroupProperties($groupId = false, $personId = false, $arProperties = array())
	{

		//each properties
		foreach ($arProperties as $arProperty) {
			if ($arProperty["PERSON_TYPE_ID"] == $personId && $arProperty["PROPS_GROUP_ID"] == $groupId && empty($arProperty["RELATION"])) {
				return true;
			}
		}

		return false;
	}

	public static function checkPropertiesRelations()
	{

		//check static
		if (!empty(self::$basketProperties["PROPERTIES"])) {

			//get current paysystem id from static
			$paySystemId = self::getFirstPaySystemId();

			//get current delivery id from static
			$deliveryId = self::getFirstDeliveryId();

			//each properties
			foreach (self::$basketProperties["PROPERTIES"] as $propertyId => &$nextProperty) {

				//check relations
				if (!empty($nextProperty["RELATION"])) {

					//check for delivery
					$nextProperty["DELIVERY_RELATION"] = (self::checkRelationProperty($nextProperty, $deliveryId, $paySystemId, "D") ? "Y" : "N");

					//check for paysystem
					$nextProperty["PAYSYSTEM_RELATION"] = (self::checkRelationProperty($nextProperty, $deliveryId, $paySystemId, "P") ? "Y" : "N");

				}

			}

			return self::$basketProperties;

		}

		return false;
	}

	public static function checkRelationProperty($arProperty = array(), $deliveryId = 0, $paysystemId = 0, $entityType = "")
	{

		//check params
		if (empty($arProperty["RELATION"])) {
			return false;
		}

		//check type
		if (empty($entityType) || ($entityType != "D" && $entityType != "P")) {
			return false;
		}

		//vars
		$deliveryBindExist = false;
		$paymentBindExist = false;

		$deliveryFound = false;
		$paymentFound = false;

		//check binding
		foreach ($arProperty["RELATION"] as $nextRelation) {

			//check paysystem binding
			if ($nextRelation["ENTITY_TYPE"] == "P" && $paymentBindExist = true) {
				if (empty($paymentFound) && $nextRelation["ENTITY_ID"] == $paysystemId) {
					$paymentFound = true;
				}
			}

			//check delivery binding
			elseif ($nextRelation["ENTITY_TYPE"] == "D" && $deliveryBindExist = true) {
				if (empty($deliveryFound) && $nextRelation["ENTITY_ID"] == $deliveryId) {
					$deliveryFound = true;
				}
			}

		}

		//cut double
		if ($entityType == "P" && $paymentFound === true && $deliveryFound === true) {
			return false;
		}

		//if not exist payment bind
		if ($entityType == "D" && $paymentBindExist === false) {
			$paymentFound = true;
		}

		//if not exist delivery bind
		elseif ($entityType == "P" && $deliveryBindExist === false) {
			$deliveryFound = true;
		}

		//check result
		if ($paymentFound === true && $deliveryFound === true) {
			return true;
		}

		return false;
	}

	//multi
	public static function checkExtraServicesRights($arExtraServices = array())
	{

		if (!empty($arExtraServices)) {
			foreach ($arExtraServices as $nextService) {
				self::checkExtraServiceRights($nextService);
			}
		}

		return false;

	}

	//one
	public static function checkExtraServiceRights($arExtraService = array())
	{

		if (!empty($arExtraService)) {
			if ($arExtraService["RIGHTS"] == "YNY" || $arExtraService["RIGHTS"] == "YYY") {
				return true;
			}
		}

		return false;

	}

	public static function getSiteId()
	{

		//check static
		if (!empty(self::$basketSiteId)) {
			return self::$basketSiteId;
		}

		//get site id
		else {
			self::$basketSiteId = \Bitrix\Main\Context::getCurrent()->getSite();
		}

		return self::$basketSiteId;

	}

	public static function getBasket($noStatic = false, $fuserId = 0, $siteId = ""): \Bitrix\Sale\Basket
	{

		//check basket from static
		if (!empty(self::$baseBasket) && $noStatic !== true) {
			return self::$baseBasket;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get vars
		$fuserId = !empty($fuserId) ? $fuserId : self::getFuserId();
		$siteId = !empty($siteId) ? $siteId : self::getSiteId();

		//get basket
		$basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, $siteId);

		//save static
		if (!empty($basket) && $noStatic !== true) {
			self::$baseBasket = $basket;
		}

		return $basket;

	}

	public static function getBasketClone(): \Bitrix\Sale\Basket
	{

		$manager = \Bitrix\Sale\Basket::create(self::getSiteId());
		$manager->setFUserId(self::getFuserId());
		$manager->isLoadForFUserId = true;

		foreach (self::getBasket() as $basketItem) {
			$manager->addItem($basketItem);
		}

		return $manager;
	}

	public static function checkMinOrderAmount()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get order
		$order = self::getOrder();

		//check instance
		if ($order instanceof \Bitrix\Sale\Order) {

			//check min order price settings
			if (!empty(self::$arSettings["MIN_SUM_TO_PAYMENT"])) {

				//check order sum
				if ($order->getPrice() < self::$arSettings["MIN_SUM_TO_PAYMENT"]) {
					return false;
				}

			}

		}

		return true;
	}

	public static function isEmptyBasket()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get basket
		$basket = self::getBasket(true, false)->getOrderableItems();

		//check instance
		if ($basket instanceof \Bitrix\Sale\Basket) {

			//get items
			$basketItems = $basket->getBasketItems();

			//check empty
			if (!empty($basketItems)) {
				return true;
			}

		}

		return false;
	}

	public static function deleteItem($basketId, $siteId = false)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get basket
		$basket = self::getBasket(true, false, $siteId);

		//check instance
		if ($basket instanceof \Bitrix\Sale\Basket) {

			//get basket item
			$basketItem = $basket->getItemById($basketId);

			//check vars
			if ($basketItem instanceof \Bitrix\Sale\BasketItem) {

				//delete item from basket
				$deleteResult = $basketItem->delete();

				//check state
				if ($deleteResult->isSuccess()) {

					//basket save state
					$saveResult = $basket->save();

					//check basket save
					if ($saveResult->isSuccess()) {
						return true;
					}

					//error
					else {

						//get errors
						$errors = $saveResult->getErrors();

						//write
						if (!empty($errors)) {
							foreach ($errors as $error) {
								self::setError($error->getMessage());
							}
						}
					}

				}

			}

		}

		return false;

	}

	public static function updateQuantity($basketId, $quantity, $siteId)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//check transmitted data
		if (!empty($basketId) || !empty($quantity) && !empty($siteId)) {

			//get basket
			$basket = self::getBasket(true, false, $siteId);

			//check instance
			if ($basket instanceof \Bitrix\Sale\Basket) {

				//get basket item
				$basketItem = $basket->getItemById($basketId);

				//check vars
				if ($basketItem instanceof \Bitrix\Sale\BasketItem) {

					//set quantity
					$basketItem->setField("QUANTITY", $quantity);
					$basket->save();

					return true;

				}

			}

		}

		return false;
	}

	public static function getBasketVars()
	{

		//get vars
		$arBasketVars = array(
			"DELIVERY_LOCATION_BCODE" => self::getUserLocationCode(),
			"DELIVERY_LOCATION_ZIP" => self::getUserLocationZip(),
			"DELIVERY_LOCATION" => self::getUserLocation(),
			"PERSON_TYPE_ID" => self::getPersonTypeId(),
			"PAY_SYSTEM_ID" => self::getFirstPaySystemId(),
			"DELIVERY_ID" => self::getFirstDeliveryId(),
			"ORDER_PROP" => self::getOrderProperties(),
		);

		return $arBasketVars;

	}

	public static function getFuserId()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//check static
		if (!empty(self::$basketFuserId)) {
			return self::$basketFuserId;
		}

		//get fuserId
		else {
			self::$basketFuserId = \Bitrix\Sale\Fuser::getId();
		}

		return self::$basketFuserId;

	}

	public static function getPersonTypes()
	{

		//check static
		if (empty(self::$basketPersonTypes)) {

			//check modules
			if (!Loader::includeModule("sale")) {
				return false;
			}

			//get all person types & write static
			self::$basketPersonTypes = \Bitrix\Sale\PersonType::load(self::getSiteId());

			//highlight selected
			if (!empty(self::$basketPersonTypes)) {

				//get first person type index
				$firstIndex = array_key_first(self::$basketPersonTypes);

				//highlight selected
				self::$basketPersonTypes[$firstIndex]["SELECTED"] = "Y";

			}

			//execute
			if (self::$staticEvents === true) {
				self::executeEvent("OnSaleComponentOrderOneStepPersonType");
			}

		}

		return self::$basketPersonTypes;

	}

	public static function getPersonTypeId(): int
	{

		//check static
		if (empty(self::$basketPersonTypeId)) {

			//get keys from all personTypes
			$personTypesKeys = array_keys(self::getPersonTypes());

			//write static
			self::$basketPersonTypeId = $personTypesKeys[0];

		}

		return self::$basketPersonTypeId;

	}

	public static function getBasketWeight(): float
	{
		return self::$baseBasket->getWeight();
	}

	public static function getBasketSum(): float
	{
		return self::$baseBasket->getPrice();
	}

	public static function getUserId(): int
	{

		//check static
		if (empty(self::$basketUserId)) {

			//globals
			global $USER;

			//check modules
			if (!Loader::includeModule("sale")) {
				return false;
			}

			//get id
			self::$basketUserId = intval($USER->GetID());

			//check user id
			if (empty(self::$basketUserId)) {

				//push
				self::$basketUserId = \CSaleUser::GetAnonymousUserID();

			}

		}

		return self::$basketUserId;
	}

	public static function getOrder(): \Bitrix\Sale\Order
	{

		//check static
		if (isset(self::$order)) {
			return self::$order;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		$registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
		$orderClass = $registry->getOrderClassName();

		self::$order = $orderClass::create(self::getSiteId(), self::getUserId());
		return self::$order;
	}

	public static function getOrderById(int $orderId): ?\Bitrix\Sale\Order
	{

		//check transmitted
		if (!empty($orderId)) {

			//check modules
			if (!Loader::includeModule("sale")) {
				return null;
			}

			//get order
			$order = \Bitrix\Sale\Order::load($orderId);

			//check instance
			if ($order instanceof \Bitrix\Sale\Order) {
				return $order;
			}

		}

		return null;
	}

	public static function getOrderSum(): float
	{
		return self::$orderSum;
	}

	public static function getFirstDelivery(): ?array
	{
		return self::$basketFirstDelivery;
	}

	public static function getFirstDeliveryId(): int
	{
		return self::$basketFirstDeliveryId;
	}

	public static function getFirstPaySystem(): array
	{
		return self::$basketFirstPaySystem;
	}

	public static function getFirstPaySystemId(): int
	{
		return self::$basketFirstPaySystemId;
	}

	public static function getParams(): array
	{

		//check static
		if (!empty(self::$arSettings)) {
			return self::$arSettings;
		}

		return [];
	}

	public static function getErrors(): array
	{

		//check static
		if (!empty(self::$arErrors)) {
			return self::$arErrors;
		}

		return [];
	}

	public static function setProperties($arProperties = array())
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//vars
		$errors = array();

		//check empty
		if (!empty($arProperties)) {

			//get order
			$order = self::getOrder();

			//get collection
			$propertyCollection = $order->getPropertyCollection();

			//each fields
			foreach ($arProperties as $fid => $propertyValue) {

				//get integer
				$fid = intval($fid);

				//get property
				$rsProperty = $propertyCollection->getItemByOrderPropertyId($fid);
				$arProperty = $rsProperty->getProperty();

				//check instance
				if ($rsProperty instanceof \Bitrix\Sale\PropertyValue) {

					//ignore location
					if (empty($arProperty["IS_LOCATION"]) || $arProperty["IS_LOCATION"] != "Y") {

						//check value
						$res = $rsProperty->checkValue($fid, $propertyValue);
						if ($res->isSuccess()) {
							//set value
							$rsProperty->setValue($propertyValue);
						}

						//get errors
						else {
							$propertyError = $res->getErrors();
							if (!empty($propertyError)) {
								foreach ($propertyError as $nextError) {
									if ($nextError instanceof \Bitrix\Sale\ResultError) {
										$errors[$fid][] = $nextError->getMessage();
									}
								}
							}
						}

					}

				} else {
					self::setError("property id: " . $fid . " - instance error");
					return false;
				}

			}

		}

		//check errors
		if (!empty($errors)) {
			self::setError($errors);
			return false;
		}

		return true;

	}

	public static function setExtraServices($arExtraServices = array())
	{
		//check empty
		if (!empty($arExtraServices) && is_array($arExtraServices)) {
			self::$basketExtraServices = $arExtraServices;
		}

		return false;

	}

	public static function setStoreId($storeId = 0)
	{

		//check empty
		if (!empty($storeId)) {
			self::$basketStoreId = intval($storeId);
		}

		return false;

	}

	public static function setCoupon($coupon = "")
	{

		//check
		if (!empty($coupon)) {

			//check modules
			if (!Loader::includeModule("sale")) {
				return false;
			}

			if (\Bitrix\Sale\DiscountCouponsManager::add($coupon)) {
				return true;
			}

		}

		return false;

	}

	public static function setOrderComment($comment)
	{

		//check
		if (!empty($comment)) {

			//check modules
			if (!Loader::includeModule("sale")) {
				return false;
			}

			//get order
			$order = self::getOrder();

			//set comment
			$order->setField("USER_DESCRIPTION", $comment);

		}

		return true;
	}

	public static function setInnerPayment()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get order
		$order = self::getOrder();

		//pay from inner
		$paymentResult = \Bitrix\Sale\Compatible\OrderCompatibility::payFromBudget($order, false, true);

		//check success
		if (!$paymentResult->isSuccess()) {

			//get errors
			$errors = $paymentResult->getErrors();

			//write
			if (!empty($errors)) {
				foreach ($errors as $error) {
					self::setError($error->getMessage());
				}
			}

			return false;

		}

		//update main paysystem sum (main payment sum - innerPayment sum)

		//get sum to spend
		$innerExpendSum = self::getInnerPaymentExpend();

		//check expend inner payment
		if (!empty($innerExpendSum)) {

			//get main payment for update sum
			$mainPayment = self::getMainPayment();

			//check instance
			if ($mainPayment instanceof \Bitrix\Sale\Payment) {

				//get current payment sum
				$mainPaymentSum = $mainPayment->getField("SUM");

				//check sum
				if (!empty($mainPaymentSum)) {

					//set sum
					$mainPayment->setField("SUM", ($mainPaymentSum - $innerExpendSum));

				}

			}

		}

		return true;
	}

	public static function setFields($siteId, $deliveryId = 0, $paysystemId = 0, $personTypeId = 0, $locationId = 0)
	{

		//siteId
		if (!empty($siteId)) {
			self::setSiteId($siteId);
		}

		//deliveryId
		if (!empty($deliveryId)) {
			self::setDeliveryId($deliveryId);
		}

		//paysystemId
		if (!empty($paysystemId)) {
			self::setPaysystemId($paysystemId);
		}

		//personTypeId
		if (!empty($personTypeId)) {
			self::setPersonTypeId($personTypeId);
		}

		//locationId
		if (!empty($locationId)) {
			self::setLocationId($locationId);
		}

	}

	public static function setBasket(object $basket = null)
	{

		//check empty
		if (!empty($basket)) {
			self::$baseBasket = $basket;
		}

		return false;

	}

	public static function setUserId(int $userId = 0)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//check
		if (!empty($userId)) {

			//get order
			$order = self::getOrder();

			//check instance
			if ($order instanceof \Bitrix\Sale\Order) {

				//set field for current order
				$order->setFieldNoDemand("USER_ID", $userId);

			}

			//set static
			self::$basketUserId = $userId;

			//
			return true;

		}

		return false;
	}

	public static function setOrder(object $order = null)
	{

		//check empty
		if (!empty($order)) {
			self::$order = $order;
		}

		return false;

	}

	public static function setSiteId(string $siteId = "")
	{

		//check empty
		if (!empty($siteId)) {
			self::$basketSiteId = $siteId;
		}

		return false;

	}

	public static function setDeliveryId(int $deliveryId = 0)
	{

		//check empty
		if (!empty($deliveryId)) {
			self::$basketFirstDeliveryId = $deliveryId;
		}

		return false;

	}

	public static function setPaysystemId(int $paySystemId = 0)
	{

		//check empty
		if (!empty($paySystemId)) {
			self::$basketFirstPaySystemId = $paySystemId;
		}

		return false;

	}

	public static function setPersonTypeId(int $personTypeId = 0)
	{

		//check empty
		if (!empty($personTypeId)) {
			self::$basketPersonTypeId = $personTypeId;
		}

		//highlight selected
		if (!empty(self::$basketPersonTypes)) {

			//each person types
			foreach (self::$basketPersonTypes as &$nextPersonType) {

				//set selected
				if ($nextPersonType == $personTypeId) {
					$nextPersonType["SELECTED"] = $nextPersonType;
				}

				//clear
				elseif (!empty($nextPersonType["SELECTED"])) {
					unset($nextPersonType["SELECTED"]);
				}

			}

		}

		return false;

	}

	public static function setLocationId(int $locationId = 0)
	{

		//check empty
		if (!empty($locationId)) {
			self::$basketLocationId = $locationId;
		}

		return false;

	}

	public static function setParams($arParams = array())
	{

		//check empty
		if (!empty($arParams)) {
			self::$arSettings = $arParams;
		}

		return false;

	}

	public static function setError($error = "")
	{

		//check empty
		if (!empty($error)) {
			//array
			if (is_array($error)) {
				foreach ($error as $nextError) {
					self::$arErrors[] = $nextError;
				}
			}
			//string
			else {
				self::$arErrors[] = $error;
			}
		}

	}

	public static function autoRegisterUser()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//globals
		global $USER;

		//check auth
		if ($USER->IsAuthorized()) {
			return true;
		}

		//get order
		$order = self::getOrder();

		//other vars
		$loginIndex = 0;

		//check instance
		if ($order instanceof \Bitrix\Sale\Order) {

			//get collection
			$propertyCollection = $order->getPropertyCollection();
			foreach ($propertyCollection as $property) {

				$propertyFields = $property->getProperty();
				if ($propertyFields["IS_EMAIL"] == "Y" && $propertyFields["PERSON_TYPE_ID"] == $order->getPersonTypeId()) {
					$propEmail = $property;
				} else if ($propertyFields["IS_PHONE"] == "Y" && $propertyFields["PERSON_TYPE_ID"] == $order->getPersonTypeId()) {
					$propPhone = $property;
				}

			}

			//check instance
			if ($propEmail instanceof \Bitrix\Sale\PropertyValue) {

				//get email value
				$email = $propEmail->getValue();

				//get phone value
				$phone = $propPhone instanceof \Bitrix\Sale\PropertyValue ? $propPhone->getValue() : "";

				//check required & filling
				if (!empty($email)) {

					//set new login
					$login = self::getLoginFromEmail($email);

					//check login
					if (!empty($login)) {

						//user register groups
						$groupIds = array();

						//check available login (email)
						$dbUserLogin = $USER->GetByLogin($login);

						//check
						while ($dbUserLogin->Fetch()) {

							//login index
							$loginIndex++;

							//generate new login
							$newLogin = $login . $loginIndex;
							$dbUserLogin = $USER->GetByLogin($newLogin);

						}

						//save new login value
						if (!empty($newLogin)) {
							$login = $newLogin;
						}

						//get user default groups
						$defaultGroups = \Bitrix\Main\Config\Option::get("main", "new_user_registration_def_group", "");

						//check groups
						if (!empty($defaultGroups)) {
							$groupIds = explode(",", $defaultGroups);
						}

						//generate password
						$newPassword = randString(8);

						//register user
						$rsRegister = new \CUser;
						$userId = $rsRegister->Add(
							array(
								"CONFIRM_PASSWORD" => $newPassword,
								"PASSWORD" => $newPassword,
								"LID" => self::getSiteId(),
								"GROUP_ID" => $groupIds,
								"PHONE_NUMBER" => $phone,
								"LAST_NAME" => "",
								"EMAIL" => $email,
								"LOGIN" => $login,
								"ACTIVE" => "Y",
								"NAME" => ""
							)
						);

						//check success
						if (!empty($userId)) {

							//set groups
							$USER->AppendUserGroup($userId, $groupIds);

							//send user info
							//C1_NEW_USER_REGISTRATION_MESSAGE
							$USER->SendUserInfo($userId, self::getSiteId(), \Bitrix\Main\Localization\Loc::GetMessage("C1_NEW_USER_REGISTRATION_MESSAGE"), true);

							//auth
							$USER->Authorize($userId);

							//set user to order
							self::setUserId($userId);

							//success
							return true;

						}

						//fail registration
						else {

							//get bitrix user errors
							$errorMessages = $rsRegister->LAST_ERROR;

							//check errors
							if (!empty($errorMessages)) {

								//clear dublicate bitrix errors
								$arErrors = array_unique(explode("<br>", $errorMessages));

								//write errors
								self::setError($arErrors);
							}

						}
					} else {
						//C1_NEW_USER_REGISTRATION_LOGIN_PARSE_ERROR
						self::setError(\Bitrix\Main\Localization\Loc::GetMessage("C1_NEW_USER_REGISTRATION_LOGIN_PARSE_ERROR"));
					}

				}

				//empty email
				else {
					//check required
					if (!$propEmail->isRequired()) {
						return true;
					}
				}

			}

		}

		return false;
	}

	public static function createUserProfile(array $arOrder, array $arProperties)
	{

		//check transmitted data
		if (empty($arProperties) || empty($arOrder["PROPERTIES"])) {
			return true;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//globals
		global $USER;

		//check auth
		if (!$USER->IsAuthorized()) {
			return true;
		}

		//get order
		$order = self::getOrder();

		//get userId
		$userId = self::getUserId();

		//get siteId
		$siteId = self::getSiteId();

		//get personTypeId
		$personTypeId = self::getPersonTypeId();

		//other vars
		$userProfileId = 0;
		$userProfileName = "";

		//check exist profiles (old api, d7 not available)
		$rsProfiles = \CSaleOrderUserProps::GetList(array("DATE_UPDATE" => "DESC"), array("PERSON_TYPE_ID" => $personTypeId, "USER_ID" => $userId), false, array("nTopCount" => 1));

		//if exist
		if ($arProfiles = $rsProfiles->Fetch()) {
			$userProfileId = $arProfiles["ID"];
		}

		//get profile name
		foreach ($arOrder["PROPERTIES"]["PROPERTIES"] as $arNextProperty) {

			//check profile name
			if ($arNextProperty["IS_PROFILE_NAME"] == "Y" && $arNextProperty["PERSON_TYPE_ID"] == $personTypeId) {

				//check profile name property
				if (!empty($arProperties[$arNextProperty["ID"]])) {
					$userProfileName = $arProperties[$arNextProperty["ID"]];
				}

			}

		}

		\CSaleOrderUserProps::DoSaveUserProfile(
			$userId,
			$userProfileId,
			$userProfileName,
			$personTypeId,
			$arProperties,
			$arErrors
		);

		//check errors
		if (!empty($arErrors)) {
			foreach ($arErrors as $error) {
				self::setError($error["TEXT"]);
			}
		}

		return true;
	}

	public static function updateUserInfo(array $arOrder, array $arProperties)
	{

		//check transmitted data
		if (empty($arProperties) || empty($arOrder["PROPERTIES"])) {
			return true;
		}

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//globals
		global $USER;

		//check auth
		if (!$USER->IsAuthorized()) {
			return true;
		}

		//get order
		$order = self::getOrder();

		//get userId
		$userId = self::getUserId();

		//get personTypeId
		$personTypeId = self::getPersonTypeId();

		//other arrays
		$arUserFields = array();
		$arNameFields = array("LAST_NAME", "NAME", "SECOND_NAME");

		//get user fields
		foreach ($arOrder["PROPERTIES"]["PROPERTIES"] as $arNextProperty) {

			//check person type
			if ($arNextProperty["PERSON_TYPE_ID"] == $personTypeId) {

				//check user name
				if ($arNextProperty["IS_PROFILE_NAME"] == "Y" && !empty($arProperties[$arNextProperty["ID"]])) {
					$userName = $arProperties[$arNextProperty["ID"]];
				}

				//check user mobile
				elseif ($arNextProperty["IS_PHONE"] == "Y" && !empty($arProperties[$arNextProperty["ID"]])) {
					$arUserFields["PERSONAL_MOBILE"] = $arProperties[$arNextProperty["ID"]];
				}

				//check user address
				elseif ($arNextProperty["IS_ADDRESS"] == "Y" && !empty($arProperties[$arNextProperty["ID"]])) {
					$arUserFields["PERSONAL_STREET"] = $arProperties[$arNextProperty["ID"]];
				}

				//check user zip
				elseif ($arNextProperty["IS_ZIP"] == "Y" && !empty($arProperties[$arNextProperty["ID"]])) {
					$arUserFields["PERSONAL_ZIP"] = $arProperties[$arNextProperty["ID"]];
				}

			}

		}

		//check user name field
		if (!empty($userName)) {

			//explode user name
			$userNameEx = explode(" ", $userName);

			//combine names
			foreach ($arNameFields as $nameIndex => $nameField) {
				if (!empty($userNameEx[$nameIndex])) {
					$arUserFields[$nameField] = ucfirst($userNameEx[$nameIndex]);
				}
			}

		}

		//update user info
		$user = new \CUser;
		$user->Update($userId, $arUserFields);

		if (!empty($user->LAST_ERROR)) {
			self::setError($user->LAST_ERROR);
		}

		return true;
	}

	public static function sendOrderSms()
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get order
		$order = self::getOrder();
		$orderId = $order->getId();

		//check order id
		if (!empty($orderId)) {

			//check order instance
			if ($order instanceof \Bitrix\Sale\Order) {

				//get collection
				$propertyCollection = $order->getPropertyCollection();

				//get phone property
				$propPhone = $propertyCollection->getPhone();

				//check instance
				if ($propPhone instanceof \Bitrix\Sale\PropertyValue) {

					//get phone value
					$phone = $propPhone->getValue();

					//check phone
					if (!empty($phone)) {

						//normalize number
						$phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);

						//get order number
						$orderNumber = $order->getField("ACCOUNT_NUMBER");
						;

						//get order vars
						$orderPrice = $order->getPrice();
						$orderCurrency = $order->getCurrency();
						$orderDeliveryPrice = $order->getDeliveryPrice();

						//prepate to send
						$sms = new \Bitrix\Main\Sms\Event("SALE_NEW_ORDER_SMS", array(
							"ORDER_ID" => $orderId,
							"USER_PHONE" => $phone,
							"ORDER_PRICE" => $orderPrice,
							"ORDER_NUMBER" => $orderNumber,
							"ORDER_CURRENCY" => $orderCurrency,
							"ORDER_DELIVERY_PRICE" => $orderDeliveryPrice,
						)
						);

						//push message
						$smsResult = $sms->send(true);

						//check result
						if (!$smsResult->isSuccess()) {

							//get error messages
							$errors = $smsResult->getErrorMessages();

							//write
							self::setError($errors);

						}

					}

				}

			}

		}

		return true;
	}

	public static function sendOrderConfirmSms($orderId = false)
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//get order
		if (!empty($orderId)) {

			//get order by id
			$order = \Bitrix\Sale\Order::load($orderId);

		}

		//get from static
		else {
			$order = self::getOrder();
			$orderId = $order->getId();
		}

		//check order id
		if (!empty($orderId)) {

			//check order instance
			if ($order instanceof \Bitrix\Sale\Order) {

				//get collection
				$propertyCollection = $order->getPropertyCollection();

				//get phone property
				$propPhone = $propertyCollection->getPhone();

				//check instance
				if ($propPhone instanceof \Bitrix\Sale\PropertyValue) {

					//get phone value
					$phone = $propPhone->getValue();

					//check phone
					if (!empty($phone)) {

						//normalize number
						$phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);

						//get order number
						$orderNumber = $order->getField("ACCOUNT_NUMBER");

						//get order vars
						$orderPrice = $order->getPrice();
						$orderCurrency = $order->getCurrency();
						$orderDeliveryPrice = $order->getDeliveryPrice();

						//get code
						$confirmCode = self::getConfirmBySmsCode($orderId, true);

						//prepate to send
						$sms = new \Bitrix\Main\Sms\Event("SALE_NEW_ORDER_SMS_CONFIRM", array(
							"ORDER_ID" => $orderId,
							"USER_PHONE" => $phone,
							"ORDER_PRICE" => $orderPrice,
							"ORDER_NUMBER" => $orderNumber,
							"ORDER_CURRENCY" => $orderCurrency,
							"ORDER_CONFIRM_CODE" => $confirmCode,
							"ORDER_DELIVERY_PRICE" => $orderDeliveryPrice,
						)
						);

						//push message
						$smsResult = $sms->send(true);

						//check result
						if (!$smsResult->isSuccess()) {

							//get error messages
							$errors = $smsResult->getErrorMessages();

							//write
							self::setError($errors);

						}

						//success
						else {
							return true;
						}

					}

				}

			}

		}

		return false;
	}

	public static function processingFiles($arFiles = array())
	{

		//vars
		$arProperties = array();

		//compilation
		if (!empty($arFiles["properties"])) {
			foreach ($arFiles["properties"] as $paramName => $paramValue) {
				if (!empty($paramValue)) {
					foreach ($paramValue as $propId => $propValue) {
						//multi
						if (is_array($propValue)) {
							foreach ($propValue as $index => $nextValue) {
								$arProperties[$propId][$index][$paramName] = $nextValue;
							}
						}
						//one
						else {
							$arProperties[$propId][$propId][$paramName] = $propValue;
						}
					}
				}
			}
		}

		return $arProperties;

	}

	public static function clearBasket($siteId = "")
	{

		//check modules
		if (!Loader::includeModule("sale")) {
			return false;
		}

		//special
		$basket = self::getBasket();

		$order = self::getOrder();
		$order->appendBasket($basket);

		//get items
		$basketItems = $basket->getBasketItems();

		//check empty
		if (!empty($basketItems)) {

			//remove items
			foreach ($basketItems as $basketItem) {
				$basketItem->delete();
			}

			//apply changes
			$basket->save();

		}

		return true;

	}

	public static function formatPrice($price)
	{

		//check empty
		if (!empty($price)) {

			//check modules
			if (!Loader::includeModule("sale")) {
				return false;
			}

			//get currency
			$currencyCode = self::getCurrencyCode();

			//format price
			$price = \CCurrencyLang::CurrencyFormat($price, $currencyCode);

		}

		return $price;
	}

	public static function clearParams($arParams = array())
	{

		//clear double ~ in arParams
		if (!empty($arParams)) {
			return array_filter($arParams, function ($index) {
				return !strstr($index, "~");
			}, ARRAY_FILTER_USE_KEY);
		}

		return $arParams;
	}

	public static function checkUserRegisterByPhone()
	{

		//globals
		global $USER;

		//check auth
		if (!$USER->IsAuthorized()) {

			//get order
			$order = self::getOrder();

			//check instance
			if ($order instanceof \Bitrix\Sale\Order) {

				//get collection
				$propertyCollection = $order->getPropertyCollection();

				//get phone property
				$propPhone = $propertyCollection->getPhone();

				//check instance
				if ($propPhone instanceof \Bitrix\Sale\PropertyValue) {

					//get phone value
					$phone = $propPhone->getValue();

					//check phone
					if (!empty($phone)) {

						//normalize
						$phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);

						//get user by phone
						$rsAuthTable = \Bitrix\Main\UserPhoneAuthTable::getList(
							array(
								"filter" => array(
									"PHONE_NUMBER" => $phone
								),
								"select" => array(
									"USER_ID",
									"PHONE_NUMBER",
									"USER.ID",
									"USER.ACTIVE"
								)
							)
						);

						//get result
						$authTableResult = $rsAuthTable->fetchObject();

						//check result
						if (!empty($authTableResult)) {
							return self::setUserId($authTableResult->getUserId());
						}

					}

				}

			}

		}

		return false;
	}

	public static function getConfirmBySmsCode(int $orderId, $new = false)
	{

		//check code by session
		if ($new === true || empty($_SESSION["ORDER_CONFIRM_SMS_CODE"][$orderId])) {
			$_SESSION["ORDER_CONFIRM_SMS_CODE"][$orderId] = rand(1000, 9999);
			$_SESSION["ORDER_CONFIRM_SMS_CODE"]["LAST_TIME"] = time();
		}

		return $_SESSION["ORDER_CONFIRM_SMS_CODE"][$orderId];
	}

	public static function getLastTimeSendSmsCode()
	{

		//check last time
		if (!empty($_SESSION["ORDER_CONFIRM_SMS_CODE"]["LAST_TIME"])) {
			return $_SESSION["ORDER_CONFIRM_SMS_CODE"]["LAST_TIME"];
		}

		return false;
	}

	public static function getLoginFromEmail($email)
	{

		//check data
		if (!empty($email)) {

			//explode
			$parts = explode("@", $email);

			//check rs
			if (count($parts) > 1) {
				//length control
				return str_pad($parts[0], 3, "_");
			}

		}

		return false;
	}

	public static function executeEvent($eventName = "", &$arResult = array(), &$arUserResult = array(), &$arParams = array())
	{

		//check enable events
		if (self::$enableEvents === true) {

			//execute events
			if (!empty($eventName)) {
				foreach (GetModuleEvents("sale", $eventName, true) as $arEvent) {
					ExecuteModuleEventEx($arEvent, array(&$arResult, &$arUserResult, &$arParams));
				}
			}

		}

	}

	public static function enableStaticEvents()
	{
		self::$staticEvents = true;
	}

	public static function disableStaticEvents()
	{
		self::$staticEvents = false;
	}

	public static function enableEvents()
	{
		self::$enableEvents = true;
	}

	public static function disableEvents()
	{
		self::$enableEvents = false;
	}

	public static function pushResult($arPush = array())
	{

		//check args
		if (!empty($arPush)) {

			//clear buffer
			ob_clean();

			//print json
			echo \Bitrix\Main\Web\Json::encode($arPush);

		}

	}
}
