<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CSaleDellinYuri extends CBitrixComponent
{
	protected function checkParams($params)
	{

        if(!isset($params['AJAX_SERVICE_CLASS']))
            throw new \Bitrix\Main\ArgumentNullException('AJAX_SERVICE_CLASS');

	}

	public function onPrepareComponentParams($params)
    {
        if (!isset($params['AJAX_SERVICE_CLASS']))
            $params['AJAX_SERVICE_CLASS'] = $this->arParams['AJAX_SERVICE_CLASS'];

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