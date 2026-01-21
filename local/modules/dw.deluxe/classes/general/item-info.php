<?php
class DwItemInfo
{
	protected static $arSectionTree = array();

	public static function checkTagSectionTree($sectionId, $mainSectionId)
	{

		if (!\Bitrix\Main\Loader::includeModule("iblock")) {
			return false;
		}

		if (!empty($sectionId) && !empty($mainSectionId)) {

			if (empty(self::$arSectionTree[$mainSectionId])) {

				$nav = CIBlockSection::GetNavChain(false, $mainSectionId);
				while ($arSectionPath = $nav->GetNext()) {
					self::$arSectionTree[$mainSectionId][$arSectionPath["ID"]] = $arSectionPath;
				}

			}

			return !empty(self::$arSectionTree[$mainSectionId][$sectionId]);
		}

		return false;
	}

	public static function getSeoByTag($tagName, $tagCode, $iblockId, $sectionId)
	{
		if (empty($tagName) || empty($tagCode) || empty($iblockId) || empty($sectionId)) {
			return false;
		}

		$arResult = array(
			"SEO_TITLE" => $tagName,
			"SEO_TAG_TITLE" => $tagName,
			"SEO_TAG_DESCRIPTION" => "",
			"SEO_KEYWORDS" => "",
			"SECTION_CHAIN" => array()
		);

		$cacheID = array(
			"CACHE_NAME" => "DOUBLE_CATALOG_TAGS_CACHE",
			"SECTION_ID" => $sectionId,
			"IBLOCK_ID" => $iblockId,
			"TAG_CODE" => $tagCode,
			"SITE_ID" => SITE_ID,
		);

		$cacheDir = implode(
			"/",
			[
				'dw.deluxe',
				'classes',
				'item.info',
				'seo.tags',
				SITE_ID
			]
		);

		$oExtraCache = new CPHPCache();

		if ($oExtraCache->InitCache("36000000", serialize($cacheID), $cacheDir)) {
			$arResult = $oExtraCache->GetVars();
		} elseif ($oExtraCache->StartDataCache()) {

			if (!\Bitrix\Main\Loader::includeModule("iblock")) {

				$oExtraCache->AbortDataCache();
				ShowError("modules not installed!");
				return 0;

			}

			$nav = CIBlockSection::GetNavChain(false, $sectionId);
			while ($arItem = $nav->Fetch()) {
				$arSectionIds[$arItem["ID"]] = $arItem["ID"];
			}

			if (!empty($arSectionIds)) {
				$rsList = CIBlockSection::GetList(
					array(
						"DEPTH_LEVEL" => "DESC"
					),
					array(
						"ID" => $arSectionIds,
						"IBLOCK_ID" => $iblockId
					),
					false,
					array(
						"ID",
						"IBLOCK_ID",
						"UF_TAG_TITLE",
						"UF_TAG_KEYWORDS",
						"UF_TAG_DESCRIPTION",
						"UF_TAG_HEADING"
					)
				);
				while ($arNextSection = $rsList->GetNext()) {
					$arSections[$arNextSection["ID"]] = $arNextSection;
				}
			}

			if (!empty($arSections)) {

				foreach ($arSections as $nextSection) {

					if (!empty($nextSection["UF_TAG_TITLE"]) || !empty($nextSection["UF_TAG_KEYWORDS"]) || !empty($nextSection["UF_TAG_DESCRIPTION"]) || !empty($nextSection["UF_TAG_HEADING"])) {

						$arReplace = array(ToUpper(substr($tagName, 0, 1)) . substr($tagName, 1), ToUpper($tagName), ToLower($tagName), $tagName);
						$arSearch = array("#TAG_UPPER_FIRST#", "#TAG_UPPER#", "#TAG_LOWER#", "#TAG#");

						if (!empty($nextSection["UF_TAG_TITLE"])) {
							$arResult["SEO_TITLE"] = str_replace($arSearch, $arReplace, $nextSection["UF_TAG_TITLE"]);
						}

						if (!empty($nextSection["UF_TAG_DESCRIPTION"])) {
							$arResult["SEO_DESCRIPTION"] = str_replace($arSearch, $arReplace, $nextSection["UF_TAG_DESCRIPTION"]);
						}

						if (!empty($nextSection["UF_TAG_KEYWORDS"])) {
							$arResult["SEO_KEYWORDS"] = str_replace($arSearch, $arReplace, $nextSection["UF_TAG_KEYWORDS"]);
						}

						if (!empty($nextSection["UF_TAG_HEADING"])) {
							$arResult["SEO_HEADING"] = str_replace($arSearch, $arReplace, $nextSection["UF_TAG_HEADING"]);
						}

						break(1);

					}

				}

			}

			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cacheDir);
			$CACHE_MANAGER->RegisterTag("iblock_id_" . $iblockId);
			$CACHE_MANAGER->EndTagCache();

			$oExtraCache->EndDataCache($arResult);

			unset($oExtraCache);

		}

