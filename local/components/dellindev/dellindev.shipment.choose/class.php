<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;

class CDellinShippingChooseComponent extends CBitrixComponent
{
//	const MAP_TYPE_YANDEX = 'yandex';
//	const MAP_TYPE_GOOGLE = 'google';
//	const MAP_TYPE_NONE = 'none';

	/**
	 * @param array $params
	 * @return bool
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function checkParams($params)
	{
		

		return true;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		if(CModule::IncludeModule("dellindev.shipment")){//fix TypeError... maybe

			$params = parent::onPrepareComponentParams($params);

			// if(!$params['DELIVERY_LOCATION'])
			// {
			// 	unset($_SESSION['current_terminals']);
			// }	

			$userType = $params['USER_RESULT']['PERSON_TYPE_ID'];

			$terminalList = \Sale\Handlers\Delivery\Dellin\AjaxService::getTerminalsForAjaxOfSession();

			$widgetParams = $this->getWidgetParams($terminalList);

			$currentTerminalField = $this->getFieldIdOnCODE($userType, 'TERMINAL_ID');
			$currentDeliveryTimeStartField  = $this->getFieldIdOnCODE($userType, 'DELLIN_DELIVERYTIME_START');
			$currentDeliveryTimeEndField = $this->getFieldIdOnCODE($userType, 'DELLIN_DELIVERYTIME_END');
			//clear teminals block
			
			// echo '<pre>';
			// var_dump($params);
			// echo '</pre>';
			// die();


			$params['dellin']['terminalList'] = $terminalList['terminals'];
			$params['dellin']['currentTerminalField'] = $currentTerminalField;
			$params['dellin']['currentDeliveryTimeStartField'] = $currentDeliveryTimeStartField;
			$params['dellin']['currentDeliveryTimeEndField'] = $currentDeliveryTimeEndField;
			$params['dellin']['terminalsMethod'] = $terminalList['terminals_method_id'];
			$params['dellin']['widget'] = $widgetParams;
		//	$params['dellin']['city'] = $terminalList


			return $params;
		}
	}

	private function getFieldIdOnCODE($userType, $code){

        $propsListToID = \DellinShipping\Kernel::getTerminalProps($userType);
        foreach ($propsListToID as $prop){

            if($prop['CODE'] == $code){
                $idField = $prop['ID'];
            }

        }
        return $idField;
    }

	private function getWidgetParams($terminalList)
	{
		$result = [
			'hasShowDellinMap' => false,
			'isOldViewComponent' => true,//default - is old value
		];

		if(is_array($terminalList['terminals_method_id']) &&
		   count($terminalList['terminals_method_id']) > 0 )
		{
			
			$config = \Bitrix\Sale\Delivery\Services\Manager::getById($terminalList['terminals_method_id'][0]);
			$stateField = $config['CONFIG']['WIDGET']; 
		}

		$key = COption::GetOptionString("fileman", "yandex_map_api_key");

		if($stateField['VIEW_TYPE'] == 0)
		{
		   $result['hasShowDellinMap'] = false;
		   $result['isOldViewComponent'] = true;
		}

		if($stateField['VIEW_TYPE'] == 1)
		{
			if(isset($key) && !empty($key)){
				$result['hasShowDellinMap'] = true;
			}
			
			$result['isOldViewComponent'] = false;
		}

		if($stateField['VIEW_TYPE'] == 2)
		{
			$result['hasShowDellinMap'] = false;
			$result['isOldViewComponent'] = false;
		}


		return $result;
	}

	
	/**
	 * void
	 */
	public function executeComponent()
	{
		try
		{
			$this->checkParams($this->arParams);
		}
		catch(\Exception $e)
		{
			ShowError($e->getMessage());
			return;
		}


		$this->includeComponentTemplate();
	}


}