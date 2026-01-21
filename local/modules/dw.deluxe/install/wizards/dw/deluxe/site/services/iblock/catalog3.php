<?php

declare(strict_types=1);

use Bitrix\Catalog\MeasureRatioTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyIndex\Manager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog')) {
	return;
}

if (!WIZARD_INSTALL_DEMO_DATA) {
	return;
}

$shopLocalization = $wizard->GetVar('shopLocalization');

if ($_SESSION['WIZARD_CATALOG_IBLOCK_ID']) {
	$IBLOCK_CATALOG_ID = $_SESSION['WIZARD_CATALOG_IBLOCK_ID'];
	unset($_SESSION['WIZARD_CATALOG_IBLOCK_ID']);
}

if ($_SESSION['WIZARD_OFFERS_IBLOCK_ID']) {
	$IBLOCK_OFFERS_ID = $_SESSION['WIZARD_OFFERS_IBLOCK_ID'];
	unset($_SESSION['WIZARD_OFFERS_IBLOCK_ID']);
}

if ($IBLOCK_OFFERS_ID) {
	$iblockCodeOffers = 'deluxe_offers_' . WIZARD_SITE_ID;
	//IBlock fields
	$iblock = new CIBlock();
	$arFields = [
		'ACTIVE' => 'Y',
		'FIELDS' => [
			'IBLOCK_SECTION' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'ACTIVE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y'],
			'ACTIVE_FROM' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'ACTIVE_TO' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'SORT' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'NAME' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''],
			'PREVIEW_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95, 'DELETE_WITH_DETAIL' => 'N', 'UPDATE_WITH_DETAIL' => 'N']],
			'PREVIEW_TEXT_TYPE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'],
			'PREVIEW_TEXT' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'DETAIL_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95]],
			'DETAIL_TEXT_TYPE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'],
			'DETAIL_TEXT' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'XML_ID' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'CODE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '_', 'TRANS_OTHER' => '_', 'TRANS_EAT' => 'Y', 'USE_GOOGLE' => 'Y']],
			'TAGS' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'SECTION_NAME' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'SECTION_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95, 'DELETE_WITH_DETAIL' => 'N', 'UPDATE_WITH_DETAIL' => 'N']],
			'SECTION_DESCRIPTION_TYPE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => 'text'],
			'SECTION_DESCRIPTION' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'SECTION_DETAIL_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95]],
			'SECTION_XML_ID' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
			'SECTION_CODE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '_', 'TRANS_OTHER' => '_', 'TRANS_EAT' => 'Y', 'USE_GOOGLE' => 'Y']]

		],
		'CODE' => 'deluxe_offers',
		'XML_ID' => $iblockCodeOffers
	];
	$iblock->Update($IBLOCK_OFFERS_ID, $arFields);
}

