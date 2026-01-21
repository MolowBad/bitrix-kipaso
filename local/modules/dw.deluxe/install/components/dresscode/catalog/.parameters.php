<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */
/** @global CUserTypeManager $USER_FIELD_MANAGER */
use Bitrix\Main\Loader,
	Bitrix\Main\ModuleManager,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Currency;

global $USER_FIELD_MANAGER;

if(!Loader::includeModule("iblock") || !Loader::includeModule("subscribe") || !Loader::includeModule("sale")){
	return;
}

//vars
$arRubricList = array();

//set filter
$arRubricFilter = array("ACTIVE" => "Y", "VISIBLE" => "Y");

//get rubrics
$rsRubric = CRubric::GetList(array("SORT" => "ASC", "NAME" => "ASC"), $arRubricFilter);
while($arRubric = $rsRubric->GetNext()){
	$arRubricList[$arRubric["ID"]] = $arRubric["NAME"]." [".$arRubric["LID"]."]";
}

$catalogIncluded = Loader::includeModule('catalog');

$usePropertyFeatures = Iblock\Model\PropertyFeature::isEnabledFeatures();

$iblockExists = (!empty($arCurrentValues['IBLOCK_ID']) && (int)$arCurrentValues['IBLOCK_ID'] > 0);

$compatibleMode = !(isset($arCurrentValues['COMPATIBLE_MODE']) && $arCurrentValues['COMPATIBLE_MODE'] === 'N');

$arIBlockType = CIBlockParameters::GetIBlockTypes();

$offersIblock = array();
if ($catalogIncluded)
{
	$iterator = Catalog\CatalogIblockTable::getList(array(
		'select' => array('IBLOCK_ID'),
		'filter' => array('!=PRODUCT_IBLOCK_ID' => 0)
	));
	while ($row = $iterator->fetch())
		$offersIblock[$row['IBLOCK_ID']] = true;
	unset($row, $iterator);
}

$arIBlock = array();
$iblockFilter = (
	!empty($arCurrentValues['IBLOCK_TYPE'])
	? array('TYPE' => $arCurrentValues['IBLOCK_TYPE'], 'ACTIVE' => 'Y')
	: array('ACTIVE' => 'Y')
);
$rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
while ($arr = $rsIBlock->Fetch())
{
	$id = (int)$arr['ID'];
	if (isset($offersIblock[$id]))
		continue;
	$arIBlock[$id] = '['.$id.'] '.$arr['NAME'];
}
unset($id, $arr, $rsIBlock, $iblockFilter);
unset($offersIblock);

$arProperty = array();
$arProperty_N = array();
$arProperty_X = array();
$arProperty_F = array();
if ($iblockExists)
{
	$propertyIterator = Iblock\PropertyTable::getList(array(
		'select' => array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'LINK_IBLOCK_ID', 'USER_TYPE', 'SORT'),
		'filter' => array('=IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'], '=ACTIVE' => 'Y'),
		'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
	));
	while ($property = $propertyIterator->fetch())
	{
		$propertyCode = (string)$property['CODE'];
		if ($propertyCode == '')
			$propertyCode = $property['ID'];
		$propertyName = '['.$propertyCode.'] '.$property['NAME'];

		if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE)
		{
			$arProperty[$propertyCode] = $propertyName;

			if ($property['MULTIPLE'] == 'Y')
				$arProperty_X[$propertyCode] = $propertyName;
			elseif ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_LIST)
				$arProperty_X[$propertyCode] = $propertyName;
			elseif ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_ELEMENT && (int)$property['LINK_IBLOCK_ID'] > 0)
				$arProperty_X[$propertyCode] = $propertyName;
		}
		else
		{
			if ($property['MULTIPLE'] == 'N')
				$arProperty_F[$propertyCode] = $propertyName;
		}

		if ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_NUMBER)
			$arProperty_N[$propertyCode] = $propertyName;
	}
	unset($propertyCode, $propertyName, $property, $propertyIterator);
}
$arProperty_LNS = $arProperty;

$arIBlock_LINK = array();
$iblockFilter = (
	!empty($arCurrentValues['LINK_IBLOCK_TYPE'])
	? array('TYPE' => $arCurrentValues['LINK_IBLOCK_TYPE'], 'ACTIVE' => 'Y')
	: array('ACTIVE' => 'Y')
);
$rsIblock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
while ($arr = $rsIblock->Fetch())
	$arIBlock_LINK[$arr['ID']] = '['.$arr['ID'].'] '.$arr['NAME'];
unset($iblockFilter);

$arProperty_LINK = array();
if (!empty($arCurrentValues['LINK_IBLOCK_ID']) && (int)$arCurrentValues['LINK_IBLOCK_ID'] > 0)
{
	$propertyIterator = Iblock\PropertyTable::getList(array(
		'select' => array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'LINK_IBLOCK_ID', 'USER_TYPE', 'SORT'),
		'filter' => array('=IBLOCK_ID' => $arCurrentValues['LINK_IBLOCK_ID'], '=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_ELEMENT, '=ACTIVE' => 'Y'),
		'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
	));
	while ($property = $propertyIterator->fetch())
	{
		$propertyCode = (string)$property['CODE'];
		if ($propertyCode == '')
			$propertyCode = $property['ID'];
		$arProperty_LINK[$propertyCode] = '['.$propertyCode.'] '.$property['NAME'];
	}
	unset($propertyCode, $property, $propertyIterator);
}

