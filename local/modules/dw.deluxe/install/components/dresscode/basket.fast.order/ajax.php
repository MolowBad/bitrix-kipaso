<?php

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery;
use	Bitrix\Sale\PaySystem;
use	Bitrix\Sale\PersonType;
use	Bitrix\Sale;
use	Bitrix\Sale\Order;
use Bitrix\Main\UserConsent\Consent;

if(!empty($_POST["act"])){
	Loader::requireModule("dw.deluxe");

	if($_POST["act"] == "sendFastForm"){

		//globals
		global $USER;

		//vars
		$arSysErrors = array();

		if(empty($_POST["basket-form-telephone"])){
			//save error
			$arSysErrors[] = array(
				"TELEPHONE" => false
			);
		}

		if(empty($_POST["basket-form-personal-info"])){
			//save error
			$arSysErrors[] = array(
				"PERSONAL" => false
			);
		}

		if(empty($_POST["site_id"])){
			//save error
			$arSysErrors[] = array(
				"SITE_ID" => false
			);
		}

		if(empty($arSysErrors)){

			//convert strings
			foreach ($_POST as $i => $nextPost){
				$_POST[$i] = !defined("BX_UTF") ? iconv("UTF-8","windows-1251//IGNORE", htmlspecialcharsbx($_POST[$i])) : htmlspecialcharsbx($_POST[$i]);
			}

			//get current user id
			$USER_ID = intval($USER->GetID());

			//if not authorized
			if($USER_ID == 0){

				//get unregistred user id
				$rsUser = CUser::GetByLogin("unregistered");
				$arUser = $rsUser->Fetch();

				if(!empty($arUser)){
					$USER_ID = $arUser["ID"];
				}

				//if unregistred user not create
				else{

					//create unregistred user
					$newUser = new CUser;
					$newPass = rand(0, 999999999);
					$arUserFields = array(
						"NAME"              => "unregistered",
						"LAST_NAME"         => "unregistered",
						"EMAIL"             => "unregistered@unregistered.com",
						"LOGIN"             => "unregistered",
						"LID"               => "ru",
						"ACTIVE"            => "Y",
						"GROUP_ID"          => array(),
						"PASSWORD"          => $newPass,
						"CONFIRM_PASSWORD"  => $newPass,
					);

					//save id
					$USER_ID = $newUser->Add($arUserFields);

					//check errors
					if(empty($USER_ID)){
						//write error
						$arSysErrors[] = array(
							"USER_CREATE" => $newUser->LAST_ERROR
						);
					}
				}
			}

			//get base currency
			$currencyCode = \Bitrix\Currency\CurrencyManager::getBaseCurrency();

			//create order d7
			$personTypes = array_keys(PersonType::load($_POST["site_id"]));

			if(!empty($personTypes)){

				$order = Order::create($_POST["site_id"], $USER_ID);
				$order->setPersonTypeId($personTypes[0]);
				$order->setField("CURRENCY", $currencyCode);

				//get basket
				$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), $_POST["site_id"]);
				$basketList = $basket->getListOfFormatText();

				if(!empty($basketList)){

					//bind basket to order
					$order->setBasket($basket);

					//set delivery
					$shipmentCollection = $order->getShipmentCollection();
					$shipment = $shipmentCollection->createItem();
					$service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
					$shipment->setFields(array(
						"DELIVERY_ID" => $service["ID"],
						"DELIVERY_NAME" => $service["NAME"],
					));

					$shipmentItemCollection = $shipment->getShipmentItemCollection();
					foreach ($order->getBasket() as $item){
						$shipmentItem = $shipmentItemCollection->createItem($item);
						$shipmentItem->setQuantity($item->getQuantity());
					}

					//set paytype
					$paymentCollection = $order->getPaymentCollection();
					$payment = $paymentCollection->createItem();

					//get paysystems
					$dbPtype = CSalePaySystem::GetList($arOrder = Array("SORT" => "ASC", "PSA_NAME" => "ASC"),
						Array("ACTIVE" => "Y", "PERSON_TYPE_ID" => $personTypes[0])
					);

					if($ptype = $dbPtype->Fetch()){

						$paySystemService = PaySystem\Manager::getObjectById($ptype["ID"]);
						$payment->setFields(array(
							"PAY_SYSTEM_ID" => $paySystemService->getField("ID"),
							"PAY_SYSTEM_NAME" => $paySystemService->getField("NAME"),
						));

						//set properties
						if(!empty($_POST["basket-form-telephone"])){
							$propertyCollection = $order->getPropertyCollection();
							if($phoneProp = $propertyCollection->getPhone()){
								$phoneProp->setValue($_POST["basket-form-telephone"]);
							}
						}

						if(!empty($_POST["basket-form-name"])){
							if($nameProp = $propertyCollection->getPayerName()){
								$nameProp->setValue($_POST["basket-form-name"]);
							}
						}

						//comment
						$order->setField("USER_DESCRIPTION", "fast order");

						//save order
						$order->doFinalAction(true);

						$result = $order->save();
						$orderId = $order->getId();

						if($result->isSuccess()){

							//check settings
							if(!empty($_POST["basket-form-sms-send"]) && $_POST["basket-form-sms-send"] == "Y"){

								//check telephone filling
								if(!empty($_POST["basket-form-telephone"])){

									//normalize number
									$phone =\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($_POST["basket-form-telephone"]);

									//get order number
									$orderNumber = $order->getField("ACCOUNT_NUMBER");;

									//get order vars
									$orderPrice = $order->getPrice();
									$orderCurrency = $order->getCurrency();
									$orderDeliveryPrice = $order->getDeliveryPrice();

									//prepate to send
									$sms = new \Bitrix\Main\Sms\Event("SALE_NEW_FAST_ORDER_SMS", array(
										"ORDER_ID" => $orderId,
										"USER_PHONE" => $phone,
										"ORDER_PRICE" => $orderPrice,
										"ORDER_NUMBER" => $orderNumber,
										"ORDER_CURRENCY" => $orderCurrency,
										"ORDER_DELIVERY_PRICE" => $orderDeliveryPrice,
									));

									//push message
									$smsResult = $sms->send(true);

									//check result
									if(!$smsResult->isSuccess()){

										//get error messages
										$errors = $smsResult->getErrorMessages();

									}

								}

							}

							$settings = DwSettings::getInstance()->getCurrentSettings();
							$userAgreementId = $settings['TEMPLATE_AGREEMENT_ID'] ?? null;

							if ($userAgreementId !== null) {
								Consent::addByContext($userAgreementId);
							}

							//return success
							echo \Bitrix\Main\Web\Json::encode(
								array("SUCCESS" => "Y", "ORDER_ID" => $orderId)
							);

						}

						//save error
						else{

							//write base error
							$arSysErrors[] = array(
								"ORDER_CREATE" => false
							);

							//write exception string
							if($exception = $APPLICATION->GetException()){
								$arSysErrors[] = array(
									"EXCEPTION" => $exception->GetString()
								);
							}

						}

					}

					else{
						//save error
						$arSysErrors[] = array(
							"PAY_SYSTEM_ID" => false
						);
					}
				}

				else{
					//save error
					$arSysErrors[] = array(
						"BASKET" => false
					);
				}

			}

			else{
				//save error
				$arSysErrors[] = array(
					"PERSON_TYPE" => false
				);
			}

		}

		//return errors
		if(!empty($arSysErrors)){
			//return error
			echo \Bitrix\Main\Web\Json::encode(
				array_merge($arSysErrors, array("ERROR" => "Y"))
			);
		}

	}

}
