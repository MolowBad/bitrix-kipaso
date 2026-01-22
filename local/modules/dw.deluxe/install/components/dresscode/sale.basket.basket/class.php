<?
	//bitrix uses
	use Bitrix\Main,
	    Bitrix\Main\Localization\Loc as Loc,
	    Bitrix\Main\Loader,
	    Bitrix\Main\Config\Option,
	    Bitrix\Sale\Delivery,
	    Bitrix\Sale\PaySystem,
	    Bitrix\Sale\PersonType,
	    Bitrix\Sale,
	    Bitrix\Sale\Order,
	    Bitrix\Sale\DiscountCouponsManager,
	    Bitrix\Main\Context;

	//other uses
	use DigitalWeb\Basket as DwBasket;
	use DigitalWeb\BasketAjax as DwBasketAjax;

	class CBasketComponentAjax{

	    //private
	    private static $instance = null;

	    //static vars
	    protected static $arDataStorage = array();
	    protected static $extraServices = null;
	    protected static $personTypeId = null;
	    protected static $paysystemId = null;
	    protected static $deliveryId = null;
	    protected static $locationId = null;
	    protected static $actionType = null;
	    protected static $siteId = null;

	    //construct
	    public function __construct(){}

	    //singleton
	    public static function getInstance(){

	        if(is_null(self::$instance)){
	            self::$instance = new self;
	        }

	        return self::$instance;
	    }

		//functions
		public function checkAjaxRequest(){

			//langs
			\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/ajax.php");

			//load modules
			if(!\Bitrix\Main\Loader::includeModule("dw.deluxe")){
				die;
			}

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get request vars
			static::$actionType = $request->getPost("actionType");
			static::$siteId = $request->getPost("siteId");

			//get basket vars
			static::$extraServices = $request->getPost("extraServices");
			static::$personTypeId = $request->getPost("personTypeId");
			static::$paysystemId = $request->getPost("paysystemId");
			static::$deliveryId = $request->getPost("deliveryId");
			static::$locationId = $request->getPost("locationId");

			//check siteId from get request
			if(empty(static::$siteId)){
				static::$siteId = $request->getQuery("siteId");
			}

			//check actionType from get request
			if(empty(static::$actionType)){
				static::$actionType = $request->getQuery("actionType");
			}

			//check request act
			if(!empty(static::$actionType)){

				//order make
				if(static::$actionType == "orderMake"){
					self::orderMake();
				}

				//get full basket info
				elseif(static::$actionType == "getCompilation"){
					self::getCompilation();
				}

				//update basket item (quantity)
				elseif(static::$actionType == "updateItem"){
					self::updateItem();
				}

				//delete item from basket
				elseif(static::$actionType == "removeItem"){
					self::removeItem();
				}

				//get order info for deliveries
				elseif(static::$actionType == "compDeliveries"){
					self::compDeliveries();
				}

				//set basket coupon
				elseif(static::$actionType == "setCoupon"){
					self::setCoupon();
				}

				//clear basket
				elseif(static::$actionType == "clearAll"){
					self::clearBasket();
				}

				//get location select component
				elseif(static::$actionType == "pushLocation"){
					self::getLocationComponent();
				}

				//replay sms code
				elseif(static::$actionType == "smsReplay"){
					self::confirmSmsReplay();
				}

				//confirm order by sms code
				elseif(static::$actionType == "smsConfirm"){
					self::confirmOrderBySms();
				}

				//get fast order component
				elseif(static::$actionType == "getFastBasketWindow"){
					self::getFastOrderComponent();
				}

			}

		}

		function orderMake(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get transmitted data
			$arTransmitted = $request->getPostList()->toArray();
			$arTransmitted["files"] = $request->getFileList()->toArray();

			//check
			if(!empty($arTransmitted)){

		        //check modules
		        if(!Loader::includeModule("sale")){
		            return false;
		        }

	        	//get arParams
	        	$arParams = !empty($arTransmitted["params"]) ? $arTransmitted["params"] : array();

	        	//set component params
	        	DwBasketAjax::setParams($arParams);

	        	//get ajax object
	        	$basketAjax = DwBasketAjax::getInstance();

	        	//set params
	        	$basketAjax->setFields(static::$siteId, static::$deliveryId, static::$paysystemId, static::$personTypeId, static::$locationId);

	            //set extraServices
	            if(!empty(static::$extraServices)){
	                $basketAjax::setExtraServices(static::$extraServices);
	            }

	            //set store
	            if(!empty($arTransmitted["store"])){
	                $basketAjax::setStoreId($arTransmitted["store"]);
	            }

	        	//create new order
	        	if($orderResult = $basketAjax->orderMake($arTransmitted)){
	        		$arReturn["orderResult"] = $orderResult;
	        	}

		        //set error
		        else{
		        	//C3_ORDER_MAKE_ERROR
		        	if(empty(DwBasket::getErrors())){
		        		DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_ORDER_MAKE_ERROR"));
		        	}
		        }

	        	//check errors
				if($arErrors = DwBasket::getErrors()){
					$arReturn["errors"] = $arErrors;
					$arReturn["status"] = false;
					$arReturn["error"] = true;
				}

				//push result
				self::pushDataToStorage($arReturn);

			}

		}

		public static function getCompilation(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//check vars
			if(!empty(static::$siteId)){

		        //check modules
		        if(!Loader::includeModule("sale")){
		            return false;
		        }

	        	//get arParams
				$arParams = $request->getPost("params");

	        	//set component params
	        	DwBasketAjax::setParams($arParams);

	        	//get ajax object
	        	$basketAjax = DwBasketAjax::getInstance();

	        	//set params
	        	$basketAjax->setFields(static::$siteId, static::$deliveryId, static::$paysystemId, static::$personTypeId, static::$locationId);

	            //set extraServices
	            if(!empty(static::$extraServices)){
	                $basketAjax::setExtraServices(static::$extraServices);
	            }

	        	//compilation data (get basket & order info)
	        	$arCompilation = $basketAjax->compilation();

	        	//check result
	        	if(!empty($arCompilation)){

		        	//push
	        		$arReturn["compilation"] = $arCompilation;

		        	//get gifts
		        	$arReturn["gifts"] = self::getGiftsComponent($arParams, $arCompilation["applied_discount_list"], $arCompilation["full_discount_list"]);

					//execute event
					foreach(GetModuleEvents("sale", "OnSaleComponentOrderShowAjaxAnswer", true) as $arEvent){
						ExecuteModuleEventEx($arEvent, array(&$arReturn));
					}

	        	}

		        //set error
		        else{
		        	//C3_BASKET_COMPILATION_ERROR
		        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_COMPILATION_ERROR"));
		        }

			}

			//check errors
			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function updateItem(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get basket id
			$basketId = $request->getPost("basketId");

			//get quantity
			$quantity = $request->getPost("quantity");

			//check vars
			if(!empty($basketId) && !empty(static::$siteId) && !empty($quantity)){

		        //check modules
		        if(!Loader::includeModule("sale")){
		            return false;
		        }

		        //delete & check state
		        if(DwBasket::updateQuantity($basketId, $quantity, static::$siteId)){

		        	//get arParams
					$arParams = $request->getPost("params");

		        	//set component params
		        	DwBasketAjax::setParams($arParams);

		        	//get ajax object
		        	$basketAjax = DwBasketAjax::getInstance();

		        	//set params
		        	$basketAjax->setFields(static::$siteId, static::$deliveryId, static::$paysystemId, static::$personTypeId, static::$locationId);

		            //set extraServices
		            if(!empty(static::$extraServices)){
		                $basketAjax::setExtraServices(static::$extraServices);
		            }

		        	//compilation data (get basket & order info)
		        	$arCompilation = $basketAjax->compilation();

		        	//check result
		        	if(!empty($arCompilation)){

			        	//push
		        		$arReturn["compilation"] = $arCompilation;

			        	//get gifts
			        	$arReturn["gifts"] = self::getGiftsComponent($arParams, $arCompilation["applied_discount_list"], $arCompilation["full_discount_list"]);

						//execute event
						foreach(GetModuleEvents("sale", "OnSaleComponentOrderShowAjaxAnswer", true) as $arEvent){
							ExecuteModuleEventEx($arEvent, array(&$arReturn));
						}

		        	}

			        //set error
			        else{
			        	//C3_BASKET_COMPILATION_ERROR
			        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_COMPILATION_ERROR"));
			        }

		        }

		        //set error
		        else{
		        	//C3_BASKET_UPDATE_ERROR
		        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_UPDATE_ERROR"));
		        }

			}

			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);
		}

		public static function removeItem(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get basket id
			$basketId = $request->getPost("basketId");

			//check vars
			if(!empty($basketId) && !empty(static::$siteId)){

		        //check modules
		        if(!Loader::includeModule("sale")){
		            return false;
		        }

		        //delete & check state
		        if(DwBasket::deleteItem($basketId, static::$siteId)){

		        	//get arParams
					$arParams = $request->getPost("params");

					//set component params
		        	DwBasketAjax::setParams($arParams);

		        	//get ajax object
		        	$basketAjax = DwBasketAjax::getInstance();

		        	//check last product
		        	if($basketAjax::isEmptyBasket()){

			        	//set params
			        	$basketAjax->setFields(static::$siteId, static::$deliveryId, static::$paysystemId, static::$personTypeId, static::$locationId);

			            //set extraServices
			            if(!empty(static::$extraServices)){
			                $basketAjax::setExtraServices(static::$extraServices);
			            }

			        	//compilation data (get basket & order info)
			        	$arCompilation = $basketAjax->compilation();

			        	//check result
			        	if(!empty($arCompilation)){

				        	//push
			        		$arReturn["compilation"] = $arCompilation;

				        	//get gifts
				        	$arReturn["gifts"] = self::getGiftsComponent($arParams, $arCompilation["applied_discount_list"], $arCompilation["full_discount_list"]);

							//execute event
							foreach(GetModuleEvents("sale", "OnSaleComponentOrderShowAjaxAnswer", true) as $arEvent){
								ExecuteModuleEventEx($arEvent, array(&$arReturn));
							}

			        	}

				        //set error
				        else{
			        		//C3_BASKET_COMPILATION_ERROR
				        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_COMPILATION_ERROR"));
				        }

				   	}

		        }

		        //set error
		        else{
		        	//C3_BASKET_DELETE_ERROR
				    DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_DELETE_ERROR"));
		        }

			}

			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function compDeliveries(){

			//basket object
			$basket = DwBasket::getInstance();

			//basket
			$arBasketItems = $basket->getBasketItems();
			$arOrder = $basket->getOrderInfo();

			//push result
			self::pushDataToStorage(array("success" => !empty($arOrder)));

		}

		public static function setCoupon(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get basket id
			$couponValue = $request->getPost("coupon");

			//check vars
			if(!empty($couponValue) && !empty(static::$siteId)){

		        //check modules
		        if(!Loader::includeModule("sale")){
		            return false;
		        }

		        //set
		        if(DwBasket::setCoupon($couponValue, static::$siteId)){
		        	$arReturn["success"] = true;
		        }

		        //set error
		        else{
	        		//C3_BASKET_COUPON_ERROR
		        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_COUPON_ERROR"));
		        }

			}

			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function clearBasket(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//check vars
			if(!empty(static::$siteId)){

		        //check modules
		        if(!Loader::includeModule("sale")){
		            return false;
		        }

		        //set siteId
		        DwBasket::setSiteId(static::$siteId);

		        //delete & check state
		        if(!DwBasket::clearBasket(static::$siteId)){
	        		//C3_BASKET_CLEAR_ERROR
		        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_BASKET_CLEAR_ERROR"));
		        }

			}

			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function getLocationComponent(){

			//globals
			global $APPLICATION;

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get search value
			$searchValue = $request->getPost("value");

			//start buffering
			ob_start();

			//push component
			$APPLICATION->IncludeComponent(
				"dresscode:sale.location.select",
				".default",
				array(
					"SITE_ID" => empty(static::$siteId) ? static::$siteId : $context->getSite(),
					"LOCATION_VALUE" => $searchValue
				),
				false,
				Array(
					//hide hermitage actions
					"HIDE_ICONS" => "Y"
				)
			);

			//capture
			$componentData = ob_get_contents();

			//stop buffering
			ob_end_clean();

			//check result
			if(!empty($componentData)){
				$arReturn["component"] = $componentData;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function confirmSmsReplay(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get arParams
			$arParams = $request->getPost("params");

			//get sms code
			$smsCode = $request->getPost("smsCode");

			//get order id
			$orderId = $request->getPost("orderId");

			//check vars
			if(!empty($orderId)){

				//check time
				if(time() - \DigitalWeb\Basket::getLastTimeSendSmsCode() >= 60){
					$arReturn["status"] = \DigitalWeb\Basket::sendOrderConfirmSms($orderId);
				}

				//timeout error
				else{
					//C3_CONFIRM_SMS_REPLAY_TIMEOUT_ERROR
					DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_CONFIRM_SMS_REPLAY_TIMEOUT_ERROR"));
				}

			}

			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function confirmOrderBySms(){

			//vars
			$arReturn = array(
				"status" => true
			);

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get arParams
			$arParams = $request->getPost("params");

			//get sms code
			$smsCode = $request->getPost("smsCode");

			//get order id
			$orderId = $request->getPost("orderId");

			//check vars
			if(!empty($orderId) && !empty($smsCode) && strlen($smsCode) == 4){

				//check settings
				if(!empty($arParams["ORDER_CONFIRM_BY_SMS_CODE"]) && $arParams["ORDER_CONFIRM_BY_SMS_CODE"] == "Y"){

					//check status settings
					if(!empty($arParams["ORDER_CONFIRM_BY_SMS_STATUS"])){

				        //compare code
				        if(DwBasket::getConfirmBySmsCode($orderId) == $smsCode){

							//check modules
							if(!Loader::includeModule("sale")){
							    return false;
							}

							//get order
							$order = DwBasket::getOrderById($orderId);

							//check order
							if($order instanceof \Bitrix\Sale\Order){

								//set order status
								$statusResult = $order->setField("STATUS_ID", $arParams["ORDER_CONFIRM_BY_SMS_STATUS"]);

								//check success
								if($statusResult->isSuccess()){

									//save order
									$orderResult = $order->save();

									//check success
									if($orderResult->isSuccess()){

										//set requet status
										$arReturn["success"] = true;

									}

									//set errors
									else{
										DwBasket::setError($orderResult->getErrorMessages());
									}

								}

								//set errors
								else{
									DwBasket::setError($statusResult->getErrorMessages());
								}

							}

							//set error
							else{
				        		//C3_CONFIRM_SMS_ORDER_NOT_FOUND_ERROR
					        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_CONFIRM_SMS_ORDER_NOT_FOUND_ERROR"));
							}


				        }

				        //set error
				        else{
			        		//C3_CONFIRM_CODE_ERROR
				        	DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_CONFIRM_CODE_ERROR"));
				        }

				    }

			    }

				//set error
				else{
					//C3_CONFIRM_SMS_SETTINGS_ERROR
					DwBasket::setError(\Bitrix\Main\Localization\Loc::GetMessage("C3_CONFIRM_SMS_SETTINGS_ERROR"));
				}

			}

			if($arErrors = DwBasket::getErrors()){
				$arReturn["errors"] = $arErrors;
				$arReturn["status"] = false;
				$arReturn["error"] = true;
			}

			//push result
			self::pushDataToStorage($arReturn);

		}

		public static function getFastOrderComponent(){

			//globals
			global $APPLICATION;

			//application
			$application = \Bitrix\Main\Application::getInstance();

			//context
			$context = $application->getContext();

			//get request
			$request = $context->getRequest();

			//get masked params
			$sendSms = $request->getQuery("sendSms");
			$maskedUse = $request->getQuery("maskedUse");
			$maskedFormat = $request->getQuery("maskedFormat");

			//push component html
			$APPLICATION->IncludeComponent(
				"dresscode:basket.fast.order",
				".default",
				array(
					"SITE_ID" => empty(static::$siteId) ? static::$siteId : $context->getSite(),
					"USE_MASKED" => !empty($maskedUse) ? $maskedUse : "N",
					"SEND_SMS_MESSAGE" => !empty($sendSms) ? $sendSms : "N",
					"MASKED_FORMAT" => !empty($maskedFormat) ? $maskedFormat : "",
				),
				false,
				array(
					//hide hermitage actions
					"HIDE_ICONS" => "Y"
				)
			);

		}

		//gifts include
		public static function getGiftsComponent($arParams, $appliedDiscount = array(), $fullDiscount = array()){

			//globals
			global $APPLICATION;

			//vars
			$componentHTML = "";

			//start buffering
			ob_start();

			//push component
			$APPLICATION->IncludeComponent("bitrix:sale.gift.basket", ".default", array(
					"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
					"PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
					"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
					"PRODUCT_PRICE_CODE" => $arParams["PRODUCT_PRICE_CODE"],
					"APPLIED_DISCOUNT_LIST" => $appliedDiscount,
					"FULL_DISCOUNT_LIST" => $fullDiscount,
					"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
					"HIDE_MEASURES" => $arParams["HIDE_MEASURES"],
					"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
					"CURRENCY_ID" => $arParams["CURRENCY_ID"]
				),
				false
			);

			//save buffer
			$componentHTML = ob_get_contents();

			//clean buffer
			ob_end_clean();

			return $componentHTML;

		}

		public static function pushDataToStorage($arData){

			//check data
			if(!empty($arData)){
				static::$arDataStorage = $arData;
			}

			return false;
		}

		public static function getFromDataStorage(){
			return static::$arDataStorage;
		}

	}

?>