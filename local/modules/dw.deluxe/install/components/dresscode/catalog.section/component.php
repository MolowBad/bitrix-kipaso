<?
use	Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\HttpRequest;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Type\Collection;
use Bitrix\Iblock\Component\Tools;
use Bitrix\Iblock\InheritedProperty\SectionValues;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if($_REQUEST["ajax"] == "Y"){
	$APPLICATION->RestartBuffer();
}

$arParams["FILTER_NAME"] = !empty($arParams["FILTER_NAME"]) ? $arParams["FILTER_NAME"] : "arrFilter";

global $USER;
global $APPLICATION;
global ${$arParams["FILTER_NAME"]};

if(empty($arParams["FILTER_NAME"]) || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])){
	$arrFilter = [];
}

else{

	$arrFilter = ${$arParams["FILTER_NAME"]};
	if(!is_array($arrFilter)){
		$arrFilter = [];
	}

	elseif(!empty($arrFilter["FACET_OPTIONS"])){
		unset($arrFilter["FACET_OPTIONS"]);
	}

}

$arResult["FILTER"] = $arrFilter;

if(empty($arParams["PRICE_CODE"])){
	$arParams["PRICE_CODE"] = [];
}

$arParams["SET_TITLE"] = $arParams["SET_TITLE"] != "N";
$arParams["ADD_EDIT_BUTTONS"] = !empty($arParams["ADD_EDIT_BUTTONS"]) ? $arParams["ADD_EDIT_BUTTONS"] : "N";
$arParams["SET_BROWSER_TITLE"] = (isset($arParams["SET_BROWSER_TITLE"]) && $arParams["SET_BROWSER_TITLE"] === "N" ? "N" : "Y");
$arParams["SET_META_KEYWORDS"] = (isset($arParams["SET_META_KEYWORDS"]) && $arParams["SET_META_KEYWORDS"] === "N" ? "N" : "Y");
$arParams["SET_META_DESCRIPTION"] = (isset($arParams["SET_META_DESCRIPTION"]) && $arParams["SET_META_DESCRIPTION"] === "N" ? "N" : "Y");
$arParams["ADD_SECTIONS_CHAIN"] = (isset($arParams["ADD_SECTIONS_CHAIN"]) && $arParams["ADD_SECTIONS_CHAIN"] === "Y");
$arParams["DISPLAY_TOP_PAGER"] = $arParams["DISPLAY_TOP_PAGER"] == "Y";
$arParams["DISPLAY_BOTTOM_PAGER"] = $arParams["DISPLAY_BOTTOM_PAGER"] != "N";
$arParams["PAGER_TITLE"] = trim($arParams["PAGER_TITLE"]);
$arParams["PAGER_SHOW_ALWAYS"] = $arParams["PAGER_SHOW_ALWAYS"] == "Y";
$arParams["PAGER_TEMPLATE"] = trim($arParams["PAGER_TEMPLATE"]);
$arParams["PAGER_DESC_NUMBERING"] = $arParams["PAGER_DESC_NUMBERING"] == "Y";
$arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] = intval($arParams["PAGER_DESC_NUMBERING_CACHE_TIME"]);
$arParams["PAGER_SHOW_ALL"] = $arParams["PAGER_SHOW_ALL"] == "Y";
$arParams["PAGE_ELEMENT_COUNT"] = !empty($arParams["PAGE_ELEMENT_COUNT"]) ? $arParams["PAGE_ELEMENT_COUNT"] : 30;
$arParams["LAZY_LOAD_PICTURES"] = !empty($arParams["LAZY_LOAD_PICTURES"]) ? $arParams["LAZY_LOAD_PICTURES"] : "N";

if($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"]){

	\CPageOption::SetOptionString("main", "nav_page_in_session", "N");

	$arNavParams = [
		"nPageSize" => $arParams["PAGE_ELEMENT_COUNT"],
		"bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
		"bShowAll" => $arParams["PAGER_SHOW_ALL"],
	];

	$arNavigation = \CDBResult::GetNavParams($arNavParams);

	if($arNavigation["PAGEN"] == 0 && $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] > 0){
		$arParams["CACHE_TIME"] = $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
	}

}

else{
	$arNavParams = [
		"nTopCount" => $arParams["PAGE_ELEMENT_COUNT"],
		"bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
	];
	$arNavigation = false;

}

