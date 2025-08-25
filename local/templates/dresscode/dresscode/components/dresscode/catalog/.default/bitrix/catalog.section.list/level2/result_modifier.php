<?php

if(empty($arResult["SECTIONS"][0])){
	$currentPath = $arResult["SECTION"]["PATH"] ?? [];

	$arFilter = [
		"IBLOCK_ID" => $arResult["SECTION"]["IBLOCK_ID"],
		"GLOBAL_ACTIVE" => "Y",
		"ACTIVE" => "Y",
		"SECTION_ID" => $arResult["SECTION"]["PATH"][count($currentPath)-2]["ID"],
		"CNT_ACTIVE" => "Y"
	];

	$iterator = CIBlockSection::GetList(["left_margin" => "asc"], $arFilter, true);
	while($section = $iterator->GetNext()){
		$arResult["SECTIONS"][] = [
			"ID" => $section["ID"],
			"SELECTED" => $arResult["SECTION"]["ID"] == $section["ID"],
			"SECTION_PAGE_URL" => $section["SECTION_PAGE_URL"],
			"NAME" => $section["NAME"],
			"ELEMENT_CNT" => $section["ELEMENT_CNT"]
		];
	}
}
