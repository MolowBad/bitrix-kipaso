<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!CModule::IncludeModule('iblock')) {
	return;
}

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH . '/xml/ru/news.xml';
$iblockCode = '4_' . WIZARD_SITE_ID;
$iblockType = 'info';

$rsIBlock = CIBlock::GetList([], ['CODE' => $iblockCode, 'TYPE' => $iblockType]);
$iblockID = false;

if ($arIBlock = $rsIBlock->Fetch()) {
	$iblockID = $arIBlock['ID'];
}

if ($iblockID === false) {
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		'4',
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = [
			'1' => 'X',
			'2' => 'R'
			// WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
			// WIZARD_PERSONNEL_DEPARTMENT_GROUP => "W",
		]
	);

	if ($iblockID < 1) {
		return;
	}

	$iblock = new CIBlock();
	$arFields = [
		'ACTIVE' => 'Y',
		'FIELDS' => [
			'SECTION_CODE' => [
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => [
					'UNIQUE' => 'Y',
					'TRANSLITERATION' => 'Y',
					'TRANS_LEN' => 50,
					'TRANS_CASE' => 'L',
					'TRANS_SPACE' => '_',
					'TRANS_OTHER' => '_',
					'TRANS_EAT' => 'Y',
					'USE_GOOGLE' => 'Y'
				]
			]
		],
		'CODE' => $iblockCode,
		'XML_ID' => $iblockCode
	];

	$iblock->Update($iblockID, $arFields);
} else {
	$arSites = [];
	$db_res = CIBlock::GetSite($iblockID);

	while ($res = $db_res->Fetch()) {
		$arSites[] = $res['LID'];
	}

	if (!in_array(WIZARD_SITE_ID, $arSites)) {
		$arSites[] = WIZARD_SITE_ID;
		$iblock = new CIBlock();
		$iblock->Update($iblockID, ['LID' => $arSites]);
	}
}

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/news/index.php', ['NEWS_IBLOCK_TYPE' => 'info']);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_leftBlock.php', ['NEWS_IBLOCK_TYPE' => 'info']);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/_index.php', ['NEWS_IBLOCK_TYPE' => 'info']);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/_index.php', ['NEWS_IBLOCK_ID' => $iblockID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/news/index.php', ['NEWS_IBLOCK_ID' => $iblockID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_leftBlock.php', ['NEWS_IBLOCK_ID' => $iblockID]);