$arUserFields_S = array("-"=>" ");
$arUserFields_F = array("-"=>" ");
if ($iblockExists)
{
	$arUserFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_'.$arCurrentValues['IBLOCK_ID'].'_SECTION', 0, LANGUAGE_ID);
	foreach ($arUserFields as $FIELD_NAME => $arUserField)
	{
		$arUserField['LIST_COLUMN_LABEL'] = (string)$arUserField['LIST_COLUMN_LABEL'];
		$arProperty_UF[$FIELD_NAME] = $arUserField['LIST_COLUMN_LABEL'] ? '['.$FIELD_NAME.']'.$arUserField['LIST_COLUMN_LABEL'] : $FIELD_NAME;
		if ($arUserField["USER_TYPE"]["BASE_TYPE"] == "string")
			$arUserFields_S[$FIELD_NAME] = $arProperty_UF[$FIELD_NAME];
		if ($arUserField["USER_TYPE"]["BASE_TYPE"] == "file" && $arUserField['MULTIPLE'] == 'N')
			$arUserFields_F[$FIELD_NAME] = $arProperty_UF[$FIELD_NAME];
	}
	unset($arUserFields);
}

$offers = false;
$arProperty_Offers = array();
$arProperty_OffersWithoutFile = array();
if ($catalogIncluded && $iblockExists)
{
	$offers = CCatalogSku::GetInfoByProductIBlock($arCurrentValues['IBLOCK_ID']);
	if (!empty($offers))
	{
		$propertyIterator = Iblock\PropertyTable::getList(array(
			'select' => array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'LINK_IBLOCK_ID', 'USER_TYPE', 'SORT'),
			'filter' => array('=IBLOCK_ID' => $offers['IBLOCK_ID'], '=ACTIVE' => 'Y', '!=ID' => $offers['SKU_PROPERTY_ID']),
			'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
		));
		while ($property = $propertyIterator->fetch())
		{
			$propertyCode = (string)$property['CODE'];
			if ($propertyCode == '')
				$propertyCode = $property['ID'];
			$propertyName = '['.$propertyCode.'] '.$property['NAME'];

			$arProperty_Offers[$propertyCode] = $propertyName;
			if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE)
				$arProperty_OffersWithoutFile[$propertyCode] = $propertyName;
		}
		unset($propertyCode, $propertyName, $property, $propertyIterator);
	}
}

$arSort = CIBlockParameters::GetElementSortFields(
	array('SHOWS', 'SORT', 'TIMESTAMP_X', 'NAME', 'ID', 'ACTIVE_FROM', 'ACTIVE_TO'),
	array('KEY_LOWERCASE' => 'Y')
);

$arPrice = array();
if ($catalogIncluded)
{
	$arSort = array_merge($arSort, CCatalogIBlockParameters::GetCatalogSortFields());
	if (isset($arSort['CATALOG_AVAILABLE']))
		unset($arSort['CATALOG_AVAILABLE']);
	$arPrice = CCatalogIBlockParameters::getPriceTypesList();
}
else
{
	$arPrice = $arProperty_N;
}

$arAscDesc = array(
	"asc" => GetMessage("IBLOCK_SORT_ASC"),
	"desc" => GetMessage("IBLOCK_SORT_DESC"),
);

