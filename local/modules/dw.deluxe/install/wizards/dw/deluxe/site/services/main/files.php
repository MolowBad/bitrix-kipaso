<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!defined('WIZARD_SITE_ID') || !defined('WIZARD_SITE_DIR')) {
	return;
}

function ___writeToAreasFile($path, $text)
{
	$fd = @fopen($path, 'wb');

	if (!$fd) {
		return false;
	}

	if (fwrite($fd, $text) === false) {
		fclose($fd);

		return false;
	}

	fclose($fd);

	if (!defined('BX_FILE_PERMISSIONS')) {
		return;
	}

	@chmod($path, BX_FILE_PERMISSIONS);
}

if (COption::GetOptionString('main', 'upload_dir') === '') {
	COption::SetOptionString('main', 'upload_dir', 'upload');
}

$templateID = $wizard->GetVar('wizTemplateID');

$publicPath = $templateID === 'dresscode' ? 'v1' : 'v2';

if (COption::GetOptionString('eshop', 'wizard_installed', 'N', WIZARD_SITE_ID) === 'N' || WIZARD_INSTALL_DEMO_DATA) {
	if (file_exists(WIZARD_ABSOLUTE_PATH . '/site/public/ru/' . $publicPath . '/')) {
		CopyDirFiles(
			WIZARD_ABSOLUTE_PATH . '/site/public/ru/' . $publicPath . '/',
			WIZARD_SITE_PATH,
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = false
		);
	}
}

if (COption::GetOptionString('eshop', 'wizard_installed', 'N', WIZARD_SITE_ID) === 'Y' && !WIZARD_INSTALL_DEMO_DATA) {
	return;
}

WizardServices::PatchHtaccess(WIZARD_SITE_PATH);

WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'news/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'catalog/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'collection/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'services/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'brands/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'search/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'personal/cart/order/', ['SITE_DIR' => WIZARD_SITE_DIR]);

WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'store/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'stores/', ['SITE_DIR' => WIZARD_SITE_DIR]);

WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'blog/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'sales/', ['SITE_DIR' => WIZARD_SITE_DIR]);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH . 'stock/', ['SITE_DIR' => WIZARD_SITE_DIR]);

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/index.php', ['SITE_DIR' => WIZARD_SITE_DIR]);
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/.section.php', ['SITE_DESCRIPTION' => htmlspecialcharsbx($wizard->GetVar('siteMetaDescription'))]);
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/.section.php', ['SITE_KEYWORDS' => htmlspecialcharsbx($wizard->GetVar('siteMetaKeywords'))]);

copy(WIZARD_THEME_ABSOLUTE_PATH . '/favicon.ico', WIZARD_SITE_PATH . 'favicon.ico');

$arUrlRewrite = [];

if (file_exists(WIZARD_SITE_ROOT_PATH . '/urlrewrite.php')) {
	include WIZARD_SITE_ROOT_PATH . '/urlrewrite.php';
}

$arNewUrlRewrite = [
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'personal/order/#',
		'RULE' => '',
		'ID' => 'bitrix:sale.personal.order',
		'PATH' => WIZARD_SITE_DIR . 'personal/order/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'collection/#',
		'RULE' => '',
		'ID' => 'bitrix:news',
		'PATH' => WIZARD_SITE_DIR . 'collection/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'services/#',
		'RULE' => '',
		'ID' => 'bitrix:catalog',
		'PATH' => WIZARD_SITE_DIR . 'services/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'catalog/#',
		'RULE' => '',
		'ID' => 'bitrix:catalog',
		'PATH' => WIZARD_SITE_DIR . 'catalog/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'brands/#',
		'RULE' => '',
		'ID' => 'bitrix:news',
		'PATH' => WIZARD_SITE_DIR . 'brands/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'stores/#',
		'RULE' => '',
		'ID' => 'bitrix:catalog.store',
		'PATH' => WIZARD_SITE_DIR . 'stores/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'store/#',
		'RULE' => '',
		'ID' => 'bitrix:catalog.store',
		'PATH' => WIZARD_SITE_DIR . 'store/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'news/#',
		'RULE' => '',
		'ID' => 'bitrix:news',
		'PATH' => WIZARD_SITE_DIR . 'news/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'blog/#',
		'RULE' => '',
		'ID' => 'bitrix:news',
		'PATH' => WIZARD_SITE_DIR . 'blog/index.php'
	],
	[
		'CONDITION' => '#^' . WIZARD_SITE_DIR . 'stock/#',
		'RULE' => '',
		'ID' => 'bitrix:news',
		'PATH' => WIZARD_SITE_DIR . 'stock/index.php'
	]
];

foreach ($arNewUrlRewrite as $arUrl) {
	if (in_array($arUrl, $arUrlRewrite)) {
		continue;
	}

	CUrlRewriter::Add($arUrl);
}