if (empty($arParams["PAGER_PARAMS_NAME"]) || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PAGER_PARAMS_NAME"])){
	$pagerParameters = [];
}

else {
	$pagerParameters = $GLOBALS[$arParams["PAGER_PARAMS_NAME"]];
	if (!is_array($pagerParameters)){
		$pagerParameters = [];
	}
}

$arParams["CACHE_GROUPS"] = trim($arParams["CACHE_GROUPS"]);
if ($arParams["CACHE_GROUPS"] != "N"){
	$arParams["CACHE_GROUPS"] = "Y";
}

$arParams["CACHE_FILTER"] = $arParams["CACHE_FILTER"] == "Y";
if(!$arParams["CACHE_FILTER"] && !empty($arrFilter)) {
	$arParams["CACHE_TIME"] = 0;
}

if (empty($arParams["ELEMENT_SORT_FIELD"]))
	$arParams["ELEMENT_SORT_FIELD"] = "sort";
if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["ELEMENT_SORT_ORDER"]))
	$arParams["ELEMENT_SORT_ORDER"] = "asc";
if (empty($arParams["ELEMENT_SORT_FIELD2"]))
	$arParams["ELEMENT_SORT_FIELD2"] = "id";
if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["ELEMENT_SORT_ORDER2"]))
	$arParams["ELEMENT_SORT_ORDER2"] = "desc";

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
$arParams["IBLOCK_ID"] = (int)$arParams["IBLOCK_ID"];
$arParams["SECTION_ID"] = (int)$arParams["~SECTION_ID"];

if($arParams["SECTION_ID"] > 0 && $arParams["SECTION_ID"]."" != $arParams["~SECTION_ID"]){

	if (Loader::includeModule("iblock")){
		Tools::process404(
			trim($arParams["MESSAGE_404"]) ?: Loc::getMessage("CATALOG_SECTION_NOT_FOUND"),
			true,
			$arParams["SET_STATUS_404"] === "Y",
			$arParams["SHOW_404"] === "Y",
			$arParams["FILE_404"]
		);
	}

	return false;

}

if (!isset($arParams["INCLUDE_SUBSECTIONS"]) || !in_array($arParams["INCLUDE_SUBSECTIONS"], ["Y", "A", "N"])){
	$arParams["INCLUDE_SUBSECTIONS"] = "Y";
}

$arParams["USE_MAIN_ELEMENT_SECTION"] = $arParams["USE_MAIN_ELEMENT_SECTION"] === "Y";
$arParams["SHOW_ALL_WO_SECTION"] = $arParams["SHOW_ALL_WO_SECTION"] === "Y";
$arParams["SET_LAST_MODIFIED"] = $arParams["SET_LAST_MODIFIED"] === "Y";

if (!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 36000000;
}

Loader::requireModule("dw.deluxe");

$cacheID = [
	"USER_GROUPS" => ($arParams["CACHE_GROUPS"] === "N" ? false : $USER->GetGroups()),
	"PRICE_CODE" => implode(",", $arParams["PRICE_CODE"]),
	"PAGER_PARAMS" => $pagerParameters,
	"NAVIGATION" => $arNavigation,
	"SMART_FILTER" => $arrFilter,
	"SITE_ID" => \SITE_ID
];