$arComponentParameters = array(
	"GROUPS" => array(
		"TAGS" => array(
			"NAME" => GetMessage("T_CATALOG_TAGS_SETTINGS"),
		),
		"FILTER_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_FILTER_SETTINGS"),
		),
		"REVIEW_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_REVIEW_SETTINGS"),
		),
		"ACTION_SETTINGS" => array(
			"NAME" => GetMessage('IBLOCK_ACTIONS')
		),
		"PRICES" => array(
			"NAME" => GetMessage("IBLOCK_PRICES"),
		),
		"SEARCH_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_SEARCH_SETTINGS"),
		),
		"TOP_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_TOP_SETTINGS"),
		),
		"SECTIONS_SETTINGS" => array(
			"NAME" => GetMessage("CP_BC_SECTIONS_SETTINGS"),
		),
		"LIST_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_SETTINGS"),
		),
		"DETAIL_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_DETAIL_SETTINGS"),
		),
		"LINK" => array(
			"NAME" => GetMessage("IBLOCK_LINK"),
		),
		"ALSO_BUY_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_ALSO_BUY_SETTINGS"),
		),
		"GIFTS_SETTINGS" => array(
			"NAME" => GetMessage("SALE_T_DESC_GIFTS_SETTINGS"),
		),
		"STORE_SETTINGS" => array(
			"NAME" => GetMessage("T_IBLOCK_DESC_STORE_SETTINGS"),
		),
		"BIG_DATA_SETTINGS" => array(
			"NAME" => GetMessage("CP_BC_GROUP_BIG_DATA_SETTINGS")
		),
		'ANALYTICS_SETTINGS' => array(
			'NAME' => GetMessage('ANALYTICS_SETTINGS')
		),
		"EXTENDED_SETTINGS" => array(
			"NAME" => GetMessage("IBLOCK_EXTENDED_SETTINGS"),
			"SORT" => 10000
		)
	),
	"PARAMETERS" => array(
		"VARIABLE_ALIASES" => array(
			"ELEMENT_ID" => array(
				"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_ELEMENT_ID"),
			),
			"SECTION_ID" => array(
				"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_SECTION_ID"),
			),

		),
		"SEF_MODE" => array(
			"sections" => array(
				"NAME" => GetMessage("SECTIONS_TOP_PAGE"),
				"DEFAULT" => "",
				"VARIABLES" => array(
				),
			),
			"section" => array(
				"NAME" => GetMessage("SECTION_PAGE"),
				"DEFAULT" => "#SECTION_ID#/",
				"VARIABLES" => array(
					"SECTION_ID",
					"SECTION_CODE",
					"SECTION_CODE_PATH",
				),
			),
			"element" => array(
				"NAME" => GetMessage("DETAIL_PAGE"),
				"DEFAULT" => "#SECTION_ID#/#ELEMENT_ID#/",
				"VARIABLES" => array(
					"ELEMENT_ID",
					"ELEMENT_CODE",
					"SECTION_ID",
					"SECTION_CODE",
					"SECTION_CODE_PATH",
				),
			),
			"compare" => array(
				"NAME" => GetMessage("COMPARE_PAGE"),
				"DEFAULT" => "compare.php?action=#ACTION_CODE#",
				"VARIABLES" => array(
					"action",
				),
			),
		),
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("IBLOCK_IBLOCK"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y",
		),
		"USE_FILTER" => array(
			"PARENT" => "FILTER_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_USE_FILTER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y",
		),
		"USE_REVIEW" => array(
			"PARENT" => "REVIEW_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_USE_REVIEW"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y",
		),
		"SECTION_COUNT_ELEMENTS" => array(
			"PARENT" => "SECTIONS_SETTINGS",
			"NAME" => GetMessage('CP_BC_SECTION_COUNT_ELEMENTS'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SECTION_TOP_DEPTH" => array(
			"PARENT" => "SECTIONS_SETTINGS",
			"NAME" => GetMessage('CP_BC_SECTION_TOP_DEPTH'),
			"TYPE" => "STRING",
			"DEFAULT" => "2",
		),
		"PAGE_ELEMENT_COUNT" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("IBLOCK_PAGE_ELEMENT_COUNT"),
			"TYPE" => "STRING",
			'HIDDEN' => isset($templateProperties['LIST_PRODUCT_ROW_VARIANTS']) ? 'Y' : 'N',
			"DEFAULT" => "30",
		),
		"ELEMENT_SORT_FIELD" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("IBLOCK_ELEMENT_SORT_FIELD"),
			"TYPE" => "LIST",
			"VALUES" => $arSort,
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => "sort",
		),
		"ELEMENT_SORT_ORDER" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("IBLOCK_ELEMENT_SORT_ORDER"),
			"TYPE" => "LIST",
			"VALUES" => $arAscDesc,
			"DEFAULT" => "asc",
			"ADDITIONAL_VALUES" => "Y",
		),
		"ELEMENT_SORT_FIELD2" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("IBLOCK_ELEMENT_SORT_FIELD2"),
			"TYPE" => "LIST",
			"VALUES" => $arSort,
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => "id",
		),
		"ELEMENT_SORT_ORDER2" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("IBLOCK_ELEMENT_SORT_ORDER2"),
			"TYPE" => "LIST",
			"VALUES" => $arAscDesc,
			"DEFAULT" => "desc",
			"ADDITIONAL_VALUES" => "Y",
		),
		"LIST_PROPERTY_CODE" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("IBLOCK_PROPERTY"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			'REFRESH' => isset($templateProperties['LIST_PROPERTY_CODE_MOBILE']) ? 'Y' : 'N',
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arProperty_LNS,
		),
		"INCLUDE_SUBSECTIONS" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("CP_BC_INCLUDE_SUBSECTIONS"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Y" => GetMessage('CP_BC_INCLUDE_SUBSECTIONS_ALL'),
				"A" => GetMessage('CP_BC_INCLUDE_SUBSECTIONS_ACTIVE'),
				"N" => GetMessage('CP_BC_INCLUDE_SUBSECTIONS_NO'),
			),
			"DEFAULT" => "Y",
		),
		"USE_MAIN_ELEMENT_SECTION" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BC_USE_MAIN_ELEMENT_SECTION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"DETAIL_STRICT_SECTION_CHECK" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BC_DETAIL_STRICT_SECTION_CHECK"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"LIST_META_KEYWORDS" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("CP_BC_LIST_META_KEYWORDS"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arUserFields_S,
		),
		"LIST_META_DESCRIPTION" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("CP_BC_LIST_META_DESCRIPTION"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arUserFields_S,
		),
		"LIST_BROWSER_TITLE" => array(
			"PARENT" => "LIST_SETTINGS",
			"NAME" => GetMessage("CP_BC_LIST_BROWSER_TITLE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"DEFAULT" => "-",
			"VALUES" => array_merge(array("-"=>" ", "NAME" => GetMessage("IBLOCK_FIELD_NAME")), $arUserFields_S),
		),
		"DETAIL_META_KEYWORDS" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("CP_BC_DETAIL_META_KEYWORDS"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => array_merge(array("-"=>" "),$arProperty_LNS),
		),
		"DETAIL_META_DESCRIPTION" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("CP_BC_DETAIL_META_DESCRIPTION"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => array_merge(array("-"=>" "),$arProperty_LNS),
		),
		"DETAIL_BROWSER_TITLE" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("CP_BC_DETAIL_BROWSER_TITLE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"DEFAULT" => "-",
			"VALUES" => array_merge(array("-"=>" ", "NAME" => GetMessage("IBLOCK_FIELD_NAME")), $arProperty_LNS),
		),
		"DETAIL_SET_CANONICAL_URL" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("CP_BC_DETAIL_SET_CANONICAL_URL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"SECTION_ID_VARIABLE" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("IBLOCK_SECTION_ID_VARIABLE"),
			"TYPE" => "STRING",
			"DEFAULT" => "SECTION_ID"
		),
		"DETAIL_CHECK_SECTION_ID_VARIABLE" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("CP_BC_DETAIL_CHECK_SECTION_ID_VARIABLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"SHOW_DEACTIVATED" => array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage('CP_BC_SHOW_DEACTIVATED'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"CACHE_TIME"  =>  array("DEFAULT"=>36000000),
		"CACHE_FILTER" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("IBLOCK_CACHE_FILTER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"CACHE_GROUPS" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("CP_BC_CACHE_GROUPS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SET_LAST_MODIFIED" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BC_SET_LAST_MODIFIED"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"SET_TITLE" => array(),
		"ADD_SECTIONS_CHAIN" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BC_ADD_SECTIONS_CHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"
		),
		"ADD_ELEMENT_CHAIN" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BC_ADD_ELEMENT_CHAIN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"PRICE_CODE" => array(
			"PARENT" => "PRICES",
			"NAME" => GetMessage("IBLOCK_PRICE_CODE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arPrice,
		),
		"ACTION_VARIABLE" => array(
			"PARENT" => "ACTION_SETTINGS",
			"NAME"		=> GetMessage("IBLOCK_ACTION_VARIABLE"),
			"TYPE"		=> "STRING",
			"DEFAULT"	=> "action"
		),
		"PRODUCT_ID_VARIABLE" => array(
			"PARENT" => "ACTION_SETTINGS",
			"NAME"		=> GetMessage("IBLOCK_PRODUCT_ID_VARIABLE"),
			"TYPE"		=> "STRING",
			"DEFAULT"	=> "id"
		),
		"USE_STORE" => array(
			"PARENT" => "STORE_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_USE_STORE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y",
		),
		"USE_GIFTS_DETAIL" => array(
			"PARENT" => "GIFTS_SETTINGS",
			"NAME" => GetMessage("SALE_T_DESC_USE_GIFTS_DETAIL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "Y",
		),
		'COMPATIBLE_MODE' => array(
			'PARENT' => 'EXTENDED_SETTINGS',
			'NAME' => GetMessage('CP_BC_COMPATIBLE_MODE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'REFRESH' => 'Y'
		),
		"USE_ELEMENT_COUNTER" => array(
			"PARENT" => "EXTENDED_SETTINGS",
			"NAME" => GetMessage('CP_BC_USE_ELEMENT_COUNTER'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y"
		),
		"DISABLE_INIT_JS_IN_COMPONENT" => array(
			"PARENT" => "EXTENDED_SETTINGS",
			"NAME" => GetMessage('CP_BC_DISABLE_INIT_JS_IN_COMPONENT'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"HIDDEN" => (!$compatibleMode ? 'Y' : 'N')
		)
	),
);

// hack for correct sort
if (isset($templateProperties['LIST_PROPERTY_CODE_MOBILE']))
{
	$arComponentParameters['PARAMETERS']['LIST_PROPERTY_CODE_MOBILE'] = $templateProperties['LIST_PROPERTY_CODE_MOBILE'];
	unset($templateProperties['LIST_PROPERTY_CODE_MOBILE']);
}
else
{
	unset($arComponentParameters['PARAMETERS']['LIST_PROPERTY_CODE_MOBILE']);
}

if ($usePropertyFeatures)
{
	if (isset($arComponentParameters['PARAMETERS']['PRODUCT_PROPERTIES'])) {
		$arComponentParameters['PARAMETERS']['PRODUCT_PROPERTIES'] = [];
	}
	unset($arComponentParameters['PARAMETERS']['LIST_PROPERTY_CODE']);
	unset($arComponentParameters['PARAMETERS']['DETAIL_PROPERTY_CODE']);
}

CIBlockParameters::AddPagerSettings(
	$arComponentParameters,
	GetMessage("T_IBLOCK_DESC_PAGER_CATALOG"), //$pager_title
	true, //$bDescNumbering
	true, //$bShowAllParam
	true, //$bBaseLink
	$arCurrentValues["PAGER_BASE_LINK_ENABLE"]==="Y" //$bBaseLinkEnabled
);

CIBlockParameters::Add404Settings($arComponentParameters, $arCurrentValues);

if($arCurrentValues["SEF_MODE"]=="Y")
{
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"] = array();
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"]["ELEMENT_ID"] = array(
		"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_ELEMENT_ID"),
		"TEMPLATE" => "#ELEMENT_ID#",
	);
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"]["ELEMENT_CODE"] = array(
		"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_ELEMENT_CODE"),
		"TEMPLATE" => "#ELEMENT_CODE#",
	);
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"]["SECTION_ID"] = array(
		"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_SECTION_ID"),
		"TEMPLATE" => "#SECTION_ID#",
	);
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"]["SECTION_CODE"] = array(
		"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_SECTION_CODE"),
		"TEMPLATE" => "#SECTION_CODE#",
	);
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"]["SECTION_CODE_PATH"] = array(
		"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_SECTION_CODE_PATH"),
		"TEMPLATE" => "#SECTION_CODE_PATH#",
	);
	$arComponentParameters["PARAMETERS"]["VARIABLE_ALIASES"]["SMART_FILTER_PATH"] = array(
		"NAME" => GetMessage("CP_BC_VARIABLE_ALIASES_SMART_FILTER_PATH"),
		"TEMPLATE" => "#SMART_FILTER_PATH#",
	);

	$smartBase = ($arCurrentValues["SEF_URL_TEMPLATES"]["section"]? $arCurrentValues["SEF_URL_TEMPLATES"]["section"]: "#SECTION_ID#/");
	$arComponentParameters["PARAMETERS"]["SEF_MODE"]["smart_filter"] = array(
		"NAME" => GetMessage("CP_BC_SEF_MODE_SMART_FILTER"),
		"DEFAULT" => $smartBase."filter/#SMART_FILTER_PATH#/apply/",
		"VARIABLES" => array(
			"SECTION_ID",
			"SECTION_CODE",
			"SECTION_CODE_PATH",
			"SMART_FILTER_PATH",
		),
	);
}

