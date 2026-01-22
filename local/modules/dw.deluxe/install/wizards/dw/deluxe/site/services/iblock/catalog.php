<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog')) {
	return;
}

if (!WIZARD_INSTALL_DEMO_DATA) {
	return;
}

//catalog iblock import
$shopLocalization = $wizard->GetVar('shopLocalization');

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH . '/xml/ru/catalog.xml';

$iblockCode = '92_' . WIZARD_SITE_ID;
$iblockType = 'catalog';

$rsIBlock = CIBlock::GetList([], ['XML_ID' => $iblockCode, 'TYPE' => $iblockType]);
$IBLOCK_CATALOG_ID = false;

if ($arIBlock = $rsIBlock->Fetch()) {
	$IBLOCK_CATALOG_ID = $arIBlock['ID'];
}
//for old furniture catalog
else {
	$rsIBlock = CIBlock::GetList([], ['XML_ID' => 'furniture_' . WIZARD_SITE_ID, 'TYPE' => $iblockType]);

	if ($arIBlock = $rsIBlock->Fetch()) {
		$IBLOCK_CATALOG_ID = $arIBlock['ID'];
	}
}

if ($IBLOCK_CATALOG_ID === false) {
	$permissions = [
		'1' => 'X',
		'2' => 'R'
	];
	$dbGroup = CGroup::GetList($by = '', $order = '', ['STRING_ID' => 'sale_administrator']);

	if ($arGroup = $dbGroup->Fetch()) {
		$permissions[$arGroup['ID']] = 'W';
	}

	$dbGroup = CGroup::GetList($by = '', $order = '', ['STRING_ID' => 'content_editor']);

	if ($arGroup = $dbGroup->Fetch()) {
		$permissions[$arGroup['ID']] = 'W';
	}

	$IBLOCK_CATALOG_ID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		'92',
		$iblockType,
		WIZARD_SITE_ID,
		$permissions
	);
} else {
	$arSites = [];
	$db_res = CIBlock::GetSite($IBLOCK_CATALOG_ID);

	while ($res = $db_res->Fetch()) {
		$arSites[] = $res['LID'];
	}

	if (!in_array(WIZARD_SITE_ID, $arSites)) {
		$arSites[] = WIZARD_SITE_ID;
		$iblock = new CIBlock();
		$iblock->Update($IBLOCK_CATALOG_ID, ['LID' => $arSites]);
	}
}

$_SESSION['WIZARD_CATALOG_IBLOCK_ID'] = $IBLOCK_CATALOG_ID;
