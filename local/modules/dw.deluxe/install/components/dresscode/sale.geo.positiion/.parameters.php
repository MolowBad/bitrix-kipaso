<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog")){
	$arComponentParameters = array(
		"PARAMETERS" => array(
			"GEO_IP_PARAMS" => array(
		         "PARENT" => "BASE",
		         "NAME" => GetMessage("GEO_IP_PARAMS"),
		         "TYPE" => "LIST",
		          "VALUES" => array(
		          	"SUPEXGEO" => GetMessage("GEO_IP_PARAMS_SYPEXGEO"),
		          	"YANDEX" => GetMessage("GEO_IP_PARAMS_YANDEX")
		          ),
		          "REFRESH" => "Y",
		          "DEFAULT" => "SUPEXGEO",
			),
			"DISABLE_CONFIRMATION_WINDOW" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("DISABLE_CONFIRMATION_WINDOW"),
				"TYPE" => "CHECKBOX",
				"SORT" => 1
			),
			"CACHE_TIME" => Array("DEFAULT" => "1285912"),
		)
	);

	if(empty($arCurrentValues["GEO_IP_PARAMS"]) || $arCurrentValues["GEO_IP_PARAMS"] === "SUPEXGEO"){
		$arComponentParameters["PARAMETERS"]["GEO_SYPEX_KEY"] = array(
	         "PARENT" => "BASE",
	         "NAME" => GetMessage("GEO_SYPEX_KEY"),
	         "TYPE" => "STRING",
	          "REFRESH" => "Y",
	          "DEFAULT" => "",
		);
	}
	if(isset($arCurrentValues["GEO_IP_PARAMS"]) && $arCurrentValues["GEO_IP_PARAMS"] === "YANDEX"){
		$arComponentParameters["PARAMETERS"]["YANDEX_API_KEY"] = array(
	         "PARENT" => "BASE",
	         "NAME" => GetMessage("YANDEX_API_KEY"),
	         "TYPE" => "STRING",
	          "REFRESH" => "Y",
	          "DEFAULT" => "",
		);
	}

}
?>