<?php

namespace DigitalWeb;

IncludeModuleLangFile(__FILE__);

final class BasketAjax extends Basket
{
	private static $instance = false;

	public static function getInstance()
	{

		if (!self::$instance) {
			self::$instance = new BasketAjax();
		}

		return self::$instance;
	}

	public static function orderMake($orderData = array())
	{

		if (!empty($orderData)) {

			$orderData["properties"] = !empty($orderData["properties"]) ? $orderData["properties"] : array();
			$arBasketItems = parent::getBasketItems();
			$arParams = parent::getParams();

			if (!empty($arBasketItems)) {

				$order = parent::getOrder();

				if (!$order instanceof \Bitrix\Sale\order) {
					parent::setError("order instance error");
					return false;
				}

				$order->setBasket(parent::getBasket());
				$arOrder = parent::getOrderInfo();

				if (!empty($orderData["innerPayment"]) && $orderData["innerPayment"] == "Y") {

					if (!parent::setInnerPayment()) {
						return false;
					}

					if ($order->isPaid()) {
						parent::clearPayments();
					}

				}

				if (!empty($orderData["files"])) {
					$arFiles = parent::processingFiles($orderData["files"]);

					if (!empty($arFiles)) {
						$orderData["properties"] = ($orderData["properties"] + $arFiles);
					}
				}

				if (!empty($orderData["properties"])) {
					if (!parent::setProperties($orderData["properties"])) {
						return false;
					}
				}

				if (!empty($orderData["comment"])) {
					if (!parent::setOrderComment($orderData["comment"])) {
						return false;
					}
				}

				$arParams["REGISTER_USER"] = parent::checkUserRegisterByPhone() === false ? $arParams["REGISTER_USER"] : "N";

				if (!empty($arParams["REGISTER_USER"]) && $arParams["REGISTER_USER"] == "Y") {
					if (!parent::autoRegisterUser()) {
						return false;
					}
				}

				if (!parent::updateUserInfo($arOrder, $orderData["properties"])) {
					return false;
				}

				if (!parent::createUserProfile($arOrder, $orderData["properties"])) {
					return false;
				}

				$order->doFinalAction(true);
				$orderStatus = $order->save();
				$orderId = $order->getId();

				if ($orderStatus->isSuccess()) {

					if (!empty($arParams["SEND_SMS_MESSAGE"]) && $arParams["SEND_SMS_MESSAGE"] == "Y") {
						parent::sendOrderSms();
					}

					if (!empty($arParams["ORDER_CONFIRM_BY_SMS_CODE"]) && $arParams["ORDER_CONFIRM_BY_SMS_CODE"] == "Y") {

						if (!empty($arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"])) {

							$paysystemId = self::getFirstPaySystemId();

							foreach ($arParams["ORDER_CONFIRM_BY_SMS_PAYSYSTEMS"] as $nextPaysystemId) {

								if ($paysystemId == $nextPaysystemId) {
									parent::sendOrderConfirmSms($orderId);
								}

							}

						}

					}

					return ["orderId" => $orderId];

				}

				else {

					$errors = $orderStatus->getErrors();

					if (!empty($errors)) {
						foreach ($errors as $error) {
							parent::setError($error->getMessage());
						}
					}

				}

			}

			else {
				parent::setError(\Bitrix\Main\Localization\Loc::GetMessage("C2_BASKET_EMPTY_ERROR"));
			}

		}

		else {
			parent::setError(\Bitrix\Main\Localization\Loc::GetMessage("C2_BASKET_DATA_EMPTY_ERROR"));
		}

		return false;
	}

	public static function compilation()
	{

		$arReturn = array();
		$arBasketItems = parent::getBasketItems();
		$arProducts = parent::addProductsInfo($arBasketItems);

		if (!empty($arProducts)) {

			$arProducts = parent::addProductPrices($arProducts);
			$arParams = parent::getParams();

			$arOrder = [
				"DELIVERIES" => [],
				"PAYSYSTEMS" => [],
				"STORES" => []
			];

			if (isset($arParams["CALCULATE_ORDER_DATA"]) && $arParams["CALCULATE_ORDER_DATA"] === "Y") {
				$arOrder = parent::getOrderInfo();
			}

			$discountListFull = parent::getDiscountListFull();
			$appliedDiscounts = parent::getAppliedDiscounts();

			$arStores = parent::getStores($arProducts);
			$currencyCode = parent::getCurrencyCode();

			$isMinOrderAmount = parent::checkMinOrderAmount();

			$arReturn = array(
				"applied_discount_list" => $appliedDiscounts,
				"full_discount_list" => $discountListFull,
				"min_order_amount" => $isMinOrderAmount,
				"currency" => $currencyCode,
				"stores" => $arStores,
				"items" => $arProducts,
				"order" => $arOrder
			);

		}

		return $arReturn;

	}

}
