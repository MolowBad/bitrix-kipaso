<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule("dw.deluxe")) {
	return false;
}

use \DigitalWeb\Basket as DwBasket;

$arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"] = !empty($arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"]) ? $arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"] : array();
$arParams["ORDER_CONFIRM_BY_SMS_CODE"] = !empty($arParams["ORDER_CONFIRM_BY_SMS_CODE"]) ? $arParams["ORDER_CONFIRM_BY_SMS_CODE"] : "N";
$arParams["BASKET_PICTURE_WIDTH"] = !empty($arParams["BASKET_PICTURE_WIDTH"]) ? $arParams["BASKET_PICTURE_WIDTH"] : 220;
$arParams["BASKET_PICTURE_HEIGHT"] = !empty($arParams["BASKET_PICTURE_HEIGHT"]) ? $arParams["BASKET_PICTURE_HEIGHT"] : 200;
$arParams["CALCULATE_ORDER_DATA"] = !empty($arParams["CALCULATE_ORDER_DATA"]) ? $arParams["CALCULATE_ORDER_DATA"] : "Y";
$arParams["LAZY_LOAD_PICTURES"] = !empty($arParams["LAZY_LOAD_PICTURES"]) ? $arParams["LAZY_LOAD_PICTURES"] : "N";
$arParams["MIN_SUM_TO_PAYMENT"] = !empty($arParams["MIN_SUM_TO_PAYMENT"]) ? $arParams["MIN_SUM_TO_PAYMENT"] : 0;
$arParams["PATH_TO_PAYMENT"] = !empty($arParams["PATH_TO_PAYMENT"]) ? $arParams["PATH_TO_PAYMENT"] : "payment.php";
$arParams["DISABLE_FAST_ORDER"] = !empty($arParams["DISABLE_FAST_ORDER"]) ? $arParams["DISABLE_FAST_ORDER"] : "N";
$arParams["SEND_SMS_MESSAGE"] = !empty($arParams["SEND_SMS_MESSAGE"]) ? $arParams["SEND_SMS_MESSAGE"] : "N";
$arParams["MASKED_FORMAT"] = !empty($arParams["MASKED_FORMAT"]) ? $arParams["MASKED_FORMAT"] : "";
$arParams["USE_MASKED"] = !empty($arParams["USE_MASKED"]) ? $arParams["USE_MASKED"] : "N";

$arParams["PART_STORES_AVAILABLE"] = !empty($arParams["PART_STORES_AVAILABLE"]) ? $arParams["PART_STORES_AVAILABLE"] : getMessage("PART_STORES_AVAILABLE");
$arParams["ALL_STORES_AVAILABLE"] = !empty($arParams["ALL_STORES_AVAILABLE"]) ? $arParams["ALL_STORES_AVAILABLE"] : getMessage("ALL_STORES_AVAILABLE");
$arParams["NO_STORES_AVAILABLE"] = !empty($arParams["NO_STORES_AVAILABLE"]) ? $arParams["NO_STORES_AVAILABLE"] : getMessage("NO_STORES_AVAILABLE");

$application = \Bitrix\Main\Application::getInstance();

$context = $application->getContext();
$request = $context->getRequest();

$orderId = intval($request->getQuery("orderId"));

if (empty($orderId)) {

	DwBasket::setParams($arParams);

	$basket = DwBasket::getInstance();

	if (!empty($_REQUEST["deliveryId"])) {
		$basket->setDeliveryId(intval($_REQUEST["deliveryId"]));
	}

	$basket->enableStaticEvents();

	$arBasketItems = $basket->getBasketItems();

	if (!empty($arBasketItems)) {

		$arProducts = $basket->addProductsInfo($arBasketItems);

		if ($arParams["CALCULATE_ORDER_DATA"] === "Y") {
			$arOrder = $basket->getOrderInfo();
		}

		else{
			$arOrder = [];
		}

		$arProducts = $basket->addProductPrices($arProducts);
		$orderSum = $basket->getOrderSum();

		$basketWeight = $basket->getBasketWeight();
		$basketSum = $basket->getBasketSum();

		$arMeasures = $basket->getMeasures();
		$arCurrency = $basket->getCurrency();
		$arPersonTypes = $basket->getPersonTypes();
		$arProperties = $basket->getOrderProperties();

		$discountListFull = $basket->getDiscountListFull();
		$appliedDiscounts = $basket->getAppliedDiscounts();

		$arUserAccount = $basket->getUserAccount();
		$arCurrentPaysystem = $basket->getFirstPaySystem();
		$arCurrentDelivery = $basket->getFirstDelivery();
		$arStores = $basket->getStores($arProducts);
		$isMinOrderAmount = $basket->checkMinOrderAmount();

		$arResult = array(
			"APPLIED_DISCOUNT_LIST" => $appliedDiscounts,
			"IS_MIN_ORDER_AMOUNT" => $isMinOrderAmount,
			"FULL_DISCOUNT_LIST" => $discountListFull,
			"PROPERTY_GROUPS" => $arProperties["GROUPS"],
			"PROPERTIES" => $arProperties["PROPERTIES"],
			"FIRST_PAYSYSTEM" => $arCurrentPaysystem,
			"FIRST_DELIVERY" => $arCurrentDelivery,
			"USER_ACCOUNT" => $arUserAccount,
			"PERSON_TYPES" => $arPersonTypes,
			"CURRENCY" => $arCurrency,
			"BASKET_SUM" => $basketSum,
			"MEASURES" => $arMeasures,
			"WEIGHT" => $basketWeight,
			"ORDER_SUM" => $orderSum,
			"ITEMS" => $arProducts,
			"STORES" => $arStores,
			"ORDER" => $arOrder
		);

	}

}

else {

	$order = DwBasket::getOrderById($orderId);

	$arResult["CONFIRM_ORDER"] = "Y";

	if (!empty($order)) {

		$arResult["ORDER"] = $order->getFieldValues();
		$arResult["ORDER"]["STATUS"] = $order->getField("STATUS_ID");

		$arResult["ORDER"]["ALLOW_PAY"] = $order->isAllowPay();

		if (!$order->isPaid() && $order->isAllowPay()) {

			if (DwBasket::initPayments($order)) {

				$arResult["ORDER"]["PAYMENTS"] = DwBasket::getPayments();
				$arResult["ORDER"]["PAYMENT_SERVICES"] = DwBasket::getPaymentServices();
				$arResult["ORDER"]["PAYMENT_ID"] = DwBasket::getPaymentId();

				if (empty($arResult["ORDER"]["ALLOW_PAY"]) || !empty($arResult["ORDER"]["PAYED"]) && $arResult["ORDER"]["PAYED"] == "Y") {
					$arParams["ORDER_CONFIRM_BY_SMS_CODE"] = "N";
				}

				if (!empty($arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"]) && !empty($arResult["ORDER"]["PAY_SYSTEM_ID"]) && $arParams["ORDER_CONFIRM_BY_SMS_CODE"] == "Y") {

					$arParams["ORDER_CONFIRM_BY_SMS_CODE"] = "N";

					foreach ($arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"] as $nextPaysystemId) {
						if ($arResult["ORDER"]["PAY_SYSTEM_ID"] == $nextPaysystemId) {
							$arParams["ORDER_CONFIRM_BY_SMS_CODE"] = "Y";
							break(1);
						}
					}

				}

			}

		}

	} else {
		$arResult["ERRORS"]["ORDER_NOT_FOUND"] = "Y";
	}

}

$this->IncludeComponentTemplate();
