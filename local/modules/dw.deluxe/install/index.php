<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use	Bitrix\Main\ORM\Data\DataManager;

Loc::loadMessages(__FILE__);

final class dw_deluxe extends CModule
{
	public $MODULE_ID = 'dw.deluxe';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_CSS;
	public $MODULE_GROUP_RIGHTS = 'Y';

	public function __construct()
	{
		$arModuleVersion = [];

		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

		$this->MODULE_NAME = Loc::getMessage('DW_DELUXE_MODULE_INSTALL_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('DW_DELUXE_MODULE_INSTALL_DESCRIPTION');
		$this->PARTNER_NAME = Loc::getMessage('DW_DELUXE_MODULE_VENDOR_NAME');
		$this->PARTNER_URI = Loc::getMessage('DW_DELUXE_MODULE_VENDOR_URI');
	}

	public function DoInstall(): bool
	{
		$this->InstallFiles();
		$this->InstallDB();
		$this->InstallEvents();

		return true;
	}

	public function DoUninstall(): bool
	{
		$this->UnInstallDB();
		$this->UnInstallFiles();
		$this->UnInstallEvents();

		return true;
	}

	public function InstallDB(): bool
	{
		ModuleManager::registerModule($this->MODULE_ID);

		return true;
	}

	public function UnInstallDB(): bool
	{
		ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	public function InstallEvents(): bool
	{
		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, 'DwBuffer', 'modifyBuffer');
		$eventManager->registerEventHandler('sale', 'OnSaleOrderPaid', $this->MODULE_ID, 'DwBonus', 'addBonus');
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, 'DwProductEvents', 'productAfterSave');
		$eventManager->registerEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, 'DwProductEvents', 'productAfterSave');
		$eventManager->registerEventHandler('catalog', 'OnPriceUpdate', $this->MODULE_ID, 'DwProductEvents', 'productAfterSave');
		$eventManager->registerEventHandler('catalog', 'OnPriceAdd', $this->MODULE_ID, 'DwProductEvents', 'productAfterSave');
		$eventManager->registerEventHandler('catalog', 'Bitrix\\Catalog\\Model\\Product::' . DataManager::EVENT_ON_AFTER_ADD, $this->MODULE_ID, 'DwProductEvents', 'productUpdate');
		$eventManager->registerEventHandler('catalog', 'Bitrix\\Catalog\\Model\\Product::' . DataManager::EVENT_ON_AFTER_UPDATE, $this->MODULE_ID, 'DwProductEvents', 'productUpdate');

		return true;
	}

	public function UnInstallEvents(): bool
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql = <<<SQL
			DELETE FROM b_module_to_module
			WHERE FROM_MODULE_ID='{$sqlHelper->forSql($this->MODULE_ID)}' OR
			TO_MODULE_ID='{$sqlHelper->forSql($this->MODULE_ID)}'
		SQL;

		$connection->queryExecute($sql);

		$eventManager = EventManager::getInstance();
		$eventManager->clearLoadedHandlers();

		return true;
	}

	public function InstallFiles(): bool
	{
		$documentRoot = $_SERVER['DOCUMENT_ROOT'];

		CopyDirFiles(
			"{$documentRoot}/bitrix/modules/{$this->MODULE_ID}/install/components",
			"{$documentRoot}/bitrix/components",
			true,
			true
		);

		return true;
	}

	public function UnInstallFiles(): bool
	{
		DeleteDirFilesEx("/bitrix/modules/{$this->MODULE_ID}");
		DeleteDirFilesEx('/bitrix/wizards/dw');

		return true;
	}
}
