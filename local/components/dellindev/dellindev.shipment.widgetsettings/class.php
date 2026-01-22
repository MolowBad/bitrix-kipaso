<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CSaleDellinWidget extends CBitrixComponent
{
	protected function checkParams($params)
	{

        if(!isset($params['yandex_map_api_key']))
            throw new \Bitrix\Main\ArgumentNullException('yandex_map_api_key');

		

	}

	public function onPrepareComponentParams($params)
    {

		if(isset($_REQUEST['ID']) || !empty($_REQUEST['ID'])) {

			$configParams = \Bitrix\Sale\Delivery\Services\Manager::getById((int)$_REQUEST['ID']);
			$params['WIDGET'] = $configParams['CONFIG']['WIDGET'];
		}



		$params['yandex_map_api_key']=COption::GetOptionString("fileman", "yandex_map_api_key");

        return $params;
    }
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

		if(!CModule::IncludeModule('sale'))
		{
			ShowError("Module sale not installed!");
			return;
		}



		CJSCore::Init('core', 'ajax');
		$this->includeComponentTemplate();
	}
}