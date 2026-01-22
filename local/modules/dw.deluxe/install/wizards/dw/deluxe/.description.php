<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!defined('WIZARD_DEFAULT_SITE_ID') && !empty($_REQUEST['wizardSiteID'])) {
	define('WIZARD_DEFAULT_SITE_ID', $_REQUEST['wizardSiteID']);
}

$arWizardDescription = [
	'NAME' => GetMessage('PORTAL_WIZARD_NAME'),
	'DESCRIPTION' => GetMessage('PORTAL_WIZARD_DESC'),
	'VERSION' => '1.0.4',
	'START_TYPE' => 'WINDOW',
	'WIZARD_TYPE' => 'INSTALL',
	'IMAGE' => '/images/' . LANGUAGE_ID . '/solution.png',
	'PARENT' => 'wizard_sol',
	'TEMPLATES' => [
		[
			'SCRIPT' => 'wizard_dw.php',
			'CLASS' => 'DwTemplate'
		]
	],
	'STEPS' => []
];

if (defined('WIZARD_DEFAULT_SITE_ID')) {
	if (LANGUAGE_ID === 'ru') {
		$arWizardDescription['STEPS'] = [
			'SelectTemplateStep',
			'SiteSettingsStep',
			'CatalogSettings',
			'ShopSettings',
			'PersonType',
			'PaySystem',
			'DataInstallStep',
			'FinishStep'

		];
	} else {
		$arWizardDescription['STEPS'] = [
			'SelectTemplateStep',
			'SiteSettingsStep',
			'CatalogSettings',
			'PaySystem',
			'DataInstallStep',
			'FinishStep'

		];
	}
} elseif (LANGUAGE_ID === 'ru') {
		$arWizardDescription['STEPS'] = [
			'SelectSiteStep',
			'SelectTemplateStep',
			'SiteSettingsStep',
			'CatalogSettings',
			'ShopSettings',
			'PersonType',
			'PaySystem',
			'DataInstallStep',
			'FinishStep'
		];
} else {
	$arWizardDescription['STEPS'] = [
		'SelectSiteStep',
		'SelectTemplateStep',
		'SiteSettingsStep',
		'CatalogSettings',
		'PaySystem',
		'DataInstallStep',
		'FinishStep'
	];
}