		return $arResult;

	}

	public static function get_extra_content(
		$cacheTime = 21285912,
		$cacheType = "Y",
		$cacheID = array(),
		$cacheDir = "/",
		$arParams = array(),
		$arGlobalParams = array(),
		$arElement = array(),
		$opCurrency = null
	)
	{
		global $USER;

		$cacheID["NAME"] = "DOUBLE_CATALOG_ITEM_CACHE";
		$cacheID["CURRECY"] = $opCurrency;
		$cacheID["EXTRA_PARAMS"] = serialize($arParams);

		$cacheDir = implode(
			"/",
			[
				'dw.deluxe',
				'classes',
				'item.info',
				'extra.content',
				SITE_ID,
			]
		);

		$oExtraCache = new CPHPCache();

		if ($cacheType != "N" && $oExtraCache->InitCache($cacheTime, serialize($cacheID), $cacheDir)) {
			$arElement = $oExtraCache->GetVars();
		} elseif ($oExtraCache->StartDataCache()) {

			if (
				!\Bitrix\Main\Loader::includeModule("iblock")
				|| !\Bitrix\Main\Loader::includeModule('highloadblock')
				|| !\Bitrix\Main\Loader::includeModule("catalog")
				|| !\Bitrix\Main\Loader::includeModule("sale")
			) {

				$oExtraCache->AbortDataCache();
				ShowError("modules not installed!");
				return 0;

			}

			$parentElementId = !empty($arElement["PARENT_PRODUCT"]) ? $arElement["PARENT_PRODUCT"]["ID"] : $arElement["ID"];
			$sectionIds = array();
			$arSection = array();

			$arIBlock = CIBlock::GetArrayByID($arGlobalParams["IBLOCK_ID"]);

			if (!empty($arIBlock["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"]) && $arIBlock["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"] == "Y") {
				$arGlobalParams["SECTION_ID"] = !empty($arElement["PARENT_PRODUCT"]) ? $arElement["PARENT_PRODUCT"]["IBLOCK_SECTION_ID"] : $arElement["IBLOCK_SECTION_ID"];
				$keepIblockSectionId = $arIBlock["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"];
			}

			if (
				!empty($arParams["DISPLAY_LAST_SECTION"]) && $arParams["DISPLAY_LAST_SECTION"] == "Y" ||
				!empty($arParams["DISPLAY_SIMILAR"]) && $arParams["DISPLAY_SIMILAR"] == "Y"
			) {

				if (empty($keepIblockSectionId)) {
					$rsGroups = CIBlockElement::GetElementGroups($parentElementId, true);
					while ($arNextGroup = $rsGroups->Fetch()) {
						$arSection[$arNextGroup["DEPTH_LEVEL"]] = $arNextGroup["ID"];
					}

					if (!empty($arSection)) {
						krsort($arSection);
					}

					if (!empty($arSection)) {
						$arElement["LAST_SECTION"] = array_slice($arSection, 0, 1);
						$rsLastSection = CIBlockSection::GetByID($arElement["LAST_SECTION"][0]);
						if ($arLastSection = $rsLastSection->GetNext()) {
							$arElement["LAST_SECTION"] = $arLastSection;
							$arGlobalParams["SECTION_ID"] = $arElement["LAST_SECTION"]["ID"];
						}
					}
				}

				else {
					$rsLastSection = CIBlockSection::GetByID($arGlobalParams["SECTION_ID"]);
					if ($arLastSection = $rsLastSection->GetNext()) {
						$arElement["LAST_SECTION"] = $arLastSection;
					}
				}

				$nav = CIBlockSection::GetNavChain(false, $arGlobalParams["SECTION_ID"]);
				while ($arSectionPath = $nav->GetNext()) {
					$arElement["SECTION_PATH_LIST"][$arSectionPath["ID"]] = $arSectionPath;
					$sectionIds[$arSectionPath["ID"]] = $arSectionPath["ID"];
				}

				if (!empty($sectionIds)) {
					$rsList = CIBlockSection::GetList(array(), array("ID" => $sectionIds, "IBLOCK_ID" => $arGlobalParams["IBLOCK_ID"]), false, array("ID", "IBLOCK_ID", "UF_SHOW_SKU_TABLE"));
					while ($arNextSection = $rsList->GetNext()) {
						if (!empty($arNextSection["UF_SHOW_SKU_TABLE"])) {
							$arElement["SECTION_PATH_LIST"][$arSectionPath["ID"]]["UF_SHOW_SKU_TABLE"] = $arNextSection["UF_SHOW_SKU_TABLE"];
						}
					}
				}

			}

			if (!empty($arParams["DISPLAY_RELATED"]) && $arParams["DISPLAY_RELATED"] == "Y") {
				if (!empty($arElement["PROPERTIES"]["RELATED_PRODUCT"]["VALUE"])) {
					$arSelect = array("ID");
					$arFilter = array("IBLOCK_ID" => $arGlobalParams["IBLOCK_ID"], "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "ID" => $arElement["PROPERTIES"]["RELATED_PRODUCT"]["VALUE"]);
					$rsRelated = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
					$arElement["RELATED_COUNT"] = $rsRelated->SelectedRowsCount();
				}
			}

			if (!empty($arParams["DISPLAY_SIMILAR"]) && $arParams["DISPLAY_SIMILAR"] == "Y") {
				if (!empty($arElement["LAST_SECTION"]["ID"]) || !empty($arElement["PROPERTIES"]["SIMILAR_PRODUCT"]["VALUE"])) {

					if (empty($arElement["PROPERTIES"]["SIMILAR_PRODUCT"]["VALUE"])) {
						$similarFilter = array("IBLOCK_ID" => $arGlobalParams["IBLOCK_ID"], "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "SECTION_ID" => $arElement["LAST_SECTION"]["ID"], "!ID" => $parentElementId);
						$rsSimilar = CIBlockElement::GetList(array(), $similarFilter, false, false, array("ID"));
					} else {
						$similarFilter = array("IBLOCK_ID" => $arGlobalParams["IBLOCK_ID"], "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", "ID" => $arElement["PROPERTIES"]["SIMILAR_PRODUCT"]["VALUE"]);
						$rsSimilar = CIBlockElement::GetList(array(), $similarFilter, false, false, $arSelect);
					}

					$arElement["SIMILAR_COUNT"] = $rsSimilar->SelectedRowsCount();
					$arElement["SIMILAR_FILTER"] = $similarFilter;

				}
			}

			if (!empty($arParams["DISPLAY_BRAND"]) && $arParams["DISPLAY_BRAND"] == "Y") {
				if (!empty($arElement["PROPERTIES"]["ATT_BRAND"]["VALUE"])) {
					$arBrandFilter = array("ID" => $arElement["PROPERTIES"]["ATT_BRAND"]["VALUE"], "ACTIVE" => "Y");
					$rsBrand = CIBlockElement::GetList(array(), $arBrandFilter, false, false, array("ID", "IBLOCK_ID", "NAME", "DETAIL_PAGE_URL", "DETAIL_PICTURE"));
					if ($brandElement = $rsBrand->GetNextElement()) {
						$arElement["BRAND"] = $brandElement->GetFields();
						$arElement["BRAND"]["PICTURE"] = CFile::ResizeImageGet($arElement["BRAND"]["DETAIL_PICTURE"], array("width" => 250, "height" => 50), BX_RESIZE_IMAGE_PROPORTIONAL, false);
					}
				}
			}

			if (!empty($arParams["DISPLAY_FILES_VIDEO"]) && $arParams["DISPLAY_FILES_VIDEO"] == "Y") {
				if (!empty($arElement["PROPERTIES"])) {
					foreach ($arElement["PROPERTIES"] as $ips => $arProperty) {
						if ($arProperty["PROPERTY_TYPE"] == "F" && $arProperty["CODE"] != "MORE_PHOTO" && !empty($arProperty["VALUE"])) {
							if (is_array($arProperty["VALUE"])) {
								foreach ($arProperty["VALUE"] as $ipv => $arPropertyValue) {
									$arTmpFile = CFile::GetByID($arPropertyValue)->Fetch();
									$arTmpFile["PARENT_NAME"] = $arProperty["NAME"];
									$arTmpFile["SRC"] = CFile::GetPath($arTmpFile["ID"]);
									$arElement["FILES"][] = $arTmpFile;
								}
							} else {
								$arTmpFile = CFile::GetByID($arProperty["VALUE"])->Fetch();
								$arTmpFile["PARENT_NAME"] = $arProperty["NAME"];
								$arTmpFile["SRC"] = CFile::GetPath($arTmpFile["ID"]);
								$arElement["FILES"][] = $arTmpFile;
							}
						} elseif ($arProperty["CODE"] == "VIDEO" && !empty($arProperty["VALUE"])) {
							if (is_array($arProperty["VALUE"])) {
								foreach ($arProperty["VALUE"] as $ipv => $arPropertyValue) {
									$arElement["VIDEO"][] = $arPropertyValue;
								}
							} else {
								$arElement["VIDEO"][] = $arProperty["VALUE"];
							}
						}
					}
				}
			}

			if (!empty($arParams["DISPLAY_MORE_PICTURES"]) && $arParams["DISPLAY_MORE_PICTURES"] == "Y") {
				$arResizeParams = array(
					"SMALL_PICTURE" => array(
						"HEIGHT" => 50,
						"WIDTH" => 50
					),
					"REGULAR_PICTURE" => array(
						"HEIGHT" => 300,
						"WIDTH" => 300
					),
					"MEDIUM_PICTURE" => array(
						"HEIGHT" => 500,
						"WIDTH" => 500
					),
					"LARGE_PICTURE" => array(
						"HEIGHT" => 1200,
						"WIDTH" => 1200
					)
				);

				if (!empty($arElement["DETAIL_PICTURE"]) && is_numeric($arElement["DETAIL_PICTURE"])) {
					$arElement["IMAGES"][] = DwItemInfo::get_more_pictures($arElement["DETAIL_PICTURE"], $arResizeParams);
				} else {
					if (!empty($arElement["PARENT_PRODUCT"]["DETAIL_PICTURE"])) {
						$arElement["IMAGES"][] = DwItemInfo::get_more_pictures($arElement["PARENT_PRODUCT"]["DETAIL_PICTURE"], $arResizeParams);
					} else {
						$arElement["IMAGES"][] = array(
							"SMALL_IMAGE" => array("SRC" => SITE_TEMPLATE_PATH . "/images/empty.svg"),
							"MEDIUM_IMAGE" => array("SRC" => SITE_TEMPLATE_PATH . "/images/empty.svg"),
							"LARGE_IMAGE" => array("SRC" => SITE_TEMPLATE_PATH . "/images/empty.svg")
						);
					}
				}

				if (!empty($arElement["PROPERTIES"]["MORE_PHOTO"]["VALUE"])) {
					foreach ($arElement["PROPERTIES"]["MORE_PHOTO"]["VALUE"] as $nextPictureID) {
						$arElement["IMAGES"][] = DwItemInfo::get_more_pictures($nextPictureID, $arResizeParams);
					}
				}
			}


			if (!empty($arParams["DISPLAY_FORMAT_PROPERTIES"]) && $arParams["DISPLAY_FORMAT_PROPERTIES"] == "Y") {
				foreach ($arElement["PROPERTIES"] as $arNextProperty) {
					$arElement["DISPLAY_PROPERTIES"][$arNextProperty["CODE"]] = CIBlockFormatProperties::GetDisplayValue($arElement, $arNextProperty, "catalog_out");
				}
			}

			if (!empty($arGlobalParams["CATALOG_SHOW_TAGS"]) && $arGlobalParams["CATALOG_SHOW_TAGS"] == "Y") {

				$arElement["ELEMENT_TAGS"] = array();
				$arElement["TAGS"] = !empty($arElement["PARENT_PRODUCT"]) ? $arElement["PARENT_PRODUCT"]["TAGS"] : $arElement["TAGS"];

				if (!empty($arElement["TAGS"])) {

					if (!isset($arGlobalParams["TAGS_DETAIL_SECTION_MAX_DELPH_LEVEL"])) {

						$sectionPath = (!empty($arGlobalParams["SECTION_CODE_PATH"]) ? $arGlobalParams["SECTION_CODE_PATH"] : $arGlobalParams["SECTION_CODE"]);

						if (empty($sectionPath) && !empty($arGlobalParams["SECTION_ID"])) {
							$sectionPath = $arGlobalParams["SECTION_ID"];
						}

						$tagPath = $arGlobalParams["SEF_FOLDER"] . $sectionPath . "/";

					}

					else {
						foreach ($arElement["SECTION_PATH_LIST"] as $secId => $nextSection) {
							if ($arGlobalParams["TAGS_DETAIL_SECTION_MAX_DELPH_LEVEL"] >= $nextSection["DEPTH_LEVEL"]) {
								if (!empty($nextSection["SECTION_PAGE_URL"])) {
									$tagPath = $nextSection["SECTION_PAGE_URL"];
								}
							}
						}
					}

					$arTags = explode(",", $arElement["TAGS"]);

					foreach ($arTags as $inx => $tagName) {

						$tagCode = Cutil::translit($tagName, LANGUAGE_ID, array("change_case" => "L", "replace_space" => "-", "replace_other" => "-"));
						$arTag = array("NAME" => $tagName, "CODE" => $tagCode);

						if ($arGlobalParams["TAGS_DETAIL_LINK_VARIANT"] == "SEARCH") {
							$arTag["LINK"] = $arGlobalParams["TAGS_SEARCH_PATH"] . "?" . $arGlobalParams["TAGS_SEARCH_PARAM"] . "=" . $tagName;
						}
						else {
							$arTag["LINK"] = $tagPath . "tag/" . $tagCode . "/";
						}

						$arElement["ELEMENT_TAGS"][$tagCode] = $arTag;
					}

				}

				if (!empty($arElement["ELEMENT_TAGS"])) {

					$arElement["ELEMENT_TAGS"] = array_slice($arElement["ELEMENT_TAGS"], 0, intval($arGlobalParams["MAX_TAGS"]), true);

					uasort($arElement["ELEMENT_TAGS"], function ($a, $b) use ($arGlobalParams) {

						if ($a[$arGlobalParams["TAGS_SORT_FIELD"]] == $b[$arGlobalParams["TAGS_SORT_FIELD"]]) {
							return false;
						}

						if ($arGlobalParams["TAGS_SORT_TYPE"] == "DESC") {
							return ($a[$arGlobalParams["SORT_FIELD"]] > $b[$arGlobalParams["TAGS_SORT_FIELD"]]) ? -1 : 1;
						}

						else {
							if ($arGlobalParams["TAGS_SORT_TYPE"] == "ASC") {
								return ($a[$arGlobalParams["TAGS_SORT_FIELD"]] < $b[$arGlobalParams["TAGS_SORT_FIELD"]]) ? -1 : 1;
							}
						}

					});

				}

			}

			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cacheDir);
			$CACHE_MANAGER->RegisterTag("iblock_id_" . $arElement["IBLOCK_ID"]);
			$CACHE_MANAGER->EndTagCache();

			$oExtraCache->EndDataCache($arElement);

			unset($oExtraCache);

		}

		return $arElement;
	}

	public static function get_more_pictures($pictureID, $arResizeParams, $arPushImage = array())
	{
		$arWaterMark = array();

		$arTemplateSettings = DwSettings::getInstance()->getCurrentSettings();

		if (!empty($arTemplateSettings["TEMPLATE_USE_AUTO_WATERMARK"]) && $arTemplateSettings["TEMPLATE_USE_AUTO_WATERMARK"] == "Y") {
			$arWaterMark = array(
				array(
					"alpha_level" => $arTemplateSettings["TEMPLATE_WATERMARK_ALPHA_LEVEL"],
					"coefficient" => $arTemplateSettings["TEMPLATE_WATERMARK_COEFFICIENT"],
					"position" => $arTemplateSettings["TEMPLATE_WATERMARK_POSITION"],
					"file" => $arTemplateSettings["TEMPLATE_WATERMARK_PICTURE"],
					"color" => $arTemplateSettings["TEMPLATE_WATERMARK_COLOR"],
					"type" => $arTemplateSettings["TEMPLATE_WATERMARK_TYPE"],
					"size" => $arTemplateSettings["TEMPLATE_WATERMARK_SIZE"],
					"fill" => $arTemplateSettings["TEMPLATE_WATERMARK_FILL"],
					"font" => $arTemplateSettings["TEMPLATE_WATERMARK_FONT"],
					"text" => $arTemplateSettings["TEMPLATE_WATERMARK_TEXT"],
					"name" => "watermark",
				)
			);
		}

		$arFileInfo = CFile::GetFileArray($pictureID);

		$arPushImage["SMALL_IMAGE"] = array_change_key_case(CFile::ResizeImageGet($pictureID, array("width" => $arResizeParams["SMALL_PICTURE"]["WIDTH"], "height" => $arResizeParams["SMALL_PICTURE"]["HEIGHT"]), BX_RESIZE_IMAGE_PROPORTIONAL, false), CASE_UPPER);
		$arPushImage["REGULAR_IMAGE"] = array_change_key_case(CFile::ResizeImageGet($pictureID, array("width" => $arResizeParams["REGULAR_PICTURE"]["WIDTH"], "height" => $arResizeParams["REGULAR_PICTURE"]["HEIGHT"]), BX_RESIZE_IMAGE_PROPORTIONAL, false), CASE_UPPER);
		$arPushImage["MEDIUM_IMAGE"] = array_change_key_case(CFile::ResizeImageGet($pictureID, array("width" => $arResizeParams["MEDIUM_PICTURE"]["WIDTH"], "height" => $arResizeParams["MEDIUM_PICTURE"]["HEIGHT"]), BX_RESIZE_IMAGE_PROPORTIONAL, false, $arWaterMark), CASE_UPPER);
		$arPushImage["LARGE_IMAGE"] = array_change_key_case(CFile::ResizeImageGet($pictureID, array("width" => $arResizeParams["LARGE_PICTURE"]["WIDTH"], "height" => $arResizeParams["LARGE_PICTURE"]["HEIGHT"]), BX_RESIZE_IMAGE_PROPORTIONAL, false, $arWaterMark), CASE_UPPER);

		foreach ($arPushImage as $index => $nextPicture) {
			$arPushImage[$index] = array_merge($arFileInfo, $arPushImage[$index]);
		}

		return $arPushImage;
	}

}