if ($this->StartResultCache($arParams["CACHE_TIME"], serialize($cacheID))){

	$arResult = [];

	$sectionId = 0;
	$woSection = false;
	$skuIblockId = null;

	$arCatalogType = \CCatalogSKU::GetInfoByIBlock($arParams["IBLOCK_ID"]);
	if(!empty($arCatalogType["IBLOCK_ID"])){
		$skuIblockId = $arCatalogType["IBLOCK_ID"];
	}

	$arSectionSelect = [
		"ID",
		"NAME",
		"CODE",
		"UF_*",
		"XML_ID",
		"ACTIVE",
		"PICTURE",
		"IBLOCK_ID",
		"TIMESTAMP_X",
		"DESCRIPTION",
		"DETAIL_PICTURE",
		"SECTION_PAGE_URL",
		"IBLOCK_SECTION_ID"
	];

	$arSectionFilter = [
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"GLOBAL_ACTIVE" => "Y",
		"ACTIVE" => "Y"
	];

	if(!empty($arParams["SECTION_ID"])){
		$arSectionFilter["ID"] = $arParams["SECTION_ID"];
	}

	elseif(!empty($arParams["SECTION_CODE"])){
		$arSectionFilter["=CODE"] = $arParams["SECTION_CODE"];
	}

	else{
		$arSectionFilter["ID"] = 0;
	}

	if(!empty($arParams["SECTION_ID"]) || !empty($arParams["SECTION_CODE"])){

		$rsSection = \CIBlockSection::GetList(
			[],
			$arSectionFilter,
			false,
			$arSectionSelect
		);

		if($arResult = $rsSection->GetNext()){
			$sectionId = $arResult["ID"];
		}

		if (!empty($sectionId)){

			$ipropValues = new SectionValues($arResult["IBLOCK_ID"], $arResult["ID"]);
			$arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();

			if($arParams["ADD_SECTIONS_CHAIN"]){

				$arResult["PATH"] = [];
				$rsPath = \CIBlockSection::GetNavChain(
					$arResult["IBLOCK_ID"],
					$arResult["ID"],
					[
						"ID", "CODE", "XML_ID", "EXTERNAL_ID", "IBLOCK_ID",
						"IBLOCK_SECTION_ID", "SORT", "NAME", "ACTIVE",
						"DEPTH_LEVEL", "SECTION_PAGE_URL"
					]
				);

				$rsPath->SetUrlTemplates("", $arParams["SECTION_URL"]);
				while($arPath = $rsPath->GetNext()){

					$ipropValues = new SectionValues($arParams["IBLOCK_ID"], $arPath["ID"]);
					$arPath["IPROPERTY_VALUES"] = $ipropValues->getValues();
					$arResult["PATH"][] = $arPath;
				}

			}

		}

		else{
			$arResult["IPROPERTY_VALUES"] = [];
		}

	}

	else{

		if($arParams["SHOW_ALL_WO_SECTION"] == "Y"){
			$woSection = true;
		}

	}

	if(empty($sectionId) && !$woSection){

		Tools::process404(
			trim($arParams["MESSAGE_404"]) ?: Loc::getMessage("CATALOG_SECTION_NOT_FOUND"),
			true,
			$arParams["SET_STATUS_404"] === "Y",
			$arParams["SHOW_404"] === "Y",
			$arParams["FILE_404"]
		);

		$this->abortResultCache();
		return false;

	}

	$arSort = [];

	if($arParams["HIDE_NOT_AVAILABLE"] == "L"){
		$arSort["CATALOG_AVAILABLE"] = "desc, nulls";
	}

	if(!empty($arParams["ELEMENT_SORT_FIELD"])){
		$arSort[$arParams["ELEMENT_SORT_FIELD"]] = $arParams["ELEMENT_SORT_ORDER"];
	}

	if(!empty($arParams["ELEMENT_SORT_FIELD2"])){
		$arSort[$arParams["ELEMENT_SORT_FIELD2"]] = $arParams["ELEMENT_SORT_ORDER2"];
	}

	$arFilter = [
		"INCLUDE_SUBSECTIONS" => ($arParams["INCLUDE_SUBSECTIONS"] == "N" ? "N" : "Y"),
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"CHECK_PERMISSIONS" => "Y",
		"MIN_PERMISSION" => "R",
		"IBLOCK_LID" => SITE_ID,
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
	];

	if ($arParams["INCLUDE_SUBSECTIONS"] == "A"){
		$arFilter["SECTION_GLOBAL_ACTIVE"] = "Y";
	}

	if($arResult["ID"]){
		$arFilter["SECTION_ID"] = [intval($sectionId)];
	}

	elseif(!$arParams["SHOW_ALL_WO_SECTION"]){
		$arFilter["SECTION_ID"] = 0;
	}

	else{

		if (is_set($arFilter, "INCLUDE_SUBSECTIONS")){
			unset($arFilter["INCLUDE_SUBSECTIONS"]);
		}

		if (is_set($arFilter, "SECTION_GLOBAL_ACTIVE")){
			unset($arFilter["SECTION_GLOBAL_ACTIVE"]);
		}
	}

	$additionalFilter = [];
	$arPriceFilter = [];
	$offersFilter = [];

	foreach($arrFilter as $inx => $nextValue){

		if(preg_match('/^(>=|<=|><|)CATALOG_/', $inx)){
			$arPriceFilter[$inx] = $nextValue;
			unset($arrFilter[$inx]);
		}

	}

	if($arParams["HIDE_NOT_AVAILABLE"] == "Y"){
		$additionalFilter["=CATALOG_AVAILABLE"] = "Y";
	}

	if(!empty($arParams["ENABLED_SKU_FILTER"]) && $arParams["ENABLED_SKU_FILTER"] == "Y"){
		if(!empty($arrFilter["OFFERS"])){
			$offersFilter = $arrFilter["OFFERS"];
		}
	}

	$arOffersFilter = [
		"=ID" => \CIBlockElement::SubQuery(
			"PROPERTY_CML2_LINK",
			array_merge(
				$arPriceFilter,
				$additionalFilter,
				$offersFilter,
				[
					"IBLOCK_ID" => $skuIblockId,
					"ACTIVE_DATE" => "Y",
					"ACTIVE" => "Y"
				]
			)
		)
	];

	if(!empty($offersFilter)){
		$arFilter[] = $arOffersFilter;
	}

	else{
		$arFilter[] = [
			"LOGIC" => "OR",
			array_merge(
				[
					"!CATALOG_TYPE" => ProductTable::TYPE_SKU
				],
				$arPriceFilter,
				$additionalFilter
			),
			$arOffersFilter
		];
	}

	$arSelect = [
		"ID",
		"SORT",
		"CODE",
		"NAME",
		"XML_ID",
		"IBLOCK_ID"
	];

	$arResult["ITEMS"] = [];

	$rsElements = \CIBlockElement::GetList(
		$arSort,
		array_merge(
			$arrFilter,
			$arFilter
		),
		false,
		$arNavParams,
		$arSelect
	);

	$rsElements->SetSectionContext($arResult);

	while($arItem = $rsElements->GetNext()){
		$arResult["ITEMS"][$arItem["ID"]] = $arItem;
	}

	if(!empty($arResult["ITEMS"]) && !empty($arParams["ENABLED_SKU_FILTER"]) && $arParams["ENABLED_SKU_FILTER"] == "Y"){
		$arResult["FILTER"] = array_merge($arrFilter, $arPriceFilter);
		if(!empty($arResult["FILTER"]["=ID"])) {
			unset($arResult["FILTER"]["=ID"]);
		}
		if(!empty($arResult["FILTER"]["ID"])) {
			unset($arResult["FILTER"]["ID"]);
		}
	}

	$navComponentParameters = [];
	if ($arParams["PAGER_BASE_LINK_ENABLE"] === "Y"){

		$pagerBaseLink = trim($arParams["PAGER_BASE_LINK"]);
		if ($pagerBaseLink === ""){
			$pagerBaseLink = $arResult["SECTION_PAGE_URL"];
		}

		if ($pagerParameters && isset($pagerParameters["BASE_LINK"])){
			$pagerBaseLink = $pagerParameters["BASE_LINK"];
			unset($pagerParameters["BASE_LINK"]);
		}

		$navComponentParameters["BASE_LINK"] = CHTTP::urlAddParams(
			$pagerBaseLink,
			$pagerParameters,
			[
				"encode" => true
			]
		);
	}

	else{

		$uri = new Uri($this->request->getRequestUri());
		$uri->deleteParams(
			array_merge(
				[
					"PAGEN_".$rsElements->NavNum,
					"SIZEN_".$rsElements->NavNum,
					"SHOWALL_".$rsElements->NavNum,
					"ajax",
					"AJAX",
					"PHPSESSID",
					"clear_cache",
					"bitrix_include_areas"
				],
				HttpRequest::getSystemParameters()
			)
		);

		$navComponentParameters["BASE_LINK"] = $uri->getUri();

	}

	$arResult["NAV_STRING"] = $rsElements->GetPageNavStringEx(
		$navComponentObject,
		$arParams["PAGER_TITLE"],
		$arParams["PAGER_TEMPLATE"],
		$arParams["PAGER_SHOW_ALWAYS"],
		$this,
		$navComponentParameters
	);

	$arResult["NAV_CACHED_DATA"] = null;
	$arResult["NAV_NUM_PAGE"] = $rsElements->NavNum;
	$arResult["NAV_PARAM"] = $navComponentParameters;

	$this->setResultCacheKeys([
		"ID",
		"NAME",
		"PATH",
		"TIMESTAMP_X",
		"NAV_CACHED_DATA",
		"IPROPERTY_VALUES",
		"IBLOCK_SECTION_ID",
		$arParams["BROWSER_TITLE"],
		$arParams["META_KEYWORDS"],
		$arParams["META_DESCRIPTION"]
	]);

	$this->IncludeComponentTemplate();

}

