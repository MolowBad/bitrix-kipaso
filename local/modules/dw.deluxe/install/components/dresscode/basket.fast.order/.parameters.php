<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog")){
	$arComponentParameters = array(
		"PARAMETERS" => array(
			"CACHE_TIME" => Array("DEFAULT" => "1285912"),
			"SITE_ID" => Array(
				"DEFAULT" => "s1",
				"NAME" => GetMessage("SITE_ID"),
				"TYPE" => "STRING",
			),
			"USE_MASKED" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("USE_MASKED"),
				"TYPE" => "CHECKBOX",
				"REFRESH" => "Y"
			),
		)
	);
	if(isset($arCurrentValues["USE_MASKED"]) && $arCurrentValues["USE_MASKED"] === "Y"){
		$arComponentParameters["PARAMETERS"]["MASKED_FORMAT"] = array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("MASKED_FORMAT"),
			"TYPE" => "LIST",
			"VALUES" => array("+7 (999) 999-99-99" => "+7 (999) 999-99-99 (ru)", "+380 (999) 999-99-99" => "+380 (999) 999-99-99 (ua)", "+375 (999) 999-99-99" => "+375 (999) 999-99-99 (by)"),
			"DEFAULT" => "+7 (999) 999-99-99",
			"ADDITIONAL_VALUES" => "Y",
		);
	}
}
?>