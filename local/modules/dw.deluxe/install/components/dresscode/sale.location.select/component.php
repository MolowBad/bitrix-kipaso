<?

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if(!\Bitrix\Main\Loader::includeModule("sale")){
	return false;
}

if(empty($arParams["LOCATION_VALUE"])){
	return false;
}

if(!isset($arParams["SITE_ID"])){
	$arParams["SITE_ID"] = SITE_ID;
}

$arResult = array();
$arLocations = array();

$arLocParams = array(
	"filter" => array(
		"PHRASE" => htmlspecialcharsbx($arParams["LOCATION_VALUE"]),
		"SITE_ID" => $arParams["SITE_ID"],
		"LANGUAGE_ID" => LANGUAGE_ID
	)
);

$rsLocation = \Bitrix\Sale\Location\Search\Finder::find($arLocParams);

while($nextLocation = $rsLocation->fetch()){

	$arPath = array();
	$pathParams = array(
		"select" => array(
			"PNAME" => "NAME.NAME",
		),
		"filter" => array(
			"NAME.LANGUAGE_ID" => LANGUAGE_ID
		)
	);

	if(!empty($nextLocation["ID"])){
		$rsPath = \Bitrix\Sale\Location\LocationTable::getPathToNode(
			$nextLocation["ID"],
			$pathParams
		);
	}

	elseif(!empty($nextLocation["CODE"])){
		$rsPath = \Bitrix\Sale\Location\LocationTable::getPathToNodeByCode(
			$nextLocation["CODE"],
			$pathParams
		);
	}

	while($nextPath = $rsPath->Fetch()){
		$arPath[] = $nextPath["PNAME"];
	}

	$nextLocation["PATH"] = implode(", ", $arPath);
	$arLocations[$nextLocation["ID"]] = $nextLocation;

}

if(!empty($arLocations)){
	$arResult["LOCATIONS"] = $arLocations;
}

$this->setResultCacheKeys(array());
$this->IncludeComponentTemplate();