if($arCurrentValues["USE_FILTER"]=="Y")
{

	$arComponentParameters["PARAMETERS"]["FILTER_INSTANT_RELOAD"] = array(
		"PARENT" => "FILTER_SETTINGS",
		"NAME" => GetMessage("T_IBLOCK_FILTER_INSTANT_RELOAD"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
	);

	$arComponentParameters["PARAMETERS"]["FILTER_NAME"] = array(
		"PARENT" => "FILTER_SETTINGS",
		"NAME" => GetMessage("T_IBLOCK_FILTER"),
		"TYPE" => "STRING",
		"DEFAULT" => "",
	);
	if ($compatibleMode)
	{
		$arComponentParameters["PARAMETERS"]["FILTER_FIELD_CODE"] = CIBlockParameters::GetFieldCode(GetMessage("IBLOCK_FIELD"), "FILTER_SETTINGS");
		$arComponentParameters["PARAMETERS"]["FILTER_PROPERTY_CODE"] = array(
			"PARENT" => "FILTER_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_PROPERTY"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arProperty_LNS,
			"ADDITIONAL_VALUES" => "Y",
		);
		$arComponentParameters["PARAMETERS"]["FILTER_PRICE_CODE"] = array(
			"PARENT" => "FILTER_SETTINGS",
			"NAME" => GetMessage("IBLOCK_PRICE_CODE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arPrice,
		);
		if (!empty($offers))
		{
			$arComponentParameters["PARAMETERS"]["FILTER_OFFERS_FIELD_CODE"] = CIBlockParameters::GetFieldCode(GetMessage("CP_BC_FILTER_OFFERS_FIELD_CODE"), "FILTER_SETTINGS");
			$arComponentParameters["PARAMETERS"]["FILTER_OFFERS_PROPERTY_CODE"] = array(
				"PARENT" => "FILTER_SETTINGS",
				"NAME" => GetMessage("CP_BC_FILTER_OFFERS_PROPERTY_CODE"),
				"TYPE" => "LIST",
				"MULTIPLE" => "Y",
				"VALUES" => $arProperty_OffersWithoutFile,
				"ADDITIONAL_VALUES" => "Y",
			);
		}
	}
}

if ($catalogIncluded && $arCurrentValues["USE_STORE"]=='Y')
{
	$arStore = array();
	$storeIterator = CCatalogStore::GetList(
		array(),
		array('ISSUING_CENTER' => 'Y'),
		false,
		false,
		array('ID', 'TITLE')
	);
	while ($store = $storeIterator->GetNext())
		$arStore[$store['ID']] = "[".$store['ID']."] ".$store['TITLE'];

	$userFields = $USER_FIELD_MANAGER->GetUserFields("CAT_STORE", 0, LANGUAGE_ID);
	$propertyUF = array();

	foreach($userFields as $fieldName => $userField)
		$propertyUF[$fieldName] = $userField["LIST_COLUMN_LABEL"] ? $userField["LIST_COLUMN_LABEL"] : $fieldName;

	$arComponentParameters["PARAMETERS"]['STORES'] = array(
		'PARENT' => 'STORE_SETTINGS',
		'NAME' => GetMessage('STORES'),
		'TYPE' => 'LIST',
		'MULTIPLE' => 'Y',
		'VALUES' => $arStore,
		'ADDITIONAL_VALUES' => 'Y'
	);
	$arComponentParameters["PARAMETERS"]['USE_MIN_AMOUNT'] = array(
		'PARENT' => 'STORE_SETTINGS',
		'NAME' => GetMessage('USE_MIN_AMOUNT'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
		"REFRESH" => "Y",
	);
	if ($arCurrentValues['USE_MIN_AMOUNT']!="N")
	{
		$arComponentParameters["PARAMETERS"]["MIN_AMOUNT"] = array(
			"PARENT" => "STORE_SETTINGS",
			"NAME" => GetMessage("MIN_AMOUNT"),
			"TYPE" => "STRING",
			"DEFAULT" => 10,
		);
	}
	$arComponentParameters["PARAMETERS"]['SHOW_EMPTY_STORE'] = array(
		'PARENT' => 'STORE_SETTINGS',
		'NAME' => GetMessage('SHOW_EMPTY_STORE'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
	);
	$arComponentParameters["PARAMETERS"]['STORE_PATH'] = array(
		'PARENT' => 'STORE_SETTINGS',
		'NAME' => GetMessage('STORE_PATH'),
		"TYPE" => "STRING",
		"DEFAULT" => "/store/#store_id#",
	);
}

if (!ModuleManager::isModuleInstalled("sale") || isset($templateProperties['HIDE_USE_ALSO_BUY']))
{
	unset($templateProperties['HIDE_USE_ALSO_BUY']);
	unset($arComponentParameters["PARAMETERS"]["USE_ALSO_BUY"]);
	unset($arComponentParameters["GROUPS"]["ALSO_BUY_SETTINGS"]);
}
elseif ($arCurrentValues["USE_ALSO_BUY"] == "Y")
{
	$arComponentParameters["PARAMETERS"]["ALSO_BUY_ELEMENT_COUNT"] = array(
		"PARENT" => "ALSO_BUY_SETTINGS",
		"NAME"		=> GetMessage("T_IBLOCK_DESC_ALSO_BUY_ELEMENT_COUNT"),
		"TYPE"		=> "STRING",
		"DEFAULT"	=> 5
	);
	$arComponentParameters["PARAMETERS"]["ALSO_BUY_MIN_BUYES"] = array(
		"PARENT" => "ALSO_BUY_SETTINGS",
		"NAME"		=> GetMessage("T_IBLOCK_DESC_ALSO_BUY_MIN_BUYES"),
		"TYPE"		=> "STRING",
		"DEFAULT"	=> 1
	);
}

if ($catalogIncluded)
{
	$arComponentParameters["PARAMETERS"]['HIDE_NOT_AVAILABLE'] = array(
		'PARENT' => 'DATA_SOURCE',
		'NAME' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE'),
		'TYPE' => 'LIST',
		'DEFAULT' => 'N',
		'VALUES' => array(
			'Y' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_HIDE'),
			'L' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_LAST'),
			'N' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_SHOW')
		),
		'ADDITIONAL_VALUES' => 'N'
	);
	$arComponentParameters['PARAMETERS']['HIDE_NOT_AVAILABLE_OFFERS'] = array(
		'PARENT' => 'DATA_SOURCE',
		'NAME' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_OFFERS'),
		'TYPE' => 'LIST',
		'DEFAULT' => 'N',
		'VALUES' => array(
			'Y' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_OFFERS_HIDE'),
			'L' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_OFFERS_SUBSCRIBE'),
			'N' => GetMessage('CP_BC_HIDE_NOT_AVAILABLE_OFFERS_SHOW')
		)
	);
	$arComponentParameters["PARAMETERS"]['CONVERT_CURRENCY'] = array(
		'PARENT' => 'PRICES',
		'NAME' => GetMessage('CP_BC_CONVERT_CURRENCY'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
		'REFRESH' => 'Y',
	);

	if (isset($arCurrentValues['CONVERT_CURRENCY']) && $arCurrentValues['CONVERT_CURRENCY'] == 'Y')
	{
		$arComponentParameters['PARAMETERS']['CURRENCY_ID'] = array(
			'PARENT' => 'PRICES',
			'NAME' => GetMessage('CP_BC_CURRENCY_ID'),
			'TYPE' => 'LIST',
			'VALUES' => Currency\CurrencyManager::getCurrencyList(),
			'DEFAULT' => Currency\CurrencyManager::getBaseCurrency(),
			"ADDITIONAL_VALUES" => "Y",
		);
	}

	$arComponentParameters['PARAMETERS']['DETAIL_SET_VIEWED_IN_COMPONENT'] = array(
		"PARENT" => "EXTENDED_SETTINGS",
		"NAME" => GetMessage('CP_BC_DETAIL_SET_VIEWED_IN_COMPONENT'),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"HIDDEN" => (!$compatibleMode ? 'Y' : 'N')
	);
}

//tags
$arComponentParameters["PARAMETERS"]["CATALOG_SHOW_TAGS"] = array(
	"PARENT" => "TAGS",
	"NAME" => GetMessage("CATALOG_SHOW_TAGS"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "N",
	"REFRESH" => "Y"
);

if($arCurrentValues["CATALOG_SHOW_TAGS"] == "Y"){
	$arComponentParameters["PARAMETERS"]["CATALOG_MAX_TAGS"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_MAX_TAGS"),
		"TYPE" => "STRING",
		"DEFAULT" => "30"
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"REFRESH" => "Y",
	);

	if(!empty($arCurrentValues["CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION"]) && $arCurrentValues["CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION"] == "Y"){
		$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION_TREE"] = array(
			"PARENT" => "TAGS",
			"NAME" => GetMessage("CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION_TREE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "N",
		);
	}

	$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_DETAIL_LINK_VARIANT"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_TAGS_DETAIL_LINK_VARIANT"),
		"TYPE" => "LIST",
		"VALUES" => array("SEARCH" => GetMessage("CATALOG_TAGS_DETAIL_LINK_VARIANT_SEARCH"), "SECTION" => GetMessage("CATALOG_TAGS_DETAIL_LINK_VARIANT_SECTION")),
		"DEFAULT" => "SECTION",
		"REFRESH" => "Y"
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_MAX_DEPTH_LEVEL"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_TAGS_MAX_DEPTH_LEVEL"),
		"TYPE" => "STRING",
		"DEFAULT" => "5"
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_MAX_VISIBLE_TAGS_DESKTOP"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_MAX_VISIBLE_TAGS_DESKTOP"),
		"TYPE" => "STRING",
		"DEFAULT" => "10",
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_MAX_VISIBLE_TAGS_MOBILE"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_MAX_VISIBLE_TAGS_MOBILE"),
		"TYPE" => "STRING",
		"DEFAULT" => "6",
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_HIDE_TAGS_ON_MOBILE"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_HIDE_TAGS_ON_MOBILE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_SORT_FIELD"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_TAGS_SORT_FIELD"),
		"TYPE" => "LIST",
		"VALUES" => array("COUNTER" => GetMessage("CATALOG_TAGS_SORT_FIELD_COUNTER"), "NAME" => GetMessage("CATALOG_TAGS_SORT_FIELD_NAME")),
		"DEFAULT" => "COUNTER"
	);
	$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_SORT_TYPE"] = array(
		"PARENT" => "TAGS",
		"NAME" => GetMessage("CATALOG_TAGS_SORT_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => array("ASC" => GetMessage("CATALOG_TAGS_SORT_TYPE_ASC"), "DESC" => GetMessage("CATALOG_TAGS_SORT_TYPE_DESC")),
		"DEFAULT" => "DESC"
	);

	if(empty($arCurrentValues["CATALOG_TAGS_DETAIL_LINK_VARIANT"]) || $arCurrentValues["CATALOG_TAGS_DETAIL_LINK_VARIANT"] == "SECTION"){
		$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_DETAIL_SECTION_MAX_DELPH_LEVEL"] = array(
			"PARENT" => "TAGS",
			"NAME" => GetMessage("CATALOG_TAGS_DETAIL_SECTION_MAX_DELPH_LEVEL"),
			"TYPE" => "STRING",
			"DEFAULT" => "5",
		);
	}
	elseif($arCurrentValues["CATALOG_TAGS_DETAIL_LINK_VARIANT"] == "SEARCH"){
		$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_SEARCH_PATH"] = array(
			"PARENT" => "TAGS",
			"NAME" => GetMessage("CATALOG_TAGS_SEARCH_PATH"),
			"TYPE" => "STRING",
			"DEFAULT" => "/search/",
		);
		$arComponentParameters["PARAMETERS"]["CATALOG_TAGS_SEARCH_PARAM"] = array(
			"PARENT" => "TAGS",
			"NAME" => GetMessage("CATALOG_TAGS_SEARCH_PARAM"),
			"TYPE" => "STRING",
			"DEFAULT" => "q",
		);
	}

}

