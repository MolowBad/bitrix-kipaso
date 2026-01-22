<?php
class DwBonus
{
    public static function addBonus($entity){

    	if(!empty($entity)){

	    	\Bitrix\Main\Loader::includeModule("currency");
	    	\Bitrix\Main\Loader::includeModule("iblock");
			\Bitrix\Main\Loader::includeModule("sale");

			if($entity instanceof \Bitrix\Main\Event){

				$parameters = $entity->getParameters();
				$order = $parameters["ENTITY"];

			}

			elseif($entity instanceof \Bitrix\Sale\Order){
				$order = $entity;
			}

			if(!$order instanceof \Bitrix\Sale\Order){
				return false;
			}

	    	$bonusValue = 0;
	    	$arProcessedOrders = array();

	    	$userId = $order->getUserId();

	    	$currencyCode = \Bitrix\Currency\CurrencyManager::getBaseCurrency();

			$paymentCollection = $order->getPaymentCollection();

			if(!$order->isPaid() && !$paymentCollection->isPaid()){
				return false;
			}

			if($paymentCollection->isExistsInnerPayment()){
				return false;
			}

	    	if(!empty($userId)){

				if(!$arUserAccount = CSaleUserAccount::GetByUserID($userId, $currencyCode)){

					$arNewAccountFields = array("USER_ID" => $userId, "CURRENCY" => $currencyCode, "CURRENT_BUDGET" => 0);
					$accountID = CSaleUserAccount::Add($arNewAccountFields);
					if(!empty($accountID)){
						$arUserAccount = array_merge($arNewAccountFields, array(
							"ID" => $accountID,
							"NOTES" => "",
							"LOCKED" => "",
							"DATE_LOCKED" => ""
						));
					}

				}

	   			if(!empty($arUserAccount) && $arUserAccount["LOCKED"] != "Y"){

	   				$orderId = $order->getId();

	   				if(!empty($arUserAccount["NOTES"])){
	   					$arProcessedOrders = explode(",", $arUserAccount["NOTES"]);
	   				}

	   				if(!empty($arProcessedOrders)){
	   					foreach($arProcessedOrders as $nextOrderId){
	   						if($orderId == $nextOrderId){
	   							return false;
	   						}
	   					}
	   				}

	   				$arProcessedOrders[] = $orderId;

				    $basket = $order->getBasket();
					$basketItems = $basket->getBasketItems();

					foreach($basketItems as $basketItem){

						$productId = $basketItem->getProductId();
			   			$productQuantity = $basketItem->getQuantity();

						$dbProduct = CIBlockElement::GetByID($productId);

						if($arProduct = $dbProduct->GetNext()){

				   			$dbBonus = CIBlockElement::GetProperty($arProduct["IBLOCK_ID"], $arProduct["ID"], array(), array("CODE" => "BONUS"));
							$arBonus = $dbBonus->Fetch();

							if(!empty($arBonus["VALUE"])){
								$bonusValue += ($arBonus["VALUE"] * $productQuantity);
							}

							else{
								$arParentSkuProduct = CCatalogSku::GetProductInfo($arProduct["ID"]);

								if(is_array($arParentSkuProduct)){

						   			$dbBonusParentProduct = CIBlockElement::GetProperty($arParentSkuProduct["IBLOCK_ID"], $arParentSkuProduct["ID"], array(), array("CODE" => "BONUS"));
									if($arBonusParentProduct = $dbBonusParentProduct->Fetch()){
										if(!empty($arBonusParentProduct["VALUE"])){
											$bonusValue += ($arBonusParentProduct["VALUE"] * $productQuantity);
										}
									}

								}

							}

						}

					}

					if(!empty($bonusValue)){
						CSaleUserAccount::Update(
							$arUserAccount["ID"],
							array(
								"USER_ID" => $arUserAccount["USER_ID"],
								"CURRENT_BUDGET" => ($arUserAccount["CURRENT_BUDGET"] + $bonusValue),
								"CURRENCY" => $arUserAccount["CURRENCY"],
								"NOTES" => implode(",", $arProcessedOrders),
								"LOCKED" => $arUserAccount["LOCKED"],
								"DATE_LOCKED" => $arUserAccount["DATE_LOCKED"],
							)
						);
					}

				}

			}

		}

    	return $order;
    }
}
