<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog")){

	//default url params for remove
	$arRemoveParams = array("sort_field" => "sort_field", "sort_to", "view", "clear_cache", "bitrix_include_areas", "set_filter", "show_page_exec_time", "show_include_exec_time", "show_sql_stat");
	$arRemoveParams = array_combine($arRemoveParams, $arRemoveParams);

	//get data
	$res = CIBlockType::GetList();
	while($arRes = $res->Fetch()){
		$IBLOCK_TYPE[$arRes["ID"]] = $arRes["ID"];
	}

	$res = CIBlock::GetList(
	    array(),
	    array('TYPE' => $arCurrentValues["IBLOCK_TYPE"])
	);

	while($arRes = $res->Fetch()){
		$IBLOCKS[$arRes["ID"]] = $arRes["NAME"];
	}

	//params
	$arComponentParameters = array(
		"PARAMETERS" => array(
			"CACHE_TIME" => array("DEFAULT" => "1285912"),
			"IBLOCK_TYPE" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("IBLOCK_TYPE"),
				"TYPE" => "LIST",
				"VALUES" => $IBLOCK_TYPE,
				"REFRESH" => "Y"
			),
			"IBLOCK_ID" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("IBLOCK"),
				"TYPE" => "LIST",
				"VALUES" => $IBLOCKS,
				"REFRESH" => "Y"
			),
			"IGNORE_URL_PARAMS" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("IGNORE_URL_PARAMS"),
				"TYPE" => "LIST",
				"VALUES" => $arRemoveParams,
				"ADDITIONAL_VALUES" => "Y",
				"MULTIPLE" => "Y",
				"REFRESH" => "N"
			),
			"IGNORE_UTM" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("IGNORE_UTM"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"REFRESH" => "N"
			),
		)
	);

}
?>