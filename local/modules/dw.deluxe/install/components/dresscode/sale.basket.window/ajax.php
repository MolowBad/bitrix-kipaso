<?
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use DigitalWeb\Basket as DwBasket;

define("STOP_STATISTICS", true);
define("NO_AGENT_CHECK", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

Loc::loadMessages(dirname(__FILE__)."/ajax.php");

if(!Loader::includeModule("dw.deluxe")){
	die;
}

$application = Application::getInstance();
$context = $application->getContext();
$request = $context->getRequest();

$actionType = $request->getPost("actionType");
$siteId = $request->getPost("siteId");

if(!empty($actionType)){

	if($actionType == "updateQuantity"){

		$arReturn = array(
			"status" => true
		);

		$basketId = $request->getPost("basketId");
		$quantity = $request->getPost("quantity");
		$hideMeasures = $request->getPost("hide-measures");

		if(!empty($basketId) && !empty($siteId) && !empty($quantity)){

			if(!Loader::includeModule("sale")){
				return false;
			}

			if(DwBasket::updateQuantity($basketId, $quantity, $siteId)){

				$basket = DwBasket::getInstance();
				$currencyCode = $basket->getCurrencyCode();
				$arBasketItems = $basket->getBasketItems();

				$arProducts = $basket->addProductsInfo($arBasketItems);
				$arProducts = $basket->addProductPrices($arProducts);

				foreach($arProducts as $arNextProduct){
					if($arNextProduct["BASKET_ID"] == $basketId){
						$arReturn["compilation"]["item"] = $arNextProduct; break(1);
					}
				}

				if(!empty($arReturn["compilation"]["item"])){

					if($hideMeasures != "Y"){
						$arReturn["compilation"]["measures"] = $basket->getMeasures();
					}

					$arReturn["compilation"]["item"]["BASE_SUM_FORMATED"] = \CCurrencyLang::CurrencyFormat(($arReturn["compilation"]["item"]["BASE_PRICE"] * $arReturn["compilation"]["item"]["QUANTITY"]), $currencyCode);
					$arReturn["compilation"]["item"]["SUM_FORMATED"] = \CCurrencyLang::CurrencyFormat(($arReturn["compilation"]["item"]["PRICE"] * $arReturn["compilation"]["item"]["QUANTITY"]), $currencyCode);

				}

				else{
					DwBasket::setError(Loc::getMessage("C4_BASKET_COMPILATION_ERROR"));
				}

			}

			else{
				DwBasket::setError(Loc::getMessage("C4_BASKET_UPDATE_ERROR"));
			}

		}

		if($arErrors = DwBasket::getErrors()){
			$arReturn["errors"] = $arErrors;
			$arReturn["status"] = false;
			$arReturn["error"] = true;
		}

		echo Json::encode($arReturn);

	}

	elseif($actionType == "removeItem"){

		$arReturn = array(
			"status" => true
		);

		$basketId = $request->getPost("basketId");

		if(!empty($basketId) && !empty($siteId)){

			if(!Loader::includeModule("sale")){
				return false;
			}

			if(!DwBasket::deleteItem($basketId, $siteId)){
				DwBasket::setError(Loc::getMessage("C4_BASKET_DELETE_ERROR"));
			}

		}

		if($arErrors = DwBasket::getErrors()){
			$arReturn["errors"] = $arErrors;
			$arReturn["status"] = false;
			$arReturn["error"] = true;
		}

		echo Json::encode($arReturn);

	}

}
