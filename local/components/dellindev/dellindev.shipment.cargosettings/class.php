<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CSaleDellinCargo extends CBitrixComponent
{
	protected function checkParams($params)
	{

	}

	public function onPrepareComponentParams($params)
	{

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