if ($IBLOCK_CATALOG_ID) {
	$iblockCode = '92_' . WIZARD_SITE_ID;
	//IBlock fields
	$iblock = new CIBlock();
	$arFields = [
		'ACTIVE' => 'Y',
		'FIELDS' => ['IBLOCK_SECTION' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''], 'ACTIVE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y'], 'ACTIVE_FROM' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'ACTIVE_TO' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'SORT' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'NAME' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''], 'PREVIEW_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95, 'DELETE_WITH_DETAIL' => 'N', 'UPDATE_WITH_DETAIL' => 'N']], 'PREVIEW_TEXT_TYPE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'], 'PREVIEW_TEXT' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'DETAIL_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95]], 'DETAIL_TEXT_TYPE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'], 'DETAIL_TEXT' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'XML_ID' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'CODE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '_', 'TRANS_OTHER' => '_', 'TRANS_EAT' => 'Y', 'USE_GOOGLE' => 'Y']], 'TAGS' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'SECTION_NAME' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''], 'SECTION_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95, 'DELETE_WITH_DETAIL' => 'N', 'UPDATE_WITH_DETAIL' => 'N']], 'SECTION_DESCRIPTION_TYPE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'], 'SECTION_DESCRIPTION' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'SECTION_DETAIL_PICTURE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ['SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', 'METHOD' => 'resample', 'COMPRESSION' => 95]], 'SECTION_XML_ID' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''], 'SECTION_CODE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '_', 'TRANS_OTHER' => '_', 'TRANS_EAT' => 'Y', 'USE_GOOGLE' => 'Y']]],
		'CODE' => '92',
		'XML_ID' => $iblockCode
	];
	$iblock->Update($IBLOCK_CATALOG_ID, $arFields);

	if ($IBLOCK_OFFERS_ID) {
		$ID_SKU = CCatalog::LinkSKUIBlock($IBLOCK_CATALOG_ID, $IBLOCK_OFFERS_ID);

		$rsCatalogs = CCatalog::GetList(
			[],
			['IBLOCK_ID' => $IBLOCK_OFFERS_ID],
			false,
			false,
			['IBLOCK_ID']
		);

		if ($arCatalog = $rsCatalogs->Fetch()) {
			CCatalog::Update($IBLOCK_OFFERS_ID, ['PRODUCT_IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'SKU_PROPERTY_ID' => $ID_SKU]);
		} else {
			CCatalog::Add(['IBLOCK_ID' => $IBLOCK_OFFERS_ID, 'PRODUCT_IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'SKU_PROPERTY_ID' => $ID_SKU]);
		}

		Manager::dropIfExists($IBLOCK_CATALOG_ID);
		Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($IBLOCK_CATALOG_ID);

		$index = Manager::createIndexer($IBLOCK_CATALOG_ID);
		$index->startIndex();

		do {
			$res = $index->continueIndex(3600);
		} while ($res > 0);

		$index->endIndex();

		Bitrix\Iblock\PropertyIndex\Manager::checkAdminNotification();
	}

	if ($IBLOCK_OFFERS_ID > 0) {
		$count = ElementTable::getCount(
			[
				'=IBLOCK_ID' => $IBLOCK_OFFERS_ID,
				'=WF_PARENT_ELEMENT_ID' => null
			]
		);

		if ($count > 0) {
			$catalogReindex = new CCatalogProductAvailable('', 0, 0);
			$catalogReindex->initStep($count, 0, 0);
			$catalogReindex->setParams(['IBLOCK_ID' => $IBLOCK_OFFERS_ID]);
			$catalogReindex->run();
			unset($catalogReindex);
		}
	}

	if ($IBLOCK_OFFERS_ID > 0) {
		$iterator = ProductTable::getList(
			[
				'select' => ['ID'],
				'filter' => ['=IBLOCK_ELEMENT.IBLOCK_ID' => $IBLOCK_OFFERS_ID],
				'order' => ['ID' => 'ASC']
			]
		);

		while ($row = $iterator->fetch()) {
			$ratio = MeasureRatioTable::getList(
				[
					'select' => ['ID'],
					'filter' => ['=PRODUCT_ID' => $row['ID'], '=IS_DEFAULT' => 'Y']
				]
			)->fetch();

			if (!empty($ratio)) {
				continue;
			}

			$result = MeasureRatioTable::add(
				[
					'PRODUCT_ID' => $row['ID'],
					'RATIO' => 1,
					'IS_DEFAULT' => 'Y'
				]
			);
			unset($result);
		}

		unset($row, $iterator);
	}

	if ($IBLOCK_OFFERS_ID > 0) {
		$newStoreId = 0;

		if (isset($_SESSION['NEW_STORE_ID'])) {
			$newStoreId = (int) $_SESSION['NEW_STORE_ID'];
		}

		if ($newStoreId > 0) {
			CCatalogDocs::synchronizeStockQuantity($newStoreId, $IBLOCK_OFFERS_ID);
		}
	}

	if ($IBLOCK_CATALOG_ID > 0) {
		$count = ElementTable::getCount(
			[
				'=IBLOCK_ID' => $IBLOCK_CATALOG_ID,
				'=WF_PARENT_ELEMENT_ID' => null
			]
		);

		if ($count > 0) {
			$catalogReindex = new CCatalogProductAvailable('', 0, 0);
			$catalogReindex->initStep($count, 0, 0);
			$catalogReindex->setParams(['IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
			$catalogReindex->run();
			unset($catalogReindex);
		}
	}

	//user fields for sections
	$arLanguages = [];
	$rsLanguage = CLanguage::GetList($by, $order, []);

	while ($arLanguage = $rsLanguage->Fetch()) {
		$arLanguages[] = $arLanguage['LID'];
	}

	$arUserFields = ['UF_BROWSER_TITLE', 'UF_KEYWORDS', 'UF_META_DESCRIPTION', 'UF_DESC', 'UF_IMAGES', 'UF_POPULAR', 'UF_MARKER', 'UF_PHOTO', 'UF_BANNER', 'UF_BANNER_LINK'];

	foreach ($arUserFields as $userField) {
		$arLabelNames = [];

		foreach ($arLanguages as $languageID) {
			WizardServices::IncludeServiceLang('property_names.php', $languageID);
			$arLabelNames[$languageID] = GetMessage($userField);
		}

		$arProperty['EDIT_FORM_LABEL'] = $arLabelNames;
		$arProperty['LIST_COLUMN_LABEL'] = $arLabelNames;
		$arProperty['LIST_FILTER_LABEL'] = $arLabelNames;

		$dbRes = CUserTypeEntity::GetList([], ['ENTITY_ID' => 'IBLOCK_' . $IBLOCK_CATALOG_ID . '_SECTION', 'FIELD_NAME' => $userField]);

		if ($arRes = $dbRes->Fetch()) {
			$userType = new CUserTypeEntity();
			$userType->Update($arRes['ID'], $arProperty);
		}

		//if($ex = $APPLICATION->GetException())
			//$strError = $ex->GetString();
	}

	//demo discount
	$dbDiscount = CCatalogDiscount::GetList([], ['SITE_ID' => WIZARD_SITE_ID]);

	if (!$dbDiscount->Fetch()) {
		if (CModule::IncludeModule('iblock')) {
			$dbSect = CIBlockSection::GetList([], ['IBLOCK_TYPE' => 'catalog', 'IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'CODE' => 'underwear', 'IBLOCK_SITE_ID' => WIZARD_SITE_ID]);

			if ($arSect = $dbSect->Fetch()) {
				$sofasSectId = $arSect['ID'];
			}
		}

		$dbSite = CSite::GetByID(WIZARD_SITE_ID);

		if ($arSite = $dbSite->Fetch()) {
			$lang = $arSite['LANGUAGE_ID'];
		}

		$defCurrency = 'EUR';

		if ($lang === 'ru') {
			$defCurrency = 'RUB';
		} elseif ($lang === 'en') {
			$defCurrency = 'USD';
		}

		$arF = [
			'SITE_ID' => WIZARD_SITE_ID,
			'ACTIVE' => 'Y',
			//"ACTIVE_FROM" => ConvertTimeStamp(mktime(0,0,0,12,15,2011), "FULL"),
			//"ACTIVE_TO" => ConvertTimeStamp(mktime(0,0,0,03,15,2012), "FULL"),
			'RENEWAL' => 'N',
			'NAME' => GetMessage('WIZ_DISCOUNT'),
			'SORT' => 100,
			'MAX_DISCOUNT' => 0,
			'VALUE_TYPE' => 'P',
			'VALUE' => 10,
			'CURRENCY' => $defCurrency,
			'CONDITIONS' => [
				'CLASS_ID' => 'CondGroup',
				'DATA' => ['All' => 'OR', 'True' => 'True'],
				'CHILDREN' => [['CLASS_ID' => 'CondIBSection', 'DATA' => ['logic' => 'Equal', 'value' => $sofasSectId]]]
			]
		];
		CCatalogDiscount::Add($arF);
	}

//precet
	$dbProperty = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'CODE' => 'SALELEADER']);
	$arFields = [];

	while ($arProperty = $dbProperty->GetNext()) {
		$arFields['find_el_property_' . $arProperty['ID']] = '';
	}

	$dbProperty = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'CODE' => 'NEWPRODUCT']);

	while ($arProperty = $dbProperty->GetNext()) {
		$arFields['find_el_property_' . $arProperty['ID']] = '';
	}

	$dbProperty = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'CODE' => 'SPECIALOFFER']);

	while ($arProperty = $dbProperty->GetNext()) {
		$arFields['find_el_property_' . $arProperty['ID']] = '';
	}

	include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/interface/admin_lib.php';
	CAdminFilter::AddPresetToBase(
		[
			'NAME' => GetMessage('WIZ_PRECET'),
			'FILTER_ID' => 'tbl_product_admin_' . md5($iblockType . '.' . $IBLOCK_CATALOG_ID) . '_filter',
			'LANGUAGE_ID' => $lang,
			'FIELDS' => $arFields
		]
	);
	CUserOptions::SetOption('filter', 'tbl_product_admin_' . md5($iblockType . '.' . $IBLOCK_CATALOG_ID) . '_filter', ['rows' => 'find_el_name, find_el_active, find_el_timestamp_from, find_el_timestamp_to'], true);

	CAdminFilter::SetDefaultRowsOption('tbl_product_admin_' . md5($iblockType . '.' . $IBLOCK_CATALOG_ID) . '_filter', ['miss-0', 'IBEL_A_F_PARENT']);

//delete 1c props
	$arPropsToDelete = ['CML2_TAXES', 'CML2_BASE_UNIT', 'CML2_TRAITS', 'CML2_ATTRIBUTES', 'CML2_ARTICLE', 'CML2_BAR_CODE', 'CML2_FILES', 'CML2_MANUFACTURER', 'CML2_PICTURES'];

	foreach ($arPropsToDelete as $code) {
		$dbProperty = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'XML_ID' => $code]);

		if ($arProperty = $dbProperty->GetNext()) {
			CIBlockProperty::Delete($arProperty['ID']);
		}

		if (!$IBLOCK_OFFERS_ID) {
			continue;
		}

		$dbProperty = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IBLOCK_OFFERS_ID, 'XML_ID' => $code]);

		if (!$arProperty = $dbProperty->GetNext()) {
			continue;
		}

		CIBlockProperty::Delete($arProperty['ID']);
	}

	$IBLOCK_CATALOG_TYPE = 'catalog';

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/_index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/catalog/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/search/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/.left.menu_ext.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sale/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/wishlist/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/recommend/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/popular/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/new/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/discount/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/compare/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/.left.menu_ext.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/brands/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/collection/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/blog/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/news/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/stock/index.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/_index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/catalog/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/search/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/.left.menu_ext.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sale/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/wishlist/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/recommend/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/popular/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/new/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/discount/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/compare/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/.left.menu_ext.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/brands/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/collection/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/blog/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/news/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/stock/index.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine2.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine2.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine3.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine3.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);


	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine4.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_searchLine4.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);


	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_footerTabs.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_footerTabs.php', ['CATALOG_IBLOCK_TYPE' => $IBLOCK_CATALOG_TYPE]);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/settings.php', ['CATALOG_IBLOCK_ID' => $IBLOCK_CATALOG_ID]);


	//get dynamic property value
	$specialOffersMap = array_flip(
		[
			'new' => GetMessage('WIZ_SPECIAL_OFFERS_NEW'),
			'popular' => GetMessage('WIZ_SPECIAL_OFFERS_POPULAR'),
			'sale' => GetMessage('WIZ_SPECIAL_OFFERS_SALE'),
			'recommend' => GetMessage('WIZ_SPECIAL_OFFERS_RECOMMEND'),
			'discount' => GetMessage('WIZ_SPECIAL_OFFERS_DISCOUNT')
		]
	);

	$iterator = CIBlockPropertyEnum::GetList(
		[],
		[
			'IBLOCK_ID' => $IBLOCK_CATALOG_ID,
			'CODE' => 'OFFERS'
		]
	);

	while ($enum = $iterator->Fetch()) {
		if (!$target = $specialOffersMap[$enum['VALUE']]) {
			continue;
		}

		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/' . $target . '/index.php', [strtoupper($target) . '_ENUM_VALUE_ID' => $enum['ID']]);
	}

	// #PROP VALUES
	$COUNTER = 0;
	$CATALOG_PROP_VALUES = 'array(';

	$property_enums = CIBlockPropertyEnum::GetList(['DEF' => 'DESC', 'SORT' => 'ASC'], ['IBLOCK_ID' => $IBLOCK_CATALOG_ID, 'CODE' => 'OFFERS']);

	while ($enum_fields = $property_enums->GetNext()) {
		$CATALOG_PROP_VALUES .= $COUNTER . ' => "' . $enum_fields['ID'] . '",';
		$COUNTER++;
	}

	$CATALOG_PROP_VALUES .= ')';
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/_index.php', ['CATALOG_PROP_VALUES' => $CATALOG_PROP_VALUES]);
}

