<?
global $USER;

CPageOption::SetOptionString("main", "nav_page_in_session", "N");
CModule::IncludeModule('highloadblock');

if(!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 360000000;
}

$obCache = new CPHPCache();
$cacheDir = implode(
	"/",
	[
		SITE_ID,
		'templates',
		'dresscode',
		'bitrix.menu',
		'left.menu',
		'result.modifier'
	]
);

if($obCache->InitCache($arParams["CACHE_TIME"], $USER->GetGroups() . SITE_ID . "v1", $cacheDir)){
	$arResult = $obCache->GetVars();
}
elseif($obCache->StartDataCache()){
	if(!empty($arResult)){

		$arMeasureProductsID = array();
		$i = 0;
		$b = 0;

		foreach($arResult as $arElement){

			if($arElement["DEPTH_LEVEL"] == 1){
				$i++;
				$sectionID = $arElement["PARAMS"]["ID"];
				$IBLOCK_ID = $arElement["PARAMS"]["IBLOCK_ID"];
				$arResult["SECTIONS"][$sectionID] = $sectionID;
				$arResult["ITEMS"][$i] = array(
					"TEXT" => $arElement["TEXT"],
					"LINK" => $arElement["LINK"],
					"ID" => $arElement["PARAMS"]["ID"],
					"SELECTED" => $arElement["SELECTED"],
					"PICTURE" => $arElement["PARAMS"]["PICTURE"],
					"IBLOCK_ID" => $arElement["PARAMS"]["IBLOCK_ID"],
					"ELEMENT_CNT" => $arElement["PARAMS"]["ELEMENT_CNT"]
				);
			}

			elseif($arElement["DEPTH_LEVEL"] == 2){
				$b++;
				$from = $arElement["PARAMS"]["FROM_IBLOCK"] <= 100 ? 1 : 2;
				$arResult["SECTIONS"][$arElement["PARAMS"]["ID"]] = $sectionID;
				$arResult["ITEMS"][$i]["ELEMENTS"][$from][$b] = array(
					"TEXT" => $arElement["TEXT"],
					"LINK" => $arElement["LINK"],
					"SELECTED" => $arElement["SELECTED"],
					"PICTURE" => $arElement["PARAMS"]["PICTURE"],
					"ELEMENT_CNT" => $arElement["PARAMS"]["ELEMENT_CNT"]
				);
			}elseif($arElement["DEPTH_LEVEL"] == 3){
				$arResult["SECTIONS"][$arElement["PARAMS"]["ID"]] = $sectionID;
				$arResult["ITEMS"][$i]["ELEMENTS"][$from][$b]["ELEMENTS"][] = array(
					"TEXT" => $arElement["TEXT"],
					"LINK" => $arElement["LINK"],
					"SELECTED" => $arElement["SELECTED"],
					"ELEMENT_CNT" => $arElement["PARAMS"]["ELEMENT_CNT"]
				);
			}

		}

		//get menu product's from property SHOW_MENU
		if(CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog")){

			//restrictions
			$arParams["PRODUCTS_LIMIT"] = !empty($arParams["PRODUCTS_LIMIT"]) ? $arParams["PRODUCTS_LIMIT"] : 30;

			$arFilter = array(
				"!PROPERTY_SHOW_MENU_VALUE" => false,
				"=PROPERTY_SHOW_MENU_VALUE" => "Y",
				"ACTIVE" => "Y"
			);

			if(!empty($arParams["IBLOCK_ID"])){
				$arFilter["IBLOCK_ID"] = $arParams["IBLOCK_ID"];
			}

			if(!empty($arParams["IBLOCK_TYPE"])){
				$arFilter["IBLOCK_TYPE"] = $arParams["IBLOCK_TYPE"];
			}

			//show only available products
			$arFilter[] = array(
				array(
					"LOGIC" => "OR",
					array(
						"=ID" => CIBlockElement::SubQuery("PROPERTY_CML2_LINK", array("=CATALOG_AVAILABLE" => "Y", "ACTIVE_DATE" => "Y", "ACTIVE" => "Y"))
					),
					array(
						"LOGIC" => "AND",
						array("!ID" => CIBlockElement::SubQuery("PROPERTY_CML2_LINK", array("!ID" => false))),
						array("=CATALOG_AVAILABLE" => "Y"),
					),
				)
			);

			$arSelect = array(
				"ID",
				"NAME",
				"IBLOCK_ID",
				"IBLOCK_TYPE",
				"DETAIL_PAGE_URL",
				"IBLOCK_SECTION_ID",
			);

			//get products
			$res = CIBlockElement::GetList(array(), $arFilter, false, array("nTopCount" => $arParams["PRODUCTS_LIMIT"], "iNumPage" => 0), $arSelect);

			//push products
			while($arProduct = $res->GetNext()){
				$arResult["PRODUCTS"][$arResult["SECTIONS"][$arProduct["IBLOCK_SECTION_ID"]]][] = $arProduct;
			}

		}

	}

	$obCache->EndDataCache($arResult);

}