$arComponentParameters["PARAMETERS"]["SHOW_ADVANTAGES_IN_DETAIL"] = array(
	"NAME" => GetMessage("SHOW_ADVANTAGES_IN_DETAIL"),
	"TYPE" => "CHECKBOX",
	"PARENT" => "DETAIL_SETTINGS",
	"REFRESH" => "Y",
	"DEFAULT" =>"Y",
	"VALUE" => "Y"
);

if(empty($arCurrentValues["SHOW_ADVANTAGES_IN_DETAIL"]) || $arCurrentValues["SHOW_ADVANTAGES_IN_DETAIL"] === "Y"){

	if(!empty($arIBlockType)){

		$advantagesIblocks = array();
		$arComponentParameters["PARAMETERS"]["ADVANTAGES_IN_DETAIL_IBLOCK_TYPE"] = array(
			"PARENT" => "DETAIL_SETTINGS",
			"NAME" => GetMessage("ADVANTAGES_IN_DETAIL_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		);

		$advantagesIblockFilter = array();
		if(!empty($arCurrentValues["ADVANTAGES_IN_DETAIL_IBLOCK_TYPE"])){
			$advantagesIblockFilter["TYPE"] = $arCurrentValues["ADVANTAGES_IN_DETAIL_IBLOCK_TYPE"];
		}
		else{
			reset($arIBlockType);
			$advantagesIblockFilter["TYPE"] = key($arIBlockType);
		}

		$rsIBlock = CIBlock::GetList(array("SORT" => "ASC"), $advantagesIblockFilter);
		while($arIblock = $rsIBlock->Fetch()){
			$advantagesIblocks[$arIblock["ID"]] = "[".$arIblock["ID"]."] ".$arIblock["NAME"];
		}

		$arComponentParameters["PARAMETERS"]["ADVANTAGES_IN_DETAIL_IBLOCK_ID"] = array(
			"NAME" => GetMessage("ADVANTAGES_IN_DETAIL_IBLOCK_ID"),
			"VALUES" => $advantagesIblocks,
			"PARENT" => "DETAIL_SETTINGS",
			"REFRESH" => "Y",
			"TYPE" => "LIST",
		);

	}

}

$arComponentParameters["PARAMETERS"]["DISPLAY_OFFERS_TABLE"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("DISPLAY_OFFERS_TABLE"),
	"DEFAULT" => "Y",
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y"
);

if($arCurrentValues["DISPLAY_OFFERS_TABLE"] == "Y"){

	$arComponentParameters["PARAMETERS"]["OFFERS_TABLE_PAGER_COUNT"] = array(
		"PARENT" => "DETAIL_SETTINGS",
		"NAME" => GetMessage("OFFERS_TABLE_PAGER_COUNT"),
		"DEFAULT" => "10",
		"TYPE" => "STRING",
		"REFRESH" => "Y"
	);

	$arComponentParameters["PARAMETERS"]["OFFERS_TABLE_DISPLAY_PICTURE_COLUMN"] = array(
		"PARENT" => "DETAIL_SETTINGS",
		"NAME" => GetMessage("OFFERS_TABLE_DISPLAY_PICTURE_COLUMN"),
		"DEFAULT" => "Y",
		"TYPE" => "CHECKBOX",
		"REFRESH" => "Y"
	);

}

$arComponentParameters["PARAMETERS"]["DISPLAY_CHEAPER"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("DISPLAY_CHEAPER"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y"
);

$arComponentParameters["PARAMETERS"]["HIDE_DELIVERY_CALC"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("HIDE_DELIVERY_CALC"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y"
);

if($arCurrentValues["DISPLAY_CHEAPER"] == "Y"){

	$arFormsId = array();
	$rsForms = CForm::GetList($by = "s_sort", $order = "desc", array(), $is_filtered);
	while ($arForm = $rsForms->Fetch()){
	    $arFormsId[$arForm["ID"]] = $arForm["NAME"]." (id: ".$arForm["ID"].")";
	}

	$arComponentParameters["PARAMETERS"]["CHEAPER_FORM_ID"] = array(
		"PARENT" => "DETAIL_SETTINGS",
		"NAME" => GetMessage("CHEAPER_FORM_ID"),
		"TYPE" => "LIST",
		"VALUES" => $arFormsId,
		"REFRESH" => "Y",
	);

}

$arComponentParameters["PARAMETERS"]["DISPLAY_SUBSCRIBE"] = array(
	"PARENT" => "LIST_SETTINGS",
	"NAME" => GetMessage("DISPLAY_SUBSCRIBE"),
	"REFRESH" => "Y",
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
	"SORT" => "2"
);

if(empty($arCurrentValues["DISPLAY_SUBSCRIBE"]) || $arCurrentValues["DISPLAY_SUBSCRIBE"] == "Y"){
	$arComponentParameters["PARAMETERS"]["SUBSCRIBE_RUBRIC_ID"] = array(
		"PARENT" => "LIST_SETTINGS",
		"VALUES" => $arRubricList,
		"NAME" => GetMessage("CATALOG_SUBSCRIBE_RUBRIC_ID"),
		"TYPE" => "LIST",
		"SORT" => "3"
	);
}

$arComponentParameters["PARAMETERS"]["DETAIL_ALLOW_ADD_REVIEW_NOT_REGISTER"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("CATALOG_ALLOW_ADD_REVIEW_NOT_REGISTER"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
	"SORT" => "1"
);

$arComponentParameters["PARAMETERS"]["DETAIL_CALCULATE_DELIVERY"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("CATALOG_CALCULATE_DELIVERY"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y",
	"DEFAULT" => "Y",
	"SORT" => "2"
);

if(empty($arCurrentValues["DETAIL_CALCULATE_DELIVERY"]) || $arCurrentValues["DETAIL_CALCULATE_DELIVERY"] == "Y"){

	//delivery groups
	$arDeliveryGroups = array();

    //get groups
    $dbRes = \Bitrix\Sale\Delivery\Services\Table::getList(array(

        "filter" => array(
            "=CLASS_NAME" => "\Bitrix\Sale\Delivery\Services\Group",
            "=ACTIVE" => "Y"
        ),

        "select" => array(
            "ID", "NAME", "PARENT_ID", "CLASS_NAME"
        ),

        "order" => array(
            "PARENT_ID" => "ASC",
            "NAME" => "ASC"
        )

    ));

	//push to result
	while($arService = $dbRes->fetch()){

		//write groups
		$arDeliveryGroups[] = $arService["ID"]."[".$arService["NAME"]."]";

	}

	$arComponentParameters["PARAMETERS"]["DETAIL_CALCULATE_DELIVERY_GROUP_BUTTONS"] = array(
		"PARENT" => "DETAIL_SETTINGS",
		"NAME" => GetMessage("CATALOG_CALCULATE_DELIVERY_GROUP_BUTTONS"),
		"TYPE" => "STRING",
		"MULTIPLE" => "Y",
		"DEFAULT" => $arDeliveryGroups,
		"SORT" => "4"
	);

	$arComponentParameters["PARAMETERS"]["DETAIL_CALCULATE_DELIVERY_SHOW_IMAGES"] = array(
		"PARENT" => "DETAIL_SETTINGS",
		"NAME" => GetMessage("CATALOG_CALCULATE_DELIVERY_SHOW_IMAGES"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
		"SORT" => "5"
	);

}

$arComponentParameters["PARAMETERS"]["DETAIL_COUNT_TOP_PROPERTIES"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("CATALOG_DETAIL_COUNT_TOP_PROPERTIES"),
	"TYPE" => "STRING",
	"DEFAULT" => "7",
	"SORT" => "5"
);

$arComponentParameters["PARAMETERS"]["DETAIL_DISABLE_PRINT_WEIGHT"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("CATALOG_DETAIL_DISABLE_PRINT_WEIGHT"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "N",
	"SORT" => "5"
);

$arComponentParameters["PARAMETERS"]["DETAIL_DISABLE_PRINT_DIMENSIONS"] = array(
	"PARENT" => "DETAIL_SETTINGS",
	"NAME" => GetMessage("CATALOG_DETAIL_DISABLE_PRINT_DIMENSIONS"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "N",
	"SORT" => "5"
);