if (CModule::IncludeModule('form')) {
	WizardServices::IncludeServiceLang('web_form_names.php', 'ru');

	$rsCheaperForm = CForm::GetBySID('DW_CHEAPER_FORM');

	if ($arCheaperForm = $rsCheaperForm->Fetch()) {
		$CHEAPER_FORM_ID = $arCheaperForm['ID'];
	} else {
		// Cheaper Form

		$arFields = [
			'NAME'              => GetMessage('C1_CHEAPER'),
			'SID'               => 'DW_CHEAPER_FORM',
			'C_SORT'            => 300,
			'BUTTON'            => GetMessage('C1_SEND'),
			'DESCRIPTION'       => '',
			'DESCRIPTION_TYPE'  => 'text',
			'STAT_EVENT1'       => 'form',
			'arSITE'            => ['s1', 's2', 's3', 's4', 's5'],
			'arMENU'            => ['ru' => GetMessage('C1_CHEAPER'), 'en' => 'Cheaper Form'],
			'arGROUP'           => ['']
		];

		$CHEAPER_FORM_ID = $NEW_ID = CForm::Set($arFields);

		if ($NEW_ID > 0) {
			$arTemplates = CForm::SetMailTemplate($NEW_ID, 'Y', 'DW_CHEAPER_FORM', $NEW_ID, false);
			CForm::Set(
				['arMAIL_TEMPLATE' => ['ID' => $arTemplates[0]['ID']], $NEW_ID]
			);

			$formFileds = [];

			//name
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_NAME'),
					'ADDITIONAL'          => 'N',
					'SID'                 => 'NAME',
					'C_SORT'              => 100,
					'REQUIRED'            => 'N',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'FILTER_TITLE'        => GetMessage('C1_NAME'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_NAME'),
					// "arIMAGE"             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH."/files/form-images/telephone.png"),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//telephone
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_PHONE'),
					'SID'                 => 'TELEPHONE',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 100,
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'FILTER_TITLE'        => GetMessage('C1_PHONE'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_PHONE'),
					// "arIMAGE"             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH."/files/form-images/telephone.png"),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//email
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_MAIL'),
					'SID'                 => 'EMAIL',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 100,
					'REQUIRED'            => 'N',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'email',
					'FILTER_TITLE'        => GetMessage('C1_MAIL'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_MAIL'),
					// "arIMAGE"             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH."/files/form-images/telephone.png"),
					'arFILTER_FIELD'      => ['email']
				]
			];

			//product name
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_PRODUCT_NAME'),
					'SID'                 => 'PRODUCT_NAME',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 100,
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'TITLE_TYPE'		  => 'html',
					'FILTER_TITLE'        => GetMessage('C1_PRODUCT_NAME'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_PRODUCT_NAME'),
					// "arIMAGE"             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH."/files/form-images/telephone.png"),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//link
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_LINK'),
					'SID'                 => 'LINK',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 100,
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'url',
					'FILTER_TITLE'        => GetMessage('C1_LINK'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_LINK'),
					// "arIMAGE"             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH."/files/form-images/telephone.png"),
					'arFILTER_FIELD'      => ['url']
				]
			];

			foreach ($formFileds as $in => $arNextFormField) {
				$NEW_FIELD_ID = CFormField::Set($arNextFormField['FORM_FIELDS']);

				if (empty($NEW_FIELD_ID) || $arNextFormField['FORM_FIELDS']['FIELD_TYPE'] === 'dropdown') {
					continue;
				}

				if (empty($arNextFormField['FORM_FIELDS']['TITLE_TYPE'])) {
					$arNextFormField['FORM_FIELDS']['TITLE_TYPE'] = 'text';
				}

				$arFields = [
					'QUESTION_ID'   => $NEW_FIELD_ID,
					'MESSAGE'       => ' ',
					'C_SORT'        => 100,
					'ACTIVE'        => 'Y',
					'FIELD_TYPE'    => $arNextFormField['FORM_FIELDS']['FIELD_TYPE'],
					'TITLE_TYPE'    => $arNextFormField['FORM_FIELDS']['TITLE_TYPE'],
					'FIELD_WIDTH'   => '40'
				];
				$NEW_ANSWER_ID = CFormAnswer::Set($arFields);
			}

			$arFields = [
				'FORM_ID'             => $NEW_ID,
				'C_SORT'              => 100,
				'ACTIVE'              => 'Y',
				'TITLE'               => GetMessage('C1_PUBLICATE'),
				'DESCRIPTION'         => GetMessage('C1_STATUS'),
				'CSS'                 => 'statusgreen',
				'HANDLER_OUT'         => '',
				'HANDLER_IN'          => '',
				'DEFAULT_VALUE'       => 'Y',
				'arPERMISSION_VIEW'   => [2],
				'arPERMISSION_MOVE'   => [2],
				'arPERMISSION_EDIT'   => [2],
				'arPERMISSION_DELETE' => [2]
			];

			$NEW_STATUS_ID = CFormStatus::Set($arFields);
		}
	}

	$rsCallbackForm = CForm::GetBySID('DW_CALLBACK_FORM');

	if ($arCallbackForm = $rsCallbackForm->Fetch()) {
		$CALLBACK_FORM_ID = $arCallbackForm['ID'];
	} else {
		// callback Form

		$arFields = [
			'NAME'              => GetMessage('C1_CALL'),
			'SID'               => 'DW_CALLBACK_FORM',
			'C_SORT'            => 300,
			'BUTTON'            => GetMessage('C1_SEND'),
			'DESCRIPTION'       => GetMessage('C1_CALLBACK_MESSAGE'),
			'DESCRIPTION_TYPE'  => 'text',
			'STAT_EVENT1'       => 'form',
			'arSITE'            => ['s1', 's2', 's3', 's4', 's5'],
			'arMENU'            => ['ru' => GetMessage('C1_CALL'), 'en' => 'Callback Form'],
			'arGROUP'           => ['']
		];

		$CALLBACK_FORM_ID = $NEW_ID = CForm::Set($arFields);

		if ($NEW_ID > 0) {
			$arTemplates = CForm::SetMailTemplate($NEW_ID, 'Y', 'DW_CALLBACK_FORM', $NEW_ID, false);
			CForm::Set(
				['arMAIL_TEMPLATE' => ['ID' => $arTemplates[0]['ID']], $NEW_ID]
			);

			$formFileds = [];

			//telephone
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_PHONE'),
					'SID'                 => 'TELEPHONE',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 100,
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'FILTER_TITLE'        => GetMessage('C1_PHONE'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_PHONE'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/telephone.png'),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//name
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_NAME'),
					'ADDITIONAL'          => 'N',
					'SID'                 => 'NAME',
					'C_SORT'              => 100,
					'REQUIRED'            => 'N',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'FILTER_TITLE'        => GetMessage('C1_NAME'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_NAME'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/name.png'),
					'arFILTER_FIELD'      => ['text']
				]
			];

			foreach ($formFileds as $in => $arNextFormField) {
				$NEW_FIELD_ID = CFormField::Set($arNextFormField['FORM_FIELDS']);

				if (empty($NEW_FIELD_ID) || $arNextFormField['FORM_FIELDS']['FIELD_TYPE'] === 'dropdown') {
					continue;
				}

				$arFields = [
					'QUESTION_ID'   => $NEW_FIELD_ID,
					'MESSAGE'       => ' ',
					'C_SORT'        => 100,
					'ACTIVE'        => 'Y',
					'FIELD_TYPE'    => $arNextFormField['FORM_FIELDS']['FIELD_TYPE'],
					'FIELD_WIDTH'   => '40'
				];
				$NEW_ANSWER_ID = CFormAnswer::Set($arFields);
			}

			$arFields = [
				'FORM_ID'             => $NEW_ID,
				'C_SORT'              => 100,
				'ACTIVE'              => 'Y',
				'TITLE'               => GetMessage('C1_PUBLICATE'),
				'DESCRIPTION'         => GetMessage('C1_STATUS'),
				'CSS'                 => 'statusgreen',
				'HANDLER_OUT'         => '',
				'HANDLER_IN'          => '',
				'DEFAULT_VALUE'       => 'Y',
				'arPERMISSION_VIEW'   => [2],
				'arPERMISSION_MOVE'   => [2],
				'arPERMISSION_EDIT'   => [2],
				'arPERMISSION_DELETE' => [2]
			];

			$NEW_STATUS_ID = CFormStatus::Set($arFields);
		}
	}

	$arFields = [
		'NAME'              => GetMessage('C1_FEEDBACK'),
		'SID'               => 'DW_FEEDBACK_FORM',
		'C_SORT'            => 300,
		'BUTTON'            => GetMessage('C1_SEND'),
		'DESCRIPTION'       => GetMessage('C1_FEEDBACK_MESSAGE'),
		'DESCRIPTION_TYPE'  => 'text',
		'STAT_EVENT1'       => 'form',
		'arSITE'            => ['s1', 's2', 's3', 's4', 's5'],
		'arMENU'            => ['ru' => GetMessage('C1_FEEDBACK'), 'en' => 'Feedback Form'],
		'arGROUP'           => ['']
	];

	$rsFeedbackForm = CForm::GetBySID('DW_FEEDBACK_FORM');

	if ($arFeedbackForm = $rsFeedbackForm->Fetch()) {
		$FEEDBACK_FORM_ID = $arFeedbackForm['ID'];
	} else {
		$FEEDBACK_FORM_ID = $NEW_ID = CForm::Set($arFields);

		if ($NEW_ID > 0) {
			$arTemplates = CForm::SetMailTemplate($NEW_ID, 'Y', 'DW_FEEDBACK_FORM', $NEW_ID, false);
			CForm::Set(
				['arMAIL_TEMPLATE' => ['ID' => $arTemplates[0]['ID']], $NEW_ID]
			);

			$formFileds = [];

			//name
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_NAME'),
					'SID'                 => 'NAME',
					'C_SORT'              => 1,
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'ADDITIONAL'          => 'N',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'FILTER_TITLE'        => GetMessage('C1_NAME'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_NAME'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/name.png'),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//email
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_MAIL'),
					'SID'                 => 'EMAIL',
					'C_SORT'              => 10,
					'ADDITIONAL'          => 'N',
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'email',
					'FILTER_TITLE'        => GetMessage('C1_MAIL'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_MAIL'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/email.png'),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//telephone
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_PHONE'),
					'SID'                 => 'TELEPHONE',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 10,
					'REQUIRED'            => 'N',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'text',
					'FILTER_TITLE'        => GetMessage('C1_PHONE'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_PHONE'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/telephone.png'),
					'arFILTER_FIELD'      => ['text']
				]
			];

			//theme
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_THEME'),
					'SID'                 => 'THEME',
					'ADDITIONAL'          => 'N',
					'C_SORT'              => 100,
					'REQUIRED'            => 'N',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'dropdown',
					'FILTER_TITLE'        => GetMessage('C1_THEME'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_THEME'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/theme.png'),
					'arFILTER_FIELD'      => ['dropdown'],
					'arANSWER'            => [
						[
							'FIELD_TYPE' => 'dropdown',
							'MESSAGE' => GetMessage('C1_QUESTION'),
							'FIELD_PARAM' => 'checked',
							'C_SORT' => 200,
							'ACTIVE' => 'Y'
						],
						[
							'FIELD_TYPE' => 'dropdown',
							'MESSAGE' => GetMessage('C1_REQUEST'),
							'C_SORT' => 200,
							'ACTIVE' => 'Y'
						],
						[
							'FIELD_TYPE' => 'dropdown',
							'MESSAGE' => GetMessage('C1_QUESTION_MAGAZINE'),
							'C_SORT' => 200,
							'ACTIVE' => 'Y'
						],
						[
							'FIELD_TYPE' => 'dropdown',
							'MESSAGE' => GetMessage('C1_ABUSE'),
							'C_SORT' => 200,
							'ACTIVE' => 'Y'
						]
					]
				]
			];

			//message
			$formFileds[] = [
				'FORM_FIELDS' => [
					'FORM_ID'             => $NEW_ID,
					'ACTIVE'              => 'Y',
					'TITLE'               => GetMessage('C1_MESSAGE'),
					'TITLE_TYPE'          => 'text',
					'ADDITIONAL'          => 'N',
					'SID'                 => 'MESSAGE',
					'C_SORT'              => 1000,
					'REQUIRED'            => 'Y',
					'IN_RESULTS_TABLE'    => 'Y',
					'IN_EXCEL_TABLE'      => 'Y',
					'FIELD_TYPE'          => 'textarea',
					'FILTER_TITLE'        => GetMessage('C1_MESSAGE'),
					'RESULTS_TABLE_TITLE' => GetMessage('C1_MESSAGE'),
					'arIMAGE'             => CFile::MakeFileArray(WIZARD_RELATIVE_PATH . '/files/form-images/message.png'),
					'arFILTER_FIELD'      => ['text']
				]
			];

			foreach ($formFileds as $in => $arNextFormField) {
				$NEW_FIELD_ID = CFormField::Set($arNextFormField['FORM_FIELDS']);

				if (empty($NEW_FIELD_ID) || $arNextFormField['FORM_FIELDS']['FIELD_TYPE'] === 'dropdown') {
					continue;
				}

				$arFields = [
					'QUESTION_ID'   => $NEW_FIELD_ID,
					'MESSAGE'       => ' ',
					'C_SORT'        => 100,
					'ACTIVE'        => 'Y',
					'FIELD_TYPE'    => $arNextFormField['FORM_FIELDS']['FIELD_TYPE'],
					'FIELD_WIDTH'   => '40'
				];
				$NEW_ANSWER_ID = CFormAnswer::Set($arFields);
			}

			$arFields = [
				'FORM_ID'             => $NEW_ID,
				'C_SORT'              => 100,
				'ACTIVE'              => 'Y',
				'TITLE'               => GetMessage('C1_PUBLICATE'),
				'NAME'				  => GetMessage('C1_PUBLICATE'),
				'DESCRIPTION'         => GetMessage('C1_STATUS'),
				'CSS'                 => 'statusgreen',
				'HANDLER_OUT'         => '',
				'HANDLER_IN'          => '',
				'DEFAULT_VALUE'       => 'Y',
				'CODE'				  => 'FORM_FEEDBACK_STATUS_' . $NEW_ID,
				'arPERMISSION_VIEW'   => [2],
				'arPERMISSION_MOVE'   => [2],
				'arPERMISSION_EDIT'   => [2],
				'arPERMISSION_DELETE' => [2]
			];

			$NEW_STATUS_ID = CFormStatus::Set($arFields);
		}
	}
}

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/callback/index.php', ['FEEDBACK_FORM_ID' => $FEEDBACK_FORM_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/about/contacts/index.php', ['FEEDBACK_FORM_ID' => $FEEDBACK_FORM_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_phone.php', ['CALLBACK_FORM_ID' => $CALLBACK_FORM_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/sect_callBack.php', ['CALLBACK_FORM_ID' => $CALLBACK_FORM_ID]);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH . '/catalog/index.php', ['CHEAPER_FORM_ID' => $CHEAPER_FORM_ID]);
