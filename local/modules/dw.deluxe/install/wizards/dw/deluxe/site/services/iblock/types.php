<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!CModule::IncludeModule('iblock')) {
	return;
}

if (COption::GetOptionString('eshop', 'wizard_installed', 'N', WIZARD_SITE_ID) === 'Y' && !WIZARD_INSTALL_DEMO_DATA) {
	return;
}

$arTypes = [
	[
		'ID' => '1c',
		'SECTIONS' => 'Y',
		'IN_RSS' => 'Y',
		'SORT' => 200,
		'LANG' => []
	],
	[
		'ID' => 'catalog',
		'SECTIONS' => 'Y',
		'IN_RSS' => 'N',
		'SORT' => 100,
		'LANG' => []
	],
	[
		'ID' => 'offers',
		'SECTIONS' => 'Y',
		'IN_RSS' => 'N',
		'SORT' => 200,
		'LANG' => []
	],
	[
		'ID' => 'info',
		'SECTIONS' => 'Y',
		'IN_RSS' => 'Y',
		'SORT' => 200,
		'LANG' => []
	],
	[
		'ID' => 'service',
		'SECTIONS' => 'Y',
		'IN_RSS' => 'N',
		'SORT' => 400,
		'LANG' => []
	],
	[
		'ID' => 'slider',
		'SECTIONS' => 'N',
		'IN_RSS' => 'N',
		'SORT' => 400,
		'LANG' => []
	]
];

$arLanguages = [];
$rsLanguage = CLanguage::GetList($by, $order, []);

while ($arLanguage = $rsLanguage->Fetch()) {
	$arLanguages[] = $arLanguage['LID'];
}

$iblockType = new CIBlockType();

foreach ($arTypes as $arType) {
	$dbType = CIBlockType::GetList([], ['=ID' => $arType['ID']]);

	if ($dbType->Fetch()) {
		continue;
	}

	foreach ($arLanguages as $languageID) {
		WizardServices::IncludeServiceLang('type.php', $languageID);

		$code = strtoupper($arType['ID']);
		$arType['LANG'][$languageID]['NAME'] = GetMessage($code . '_TYPE_NAME');
		$arType['LANG'][$languageID]['ELEMENT_NAME'] = GetMessage($code . '_ELEMENT_NAME');

		if ($arType['SECTIONS'] !== 'Y') {
			continue;
		}

		$arType['LANG'][$languageID]['SECTION_NAME'] = GetMessage($code . '_SECTION_NAME');
	}

	$iblockType->Add($arType);
}

COption::SetOptionString('iblock', 'combined_list_mode', 'Y');
