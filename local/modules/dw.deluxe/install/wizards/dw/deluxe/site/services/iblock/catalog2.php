<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!CModule::IncludeModule('catalog')) {
	return;
}

if (!WIZARD_INSTALL_DEMO_DATA) {
	return;
}

//offers iblock import
$shopLocalization = $wizard->GetVar('shopLocalization');

$iblockXMLFileOffers = WIZARD_SERVICE_RELATIVE_PATH . '/xml/ru/offers.xml';

$iblockCodeOffers = 'deluxe_offers_' . WIZARD_SITE_ID;
$iblockTypeOffers = 'offers';

$rsIBlock = CIBlock::GetList([], ['XML_ID' => $iblockCodeOffers, 'TYPE' => $iblockTypeOffers]);
$IBLOCK_OFFERS_ID = false;

if ($arIBlock = $rsIBlock->Fetch()) {
	$IBLOCK_OFFERS_ID = $arIBlock['ID'];

	if (WIZARD_INSTALL_DEMO_DATA) {
		CIBlock::Delete($arIBlock['ID']);
		$IBLOCK_OFFERS_ID = false;
	}
}

//--offers

if ($IBLOCK_OFFERS_ID === false) {
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

	$IBLOCK_OFFERS_ID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFileOffers,
		'deluxe_offers',
		$iblockTypeOffers,
		WIZARD_SITE_ID,
		$permissions
	);

	if ($IBLOCK_OFFERS_ID < 1) {
		return;
	}

	$_SESSION['WIZARD_OFFERS_IBLOCK_ID'] = $IBLOCK_OFFERS_ID;
} else {
	$arSites = [];
	$db_res = CIBlock::GetSite($IBLOCK_OFFERS_ID);

	while ($res = $db_res->Fetch()) {
		$arSites[] = $res['LID'];
	}

	if (!in_array(WIZARD_SITE_ID, $arSites)) {
		$arSites[] = WIZARD_SITE_ID;
		$iblock = new CIBlock();
		$iblock->Update($IBLOCK_OFFERS_ID, ['LID' => $arSites]);
	}
}
