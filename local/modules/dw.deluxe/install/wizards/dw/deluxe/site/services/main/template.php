<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!defined('WIZARD_TEMPLATE_ID')) {
	return;
}

$templateID = $wizard->GetVar('wizTemplateID');

$dresscodeV1Dir = $_SERVER['DOCUMENT_ROOT'] . BX_PERSONAL_ROOT . '/templates/dresscode';
$dresscodeV2Dir = $_SERVER['DOCUMENT_ROOT'] . BX_PERSONAL_ROOT . '/templates/dresscodeV2';

CopyDirFiles(
	$_SERVER['DOCUMENT_ROOT'] . WizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH . '/site') . '/dresscode',
	$dresscodeV1Dir,
	$rewrite = true,
	$recursive = true,
	$delete_after_copy = false,
	$exclude = ''
);

CopyDirFiles(
	$_SERVER['DOCUMENT_ROOT'] . WizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH . '/site') . '/dresscodeV2',
	$dresscodeV2Dir,
	$rewrite = true,
	$recursive = true,
	$delete_after_copy = false,
	$exclude = ''
);

$obSite = new CSite();
$obSite->Update(
	WIZARD_SITE_ID,
	[
		'ACTIVE' => 'Y',
		'TEMPLATE' => [
			[
				'CONDITION' => '',
				'SORT' => 1,
				'TEMPLATE' => $templateID
			]
		]
	]
);

$wizrdTemplateId = $templateID;

COption::SetOptionString('main', 'wizard_template_id', $wizrdTemplateId, false, WIZARD_SITE_ID);
