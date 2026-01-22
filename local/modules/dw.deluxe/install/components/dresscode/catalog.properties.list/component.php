<?

if (empty($arParams["PRODUCT_ID"])) {
	return;
}

//default params
$arParams["CACHE_TIME"] = !empty($arParams["CACHE_TIME"]) ? $arParams["CACHE_TIME"] : "1285912";
$arParams["SORT_PARAMS_VALUE"] = !empty($arParams["SORT_PARAMS_VALUE"]) ? $arParams["SORT_PARAMS_VALUE"] : "5000";
$arParams["DISABLE_PRINT_DIMENSIONS"] = !empty($arParams["DISABLE_PRINT_DIMENSIONS"]) ? $arParams["DISABLE_PRINT_DIMENSIONS"] : "N";
$arParams["DISABLE_PRINT_WEIGHT"] = !empty($arParams["DISABLE_PRINT_WEIGHT"]) ? $arParams["DISABLE_PRINT_WEIGHT"] : "N";

//start cache
if($this->StartResultCache($arParams["CACHE_TIME"], $arParams["PRODUCT_ID"])){

	//load modules
	\Bitrix\Main\Loader::includeModule("iblock");
	\Bitrix\Main\Loader::includeModule("catalog");

	//vars
	$arResult["SECTIONS"] = array();
	$arResult["PUBLIC_GROUPS"] = array();
	$arResult["ANONYMOUS_PROPERTIES"] = array();
	$arDimensions = array();

	//get product id by sku id
	$arProductSkuExist = CCatalogSku::GetProductInfo(intval($arParams["PRODUCT_ID"]));
	if(is_array($arProductSkuExist)){
		$getSkuProductResult = CIBlockElement::GetList(
			array(),
			array(
				"ID" => intval($arProductSkuExist["ID"]),
				"ACTIVE_DATE" => "",
				"ACTIVE" => ""
			),
			false,
			array("nPageSize" => 1),
			array(
				"ID",
				"IBLOCK_ID",
				"CATALOG_WIDTH",
				"CATALOG_WEIGHT",
				"CATALOG_LENGTH",
				"CATALOG_HEIGHT",
			)
		);

		if($getSkuProductObject = $getSkuProductResult->GetNextElement()){
			$arSkuProductFields = $getSkuProductObject->GetFields();
			$arSkuProductProperties = $getSkuProductObject->GetProperties(array("sort" => "asc", "name" => "asc"));
		}
	}

	//get element
	$getProductResult = CIBlockElement::GetList(
		array(),
		array(
			"ID" => intval($arParams["PRODUCT_ID"]),
			"ACTIVE_DATE" => "",
			"ACTIVE" => "",
		),
		false,
		array("nPageSize" => 1),
		array(
			"ID",
			"IBLOCK_ID",
			"CATALOG_WIDTH",
			"CATALOG_WEIGHT",
			"CATALOG_LENGTH",
			"CATALOG_HEIGHT",
		)
	);

	if($getProductObject = $getProductResult->GetNextElement()){
		$arProductFields = $getProductObject->GetFields();
		$arProductProperties = $getProductObject->GetProperties(array("sort" => "asc", "name" => "asc"));
	}

	$arResult["PROPERTIES"] = $arProductProperties;
	if(!empty($arSkuProductProperties)){
		$arResult["PROPERTIES"] = array_merge($arSkuProductProperties, $arResult["PROPERTIES"]);
	}

	if(!empty($arResult["PROPERTIES"])){

		if(!empty($arResult["PROPERTIES"]["CML2_AVAILABLE"])){
			unset($arResult["PROPERTIES"]["CML2_AVAILABLE"]);
		}

		if(!empty($arResult["PROPERTIES"]["MORE_PHOTO"])){
			unset($arResult["PROPERTIES"]["MORE_PHOTO"]);
		}

		foreach($arResult["PROPERTIES"] as $propertyCode => $arNextProperty){
			$arResult["DISPLAY_PROPERTIES"][$propertyCode] = CIBlockFormatProperties::GetDisplayValue($arResult, $arNextProperty, "catalog_out");
		}

	}

	//check print dimensions
	if($arParams["DISABLE_PRINT_DIMENSIONS"] != "Y"){
		$arDimensions["LENGTH"] = array("NAME" => GetMessage("PROPERTIES_CATALOG_LENGTH"), "VALUE" => (!empty($arSkuProductFields["CATALOG_LENGTH"]) ? $arSkuProductFields["CATALOG_LENGTH"] : (!empty($arProductFields["CATALOG_LENGTH"]) ? $arProductFields["CATALOG_LENGTH"] : "")));
		$arDimensions["HEIGHT"] = array("NAME" => GetMessage("PROPERTIES_CATALOG_HEIGHT"), "VALUE" => (!empty($arSkuProductFields["CATALOG_HEIGHT"]) ? $arSkuProductFields["CATALOG_HEIGHT"] : (!empty($arProductFields["CATALOG_HEIGHT"]) ? $arProductFields["CATALOG_HEIGHT"] : "")));
		$arDimensions["WIDTH"] = array("NAME" => GetMessage("PROPERTIES_CATALOG_WIDTH"), "VALUE" => (!empty($arSkuProductFields["CATALOG_WIDTH"]) ? $arSkuProductFields["CATALOG_WIDTH"] : (!empty($arProductFields["CATALOG_WIDTH"]) ? $arProductFields["CATALOG_WIDTH"] : "")));
	}

	//check print weight
	if($arParams["DISABLE_PRINT_WEIGHT"] != "Y"){
		$arDimensions["WEIGHT"] = array("NAME" => GetMessage("PROPERTIES_CATALOG_WEIGHT"), "VALUE" => (!empty($arSkuProductFields["CATALOG_WEIGHT"]) ? $arSkuProductFields["CATALOG_WEIGHT"] : (!empty($arProductFields["CATALOG_WEIGHT"]) ? $arProductFields["CATALOG_WEIGHT"] : "")));
	}

	//push
	foreach($arDimensions as $inx => $nextDimension){
		if(!empty($nextDimension["VALUE"])){
			$arResult["DISPLAY_PROPERTIES"][] = array(
				"ID" => "CML2_CATALOG__".$inx,
				"CODE" => "CML2_CATALOG_".$inx,
				"SORT" => 5000,
				"VALUE" => $nextDimension["VALUE"],
				"DISPLAY_VALUE" => $nextDimension["VALUE"],
				"NAME" => $nextDimension["NAME"]
			);
		}
	}

	if(!empty($arResult["DISPLAY_PROPERTIES"])){
		foreach($arResult["DISPLAY_PROPERTIES"] as $index => $arProp){
			if($arProp["SORT"] <= $arParams["SORT_PARAMS_VALUE"] && !empty($arProp["VALUE"])){
				if($arProp["CODE"] == "MORE_PROPERTIES"){

					//get property group name
					if(preg_match("/\[(.*)\]/", trim($arProp["NAME"]), $groupName)){
						$groupName = !empty($groupName[1]) ? $groupName[1] : "";
					}

					foreach($arProp["VALUE"] as $i => $arValue) {
						$arResult["DISPLAY_PROPERTIES"][] = array(
							"ID" => $arProp["ID"]."_".$i,
							"CODE" => $arProp["PROPERTY_VALUE_ID"][$i],
							"SORT" => $arParams["SORT_PARAMS_VALUE"],
							"VALUE" => $arProp["DESCRIPTION"][$i],
							"DISPLAY_VALUE" => $arProp["DESCRIPTION"][$i],
							"NAME" => !empty($groupName) ? $arValue."[".$groupName."]" : $arValue
						);
					}
					unset($arResult["DISPLAY_PROPERTIES"][$index]);
					continue;

				}
				elseif($arProp["USER_TYPE"] == "HTML"){
					$arResult["DISPLAY_PROPERTIES"][$index]["VALUE"] = $arProp["~VALUE"]["TEXT"];
				}
				elseif($arProp["CODE"] == "VIDEO"){
					unset($arResult["DISPLAY_PROPERTIES"][$index]);
				}
				//multi
				if(is_array($arProp["DISPLAY_VALUE"])){

					//sort display value
					if(!empty($arProp["VALUE_SORT"])){
						array_multisort($arProp["VALUE_SORT"], $arProp["DISPLAY_VALUE"]);
					}

					//push inline
					$arResult["DISPLAY_PROPERTIES"][$index]["DISPLAY_VALUE"] = implode(" / ", $arProp["DISPLAY_VALUE"]);

				}

			}else{
				unset($arResult["DISPLAY_PROPERTIES"][$index]);
			}

		}
	}

	foreach($arResult["PROPERTIES"] as $index => $arProp) {
		if($arProp["CODE"] == "MORE_PROPERTIES"){
			if(!empty($arProp["VALUE"])){
				foreach ($arProp["VALUE"] as $i => &$arValue) {
					$arResult["PROPERTIES"][] = array(
						"CODE" => $arProp["PROPERTY_VALUE_ID"][$i],
						"SORT" => $arParams["SORT_PARAMS_VALUE"],
						"VALUE" => $arProp["DESCRIPTION"][$i],
						"NAME" => $arValue
					);
				}
			}
			unset($arResult["PROPERTIES"][$index]);
		}
		elseif($arProp["PROPERTY_TYPE"] == "F" && $arProp["SORT"] <= $arParams["SORT_PARAMS_VALUE"]){
			if(!empty($arProp["VALUE"])){
				if($arProp["MULTIPLE"] == "Y"){
					foreach($arProp["VALUE"] as $ifx => &$fileID) {
						$rsFile = CFile::GetByID($fileID);
						$arFile = $rsFile->Fetch();
						$arResult["PROPERTIES"][] = array(
							"CODE" => $arFile["ID"],
							"SORT" => $arParams["SORT_PARAMS_VALUE"],
							"PROPERTY_TYPE" => "F",
							"VALUE" => !empty($arProp["DESCRIPTION"][$ifx]) ? '<a href="'.CFile::GetPath($fileID).'">'.$arProp["DESCRIPTION"][$ifx].'</a> ' : '<a href="'.CFile::GetPath($fileID).'">'.$arFile["FILE_NAME"].'</a> ',
							"NAME" => $arProp["NAME"]
						);
					}
				}
				else{
					$rsFile = CFile::GetByID($arProp["VALUE"]);
					$arFile = $rsFile->Fetch();
					$arResult["PROPERTIES"][] = array(
						"CODE" => $arFile["ID"],
						"SORT" => $arParams["SORT_PARAMS_VALUE"],
						"PROPERTY_TYPE" => "F",
						"VALUE" => !empty($arProp["DESCRIPTION"]) ? '<a href="'.CFile::GetPath($fileID).'">'.$arProp["DESCRIPTION"].'</a> ' : '<a href="'.CFile::GetPath($arProp["VALUE"]).'">'.$arFile["FILE_NAME"].'</a> ',
						"NAME" => $arProp["NAME"]
					);
				}
			}
			unset($arResult["PROPERTIES"][$index]);
		}
		elseif($arProp["USER_TYPE"] == "HTML"){
			if(!empty($arProp["~VALUE"]["TEXT"])){
				$arResult["PROPERTIES"][$index]["VALUE"] = $arProp["~VALUE"]["TEXT"];
			}
		}
	}

	foreach($arResult["PROPERTIES"] as $pid => $arPropNext) {
		if($arPropNext["PROPERTY_TYPE"] == "F" && $arPropNext["SORT"] <= $arParams["SORT_PARAMS_VALUE"]){
			$arResult["DISPLAY_PROPERTIES"][$pid] = $arPropNext;
		}
	}

	//smart filter link processing
	if(!empty($arParams["CATALOG_VARIABLES"]["URL_TEMPLATES"]["smart_filter"]) && !empty($arParams["CATALOG_VARIABLES"]["FOLDER"])){

		//check section params
		if(!empty($arParams["SECTION_PATH_LIST"]) && !empty($arParams["LAST_SECTION"])){

			//vars
			$sectionCodePath = "";

			//get section code path
			foreach($arParams["SECTION_PATH_LIST"] as $nextSection){
				if(!empty($nextSection["CODE"])){
					$sectionCodePath .= (!empty($sectionCodePath) ? "/" : "").$nextSection["CODE"];
				}
			}

			//append link to smartFilter
			foreach($arResult["DISPLAY_PROPERTIES"] as &$arProperty){

				//check target
				if($arProperty["FILTRABLE"] == "Y" && $arProperty["MULTIPLE"] != "Y"){

					//check property type
					if(!empty($arProperty["LIST_TYPE"]) && $arProperty["LIST_TYPE"] == "L"){

						//check value
						if(!empty($arProperty["VALUE_XML_ID"])){

							//append catalog folder
							$arProperty["FILTER_URL"] = $arParams["CATALOG_VARIABLES"]["FOLDER"];

							//append filter property value
							$arProperty["FILTER_URL"] .= str_replace("#SMART_FILTER_PATH#", toLower($arProperty["CODE"])."-is-".toLower($arProperty["VALUE_XML_ID"]), $arParams["CATALOG_VARIABLES"]["URL_TEMPLATES"]["smart_filter"]);

							//replace catalog section code / section code path macros
							$arProperty["FILTER_URL"] = str_replace("#SECTION_CODE_PATH#", $sectionCodePath, $arProperty["FILTER_URL"]);
							$arProperty["FILTER_URL"] = str_replace("#SECTION_CODE#", $arParams["LAST_SECTION"]["CODE"], $arProperty["FILTER_URL"]);

						}

					}

				}

			}
		}

	}

	if(!empty($arResult["DISPLAY_PROPERTIES"])){

		//each display properties
		foreach($arResult["DISPLAY_PROPERTIES"] as $nextProperty){

			//check empty value
			if(!empty($nextProperty["VALUE"]) || !empty($nextProperty["LINK"])){

				//check sort
				if($nextProperty["SORT"] <= 5000){

					//get property group name
					if(preg_match("/\[(.*)\]/", trim($nextProperty["NAME"]), $groupName)){

						//check result
						if(!empty($groupName[1])){

							//check group exist
							if(empty($arResult["PUBLIC_GROUPS"][$groupName[1]])){
								$arResult["PUBLIC_GROUPS"][$groupName[1]]["NAME"] = $groupName[1];
							}

						}

						//normalize name
						$nextProperty["NAME"] = preg_replace("/\[.*\]/", "", trim($nextProperty["NAME"]));

						//push properties to current group
						$arResult["PUBLIC_GROUPS"][$groupName[1]]["PROPERTIES"][$nextProperty["ID"]] = $nextProperty;

					}

					//push property to anonymous group
					else{

						//check empty value
						if(!empty($nextProperty["VALUE"]) || !empty($nextProperty["LINK"])){

							//check sort
							if($nextProperty["SORT"] <= 5000){
								$arResult["ANONYMOUS_PROPERTIES"][$nextProperty["ID"]] = $nextProperty;
							}

						}

					}

				}

			}

		}

	}

	$this->setResultCacheKeys(array());
	$this->IncludeComponentTemplate();

}
