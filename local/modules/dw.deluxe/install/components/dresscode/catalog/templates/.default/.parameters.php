<?if(!\Bitrix\Main\Loader::includeModule("iblock"))
	return;

$boolCatalog = \Bitrix\Main\Loader::includeModule("catalog");
$boolForms = Bitrix\Main\Loader::includeModule("form");

$arIBlockType = CIBlockParameters::GetIBlockTypes();

//reviews
$arIBlock = array();
$rsIBlock = CIBlock::GetList(array("sort" => "asc"), array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE" => "Y"));
while($arr=$rsIBlock->Fetch()){
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
}

if($arCurrentValues["USE_REVIEW"] == "Y"){
	$arIBlockReview = array();
	$rsIBlockReview = CIBlock::GetList(array("sort" => "asc"), array("TYPE" => $arCurrentValues["REVIEW_IBLOCK_TYPE"], "ACTIVE" => "Y"));
	while($arRew = $rsIBlockReview->Fetch()){
		$arIBlockReview[$arRew["ID"]] = "[".$arRew["ID"]."] ".$arRew["NAME"];
	}
}

//services
if($arCurrentValues["SHOW_SERVICES"] == "Y"){
	$arIBlockServices = array();
	$rsIBlockServices = CIBlock::GetList(array("sort" => "asc"), array("TYPE" => $arCurrentValues["SERVICES_IBLOCK_TYPE"], "ACTIVE" => "Y"));
	while($arService = $rsIBlockServices->Fetch()){
		$arIBlockServices[$arService["ID"]] = "[".$arService["ID"]."] ".$arService["NAME"];
	}
}

//sales
$saleIBlock = array();
$rsSaleIBlock = CIBlock::GetList(array("sort" => "asc"), array("TYPE" => $arCurrentValues["SALE_IBLOCK_TYPE"], "ACTIVE" => "Y"));
while($arNextIblock = $rsSaleIBlock->Fetch()){
	$saleIBlock[$arNextIblock["ID"]] = "[".$arNextIblock["ID"]."] ".$arNextIblock["NAME"];
}

//sales
$sliderIBlock = array();
$rsSliderIBlock = CIBlock::GetList(array("sort" => "asc"), array("TYPE" => $arCurrentValues["MIDDLE_SLIDER_IBLOCK_TYPE"], "ACTIVE" => "Y"));
while($arNextIblock = $rsSliderIBlock->Fetch()){
	$sliderIBlock[$arNextIblock["ID"]] = "[".$arNextIblock["ID"]."] ".$arNextIblock["NAME"];
}

//reviews
if($arCurrentValues["USE_REVIEW"] == "Y"){
	$arTemplateParameters["REVIEW_IBLOCK_TYPE"] = array(
		"PARENT" => "REVIEW_SETTINGS",
		"NAME" => GetMessage("IBLOCK_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlockType,
		"REFRESH" => "Y",
	);

	$arTemplateParameters["REVIEW_IBLOCK_ID"] = array(
		"PARENT" => "REVIEW_SETTINGS",
		"NAME" => GetMessage("IBLOCK_IBLOCK"),
		"TYPE" => "LIST",
		"ADDITIONAL_VALUES" => "Y",
		"VALUES" => $arIBlockReview,
		"REFRESH" => "Y",
	);
}

//services
$arTemplateParameters["SHOW_SERVICES"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("DISPLAY_SERVICES"),
	"DEFAULT" => "Y",
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y"
);

//middle slider
$arTemplateParameters["SHOW_MIDDLE_SLIDER"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("SHOW_MIDDLE_SLIDER"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "N",
	"REFRESH" => "Y",
);

if($arCurrentValues["SHOW_MIDDLE_SLIDER"] == "Y"){
	$arTemplateParameters["MIDDLE_SLIDER_IBLOCK_TYPE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MIDDLE_SLIDER_IBLOCK_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlockType,
		"REFRESH" => "Y",
	);

	$arTemplateParameters["MIDDLE_SLIDER_IBLOCK_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MIDDLE_SLIDER_IBLOCK_ID"),
		"TYPE" => "LIST",
		"ADDITIONAL_VALUES" => "Y",
		"VALUES" => $sliderIBlock,
		"REFRESH" => "Y",
	);
}

if($arCurrentValues["SHOW_SERVICES"] == "Y"){
	$arTemplateParameters["SERVICES_IBLOCK_TYPE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("SERVICES_IBLOCK_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlockType,
		"REFRESH" => "Y",
	);

	$arTemplateParameters["SERVICES_IBLOCK_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("SERVICES_IBLOCK_ID"),
		"TYPE" => "LIST",
		"ADDITIONAL_VALUES" => "Y",
		"VALUES" => $arIBlockServices,
		"REFRESH" => "Y",
	);
}

//sales
$arTemplateParameters["SALE_IBLOCK_TYPE"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("SALE_IBLOCK_TYPE"),
	"TYPE" => "LIST",
	"VALUES" => $arIBlockType,
	"REFRESH" => "Y",
);

$arTemplateParameters["SALE_IBLOCK_ID"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("SALE_IBLOCK_ID"),
	"TYPE" => "LIST",
	"ADDITIONAL_VALUES" => "Y",
	"VALUES" => $saleIBlock,
	"REFRESH" => "Y",
);

$arTemplateParameters["LAZY_LOAD_PICTURES"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("LAZY_LOAD_PICTURES"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y",
);

$arTemplateParameters["HIDE_MEASURES"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("HIDE_MEASURES"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y"
);

$arTemplateParameters["SHOW_SECTION_BANNER"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("SHOW_SECTION_BANNER"),
	"TYPE" => "CHECKBOX",
	"REFRESH" => "Y"
);


?>