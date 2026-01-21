<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

	use Bitrix\Main\Loader;
	use Bitrix\Iblock;
	use Bitrix\Currency;

	//globals
	global $USER_FIELD_MANAGER;

	//load modules
	if(!Loader::includeModule("subscribe")){
		return false;
	}

	//vars
	$arRubricList = array();
	$arSitesList = array();

	//set filter
	$arRubricFilter = array("ACTIVE" => "Y", "VISIBLE" => "Y");

	//check site id
	if(!empty($arCurrentValues["SITE_ID"])){
		$arRubricFilter["LID"] = $arCurrentValues["SITE_ID"];
	}

	//get rubrics
	$rsRubric = CRubric::GetList(array("SORT" => "ASC", "NAME" => "ASC"), $arRubricFilter);
	while($arRubric = $rsRubric->GetNext()){
		$arRubricList[$arRubric["ID"]] = $arRubric["NAME"]." [".$arRubric["LID"]."]";
	}

	//get sites
	$rsSites = CSite::GetList($by = "sort", $order = "desc", array("ACTIVE" => "Y"));
	while($arSite = $rsSites->Fetch()){
		$arSitesList[$arSite["ID"]] = $arSite["ID"];
	}

	//component paraments
	$arComponentParameters = array(
		"PARAMETERS" => array(
			"SITE_ID" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("CATALOG_SUBSCRIBE_SITE_ID"),
				"TYPE" => "LIST",
				"VALUES" => $arSitesList,
				"REFRESH" => "Y"
			),
			"CACHE_TIME" => Array("DEFAULT" => "36000000")
		)
	);

	$arComponentParameters["PARAMETERS"]["RUBRIC_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("CATALOG_SUBSCRIBE_RUBRIC_ID"),
		"TYPE" => "LIST",
		"VALUES" => $arRubricList,
	);

?>