$arTitleOptions = null;

if($arParams["SET_TITLE"]){

	if (!empty($arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"])){
		$APPLICATION->SetTitle($arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"], $arTitleOptions);
	}

	elseif(isset($arResult["NAME"])){
		$APPLICATION->SetTitle($arResult["NAME"], $arTitleOptions);

	}

}

if($arParams["SET_BROWSER_TITLE"] === "Y"){

	$browserTitle = Collection::firstNotEmpty($arResult, $arParams["BROWSER_TITLE"], $arResult["IPROPERTY_VALUES"], "SECTION_META_TITLE");

	if (is_array($browserTitle)){
		$APPLICATION->SetPageProperty("title", implode(" ", $browserTitle), $arTitleOptions);
	}

	elseif ($browserTitle != ""){
		$APPLICATION->SetPageProperty("title", $browserTitle, $arTitleOptions);
	}

	else{
		$APPLICATION->SetPageProperty("title", $arResult["NAME"], $arTitleOptions);
	}

}

if($arParams["SET_META_KEYWORDS"] === "Y"){
	$metaKeywords = Collection::firstNotEmpty(
		$arResult,
		$arParams["META_KEYWORDS"],
		$arResult["IPROPERTY_VALUES"],
		"SECTION_META_KEYWORDS"
	);

	if(is_array($metaKeywords)){
		$APPLICATION->SetPageProperty("keywords", implode(" ", $metaKeywords), $arTitleOptions);
	}

	elseif(!empty($metaKeywords)){
		$APPLICATION->SetPageProperty("keywords", $metaKeywords, $arTitleOptions);
	}
}

if($arParams["SET_META_DESCRIPTION"] === "Y"){

	$metaDescription = Collection::firstNotEmpty(
		$arResult,
		$arParams["META_DESCRIPTION"],
		$arResult["IPROPERTY_VALUES"],
		"SECTION_META_DESCRIPTION"
	);

	if(is_array($metaDescription)){
		$APPLICATION->SetPageProperty("description", implode(" ", $metaDescription), $arTitleOptions);
	}

	elseif(!empty($metaDescription)){
		$APPLICATION->SetPageProperty("description", $metaDescription, $arTitleOptions);
	}
}

if($arParams["ADD_SECTIONS_CHAIN"] && isset($arResult["PATH"]) && is_array($arResult["PATH"])){

	foreach($arResult["PATH"] as $arPath){

		if(!empty($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"])){
			$APPLICATION->AddChainItem(
				$arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"],
				$arPath["~SECTION_PAGE_URL"]
			);
		}

		else{
			$APPLICATION->AddChainItem(
				$arPath["NAME"],
				$arPath["~SECTION_PAGE_URL"]
			);
		}

	}

}

if($arParams["SET_LAST_MODIFIED"] && $arResult["TIMESTAMP_X"]){
	Context::getCurrent()->getResponse()->setLastModified($arResult["TIMESTAMP_X"]);
}

if($APPLICATION->GetShowIncludeAreas()){

	if($arParams["ADD_EDIT_BUTTONS"] == "Y"){

		if(Loader::includeModule("iblock")){

			$sectionId = !empty($sectionId) ? $sectionId : $arParams["SECTION_ID"];

			$buttons = \CIBlock::GetPanelButtons(
				$arParams["IBLOCK_ID"],	0, $sectionId, [
					"USE_CATALOG_BUTTONS" => [
						"add_product" => 1,
						"add_sku" => 1
					],
					"CATALOG" => true,
					"SESSID" => false
				]
			);

			$this->addIncludeAreaIcons(
				\CIBlock::GetComponentMenu(
					$APPLICATION->GetPublicShowMode(),
					$buttons
				)
			);

		}

	}

}

if($_REQUEST["ajax"] == "Y"){
	die();
}

return $arResult["ID"];
