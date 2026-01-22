<?//check empty request
if(empty($_POST["SEARCH_QUERY"])){
	die();
}?>
<?if(!empty($_POST["SITE_ID"])){
	define("SITE_ID", $_POST["SITE_ID"]);
}?>
<?define("STOP_STATISTICS", true);?>
<?define("NO_AGENT_CHECK", true);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?include($_SERVER["DOCUMENT_ROOT"]."/".$APPLICATION->GetCurDir()."lang/".LANGUAGE_ID."/ajax.php");?>
<?if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("search")){
	die("Include modules error, search, iblock");
}

//globals
global $arrFilter;

//vars
$arItemsIds = array();

//convert encoding
$_POST["SEARCH_QUERY"] = !defined("BX_UTF") ? iconv("UTF-8", "windows-1251//ignore", $_POST["SEARCH_QUERY"]) : $_POST["SEARCH_QUERY"];

//convert case
if(!empty($_POST["CONVERT_CASE"]) && $_POST["CONVERT_CASE"] == "Y"){
	$arLang = CSearchLanguage::GuessLanguage($_POST["SEARCH_QUERY"]);
	if(is_array($arLang) && $arLang["from"] != $arLang["to"]){
		$_POST["SEARCH_QUERY"] = CSearchLanguage::ConvertKeyboardLayout($_POST["SEARCH_QUERY"], $arLang["from"], $arLang["to"]);
	}
}

//search
$obSearch = new CSearch;
$arSearchParams = array(
   "QUERY" => $_POST["SEARCH_QUERY"],
   "SITE_ID" => $_POST["SITE_ID"],
   "MODULE_ID" => "iblock",
   "PARAM2" => intval($_POST["IBLOCK_ID"])
);

$obSearch->Search($arSearchParams, array(), array("STEMMING" => !empty($_POST["STEMMING"]) && $_POST["STEMMING"] == "Y"));
while($searchItem = $obSearch->fetch()){
	if(is_numeric($searchItem["ITEM_ID"])){
		$arItemsIds[$searchItem["ITEM_ID"]] = $searchItem["ITEM_ID"];
	}
}

//push ids
$arrFilter["ID"] = $arItemsIds;
?>
<?if(!empty($arItemsIds)):?>
	<h1><?=GetMessage("SEARCH_HEADING")?> <a href="#" id="searchProductsClose"></a></h1>
	<?$APPLICATION->IncludeComponent(
		"dresscode:catalog.section",
		"squares",
		array(
			"IBLOCK_TYPE" => $_POST["IBLOCK_TYPE"],
			"IBLOCK_ID" => intval($_POST["IBLOCK_ID"]),
			"ELEMENT_SORT_FIELD" => $_POST["ELEMENT_SORT_FIELD"],
			"ELEMENT_SORT_ORDER" => $_POST["ELEMENT_SORT_ORDER"],
			"PROPERTY_CODE" => $_POST["PROPERTY_CODE"],
			"PAGE_ELEMENT_COUNT" => $_POST["PAGE_ELEMENT_COUNT"],
			"LAZY_LOAD_PICTURES" => $_POST["LAZY_LOAD_PICTURES"],
			"PRICE_CODE" => $_POST["PRICE_CODE"],
			"PAGER_TEMPLATE" => "round_search",
			"CONVERT_CURRENCY" => $_POST["CONVERT_CURRENCY"],
			"CURRENCY_ID" => $_POST["CURRENCY_ID"],
			"FILTER_NAME" => "arrFilter",
			"ADD_SECTIONS_CHAIN" => "N",
			"SHOW_ALL_WO_SECTION" => "Y",
			"AJAX_MODE" => "N",
			"AJAX_OPTION_JUMP" => "N",
			"CACHE_TYPE" => "Y",
			"CACHE_FILTER" => "Y",
			"AJAX_OPTION_HISTORY" => "N",
			"HIDE_NOT_AVAILABLE" => $_POST["HIDE_NOT_AVAILABLE"],
			"HIDE_MEASURES" => $_POST["HIDE_MEASURES"],
		)
	);
	?>
	<a href="<?=SITE_DIR?>search/?q=<?=htmlspecialcharsbx($_POST["SEARCH_QUERY"])?>" class="searchAllResult"><span><?=GetMessage("SEARCH_ALL_RESULT")?></span></a>
<?else:?>
	<div class="errorMessage"><?=GetMessage("SEARCH_ERROR_FOR_EMPTY_RESULT")?><a href="#" id="searchProductsClose"></a></div>
<?endif;?>
