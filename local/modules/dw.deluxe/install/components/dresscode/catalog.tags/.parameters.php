<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

	use Bitrix\Main\Loader;
	use Bitrix\Iblock;
	use Bitrix\Currency;

	//globals
	global $USER_FIELD_MANAGER;

	if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog")){
		return false;
	}

	$IBLOCKS     = array();
	$IBLOCK_TYPE = array();

	$res = CIBlockType::GetList();
	while($arRes = $res->Fetch()){
		$IBLOCK_TYPE[$arRes["ID"]] = $arRes["ID"];
	}

	$res = CIBlock::GetList(
	    Array(),
	    Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"])
	);

	while($arRes = $res->Fetch()){
		$IBLOCKS[$arRes["ID"]] = $arRes["NAME"];
	}
	$arComponentParameters = array(
		"GROUPS" => array(
			"MAIN_SECTION" => array(
				"NAME" => GetMessage("MAIN_SECTION_GROUP"),
			),
		),
		"PARAMETERS" => array(
			"IBLOCK_TYPE" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("IBLOCK_TYPE"),
				"TYPE" => "LIST",
				"VALUES" => $IBLOCK_TYPE,
			),
			"IBLOCK_ID" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("IBLOCK_ID"),
				"TYPE" => "LIST",
				"VALUES" => $IBLOCKS,
			),
			"SECTION_ID" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("SECTION_ID"),
				"TYPE" => "STRING",
			),
			"SECTION_CODE" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("SECTION_CODE"),
				"TYPE" => "STRING",
			),
			"SECTION_CODE_PATH" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("SECTION_CODE_PATH"),
				"TYPE" => "STRING",
			),
			"SEF_FOLDER" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("SEF_FOLDER"),
				"TYPE" => "STRING",
			),
			"INCLUDE_SUBSECTIONS" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("INCLUDE_SUBSECTIONS"),
				"TYPE" => "CHECKBOX",
				"REFRESH" => "Y"
			),
			"HIDE_NOT_AVAILABLE" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("HIDE_NOT_AVAILABLE"),
				"TYPE" => "CHECKBOX",
			),
			"MAX_TAGS" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("MAX_TAGS"),
				"DEFAULT" => "30",
				"TYPE" => "STRING",
			),
			"MAX_VISIBLE_TAGS_DESKTOP" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("MAX_VISIBLE_TAGS_DESKTOP"),
				"TYPE" => "STRING",
				"DEFAULT" => "10",
			),
			"MAX_VISIBLE_TAGS_MOBILE" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("MAX_VISIBLE_TAGS_MOBILE"),
				"TYPE" => "STRING",
				"DEFAULT" => "6",
			),
			"USE_IBLOCK_MAIN_SECTION" => array(
				"PARENT" => "MAIN_SECTION",
				"NAME" => GetMessage("USE_IBLOCK_MAIN_SECTION"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "N",
				"REFRESH" => "Y",
			),
			"HIDE_TAGS_ON_MOBILE" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("HIDE_TAGS_ON_MOBILE"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "N",
			),
			"SORT_FIELD" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("SORT_FIELD"),
				"TYPE" => "LIST",
				"VALUES" => array("COUNTER" => GetMessage("SORT_FIELD_COUNTER"), "NAME" => GetMessage("SORT_FIELD_NAME")),
				"DEFAULT" => "COUNTER"
			),
			"SORT_TYPE" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("SORT_TYPE"),
				"TYPE" => "LIST",
				"VALUES" => array("ASC" => GetMessage("SORT_TYPE_ASC"), "DESC" => GetMessage("SORT_TYPE_DESC")),
				"DEFAULT" => "DESC"
			),
			"CACHE_TIME" => Array("DEFAULT" => "36000000")
		)
	);

	//check use main section
	if(!empty($arCurrentValues["USE_IBLOCK_MAIN_SECTION"]) && $arCurrentValues["USE_IBLOCK_MAIN_SECTION"] == "Y"){
		$arComponentParameters["PARAMETERS"]["USE_IBLOCK_MAIN_SECTION_TREE"] = array(
			"PARENT" => "MAIN_SECTION",
			"NAME" => GetMessage("USE_IBLOCK_MAIN_SECTION_TREE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y"
		);
	}

?>
