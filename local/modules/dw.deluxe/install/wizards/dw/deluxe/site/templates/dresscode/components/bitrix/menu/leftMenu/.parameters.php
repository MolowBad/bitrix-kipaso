<?

//d7 namespace
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Iblock;
use Bitrix\Currency;

if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog")){

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

	$arPrice = CCatalogIBlockParameters::getPriceTypesList();
	$arTemplateParameters["IBLOCK_TYPE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MENU_IBLOCK_TYPE"),
		"TYPE" => "LIST",
		"VALUES" => $IBLOCK_TYPE,
		"REFRESH" => "Y"
	);
	$arTemplateParameters["IBLOCK_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MENU_IBLOCK"),
		"TYPE" => "LIST",
		"VALUES" => $IBLOCKS,
		"REFRESH" => "Y"
	);
	$arTemplateParameters["PRODUCTS_LIMIT"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MENU_PRODUCTS_LIMIT"),
		"TYPE" => "STRING",
		"DEFAULT" => "28"
	);
	$arTemplateParameters["HIDE_MEASURES"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MENU_HIDE_MEASURES"),
		"TYPE" => "CHECKBOX",
		"REFRESH" => "Y"
	);
	$arTemplateParameters["PRODUCT_PRICE_CODE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MENU_IBLOCK_PRICE_CODE"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => $arPrice,
	);
	$arTemplateParameters["CONVERT_CURRENCY"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("MENU_CONVERT_CURRENCY"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"REFRESH" => "Y",
	);

	if (isset($arCurrentValues["CONVERT_CURRENCY"]) && $arCurrentValues["CONVERT_CURRENCY"] === "Y"){
		$arTemplateParameters["CURRENCY_ID"] = array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("MENU_CURRENCY_ID"),
			"TYPE" => "LIST",
			"VALUES" => Currency\CurrencyManager::getCurrencyList(),
			"DEFAULT" => Currency\CurrencyManager::getBaseCurrency(),
			"ADDITIONAL_VALUES" => "Y",
		);
	}

}
?>