<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
    //comp mode
    $this->setFrameMode(false);

    //load modules
    if(CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog") && CModule::IncludeModule("dw.deluxe")){

    //global vars
    global $APPLICATION;

    $arSortFields = array(
        "SHOW_COUNTER" => array(
            "ID" => "SHOW_COUNTER",
            "ORDER"=> "DESC",
            "CODE" => "SHOW_COUNTER",
            "NAME" => GetMessage("CATALOG_SORT_FIELD_SHOWS")
        ),
        "NAME" => array( // РїР°СЂР°РјРµС‚СЂ РІ url
            "ID" => "NAME",
            "ORDER"=> "ASC", //РІ РІРѕР·СЂР°СЃС‚Р°СЋС‰РµРј РїРѕСЂСЏРґРєРµ
            "CODE" => "NAME", // РљРѕРґ РїРѕР»СЏ РґР»СЏ СЃРѕСЂС‚РёСЂРѕРІРєРё
            "NAME" => GetMessage("CATALOG_SORT_FIELD_NAME") // РёРјСЏ РґР»СЏ РІС‹РІРѕРґР° РІ РїСѓР±Р»РёС‡РЅРѕР№ С‡Р°СЃС‚Рё, СЂРµРґР°РєС‚РёСЂРѕРІР°С‚СЊ РІ (/lang/ru/section.php)
        ),
        "PRICE_ASC" => array(
            "ID" => "PRICE_ASC",
            "ORDER"=> "ASC",
            "CODE" => "PROPERTY_MINIMUM_PRICE",  // РёР·РјРµРЅРµРЅ РґР»СЏ СЃРѕСЂС‚РёСЂРѕРІРєРё РїРѕ РўРџ
            "NAME" => GetMessage("CATALOG_SORT_FIELD_PRICE_ASC")
        ),
        "PRICE_DESC" => array(
            "ID" => "PRICE_DESC",
            "ORDER"=> "DESC",
            "CODE" => "PROPERTY_MAXIMUM_PRICE", // РёР·РјРµРЅРµРЅ РґР»СЏ СЃРѕСЂС‚РёСЂРѕРІРєРё РїРѕ РўРџ
            "NAME" => GetMessage("CATALOG_SORT_FIELD_PRICE_DESC")
        )
    );

    //get section ID for smart filter
    $arFilter = array(
        "ACTIVE" => "Y",
        "GLOBAL_ACTIVE" => "Y",
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
    );

    if(!empty($arResult["VARIABLES"]["SECTION_ID"])){
        $arFilter["ID"] = $arResult["VARIABLES"]["SECTION_ID"];
    }

    elseif(!empty($arResult["VARIABLES"]["SECTION_CODE"])){
        $arFilter["=CODE"] = $arResult["VARIABLES"]["SECTION_CODE"];
    }

    //start cache
    $obCache = new CPHPCache();
	$cacheDir = implode(
		"/",
		[
			SITE_ID,
			'templates',
			'dresscode',
			'dresscode.catalog',
			'.default',
			'section'
		]
	);

    //get from cache
    if($obCache->InitCache(36000000, serialize($arFilter), $cacheDir)){
        $arCachedVars = $obCache->GetVars();
        $arCurSection = $arCachedVars["SECTION"];
        $arResult["PRICE_SORT_FROM_PROPERTY"] = $arCachedVars["PRICE_SORT_FROM_PROPERTY"];
        $arResult["IPROPERTY_VALUES"] = $arCachedVars["IPROPERTY_VALUES"];
        $arResult["SECTION_BANNERS"] = $arCachedVars["SECTION_BANNERS"];
        $arResult["BASE_PRICE"] = $arCachedVars["BASE_PRICE"];
    }

    //no cache
    elseif($obCache->StartDataCache()){

        $arCurSection = array();
        $dbRes = CIBlockSection::GetList(array(), $arFilter, false, array("ID", "CODE", "NAME", "IBLOCK_ID", "DEPTH_LEVEL", "DESCRIPTION", "UF_TOP_DESCRIPTION"));

        if($arCurSection = $dbRes->GetNext()){

            if(defined("BX_COMP_MANAGED_CACHE")){
                global $CACHE_MANAGER;
                $CACHE_MANAGER->StartTagCache($cacheDir);
                $CACHE_MANAGER->RegisterTag("iblock_id_".$arParams["IBLOCK_ID"]);
                $CACHE_MANAGER->EndTagCache();
            }

        }

        else{
            if(!$arCurSection = $dbRes->GetNext()){
                $arCurSection = array();
            }
        }

        $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($arCurSection["IBLOCK_ID"], $arCurSection["ID"]);
        $arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();
        $arResult["BASE_PRICE"] = CCatalogGroup::GetBaseGroup();

        //get template settings
        $arTemplateSettings = DwSettings::getInstance()->getCurrentSettings();
        if(!empty($arTemplateSettings)){
            $arResult["PRICE_SORT_FROM_PROPERTY"] = !empty($arTemplateSettings["TEMPLATE_USE_AUTO_SAVE_PRICE"]) ? $arTemplateSettings["TEMPLATE_USE_AUTO_SAVE_PRICE"] : "N";
        }

        // get section banner
        $arResult["SECTION_BANNERS"] = array();
        if(empty($arParams["SHOW_SECTION_BANNER"]) || !empty($arParams["SHOW_SECTION_BANNER"]) && $arParams["SHOW_SECTION_BANNER"] == "Y"){
            if(!empty($arCurSection["ID"])){
                $arSectionID = array();
                $navChain = CIBlockSection::GetNavChain($arParams["IBLOCK_ID"], $arCurSection["ID"]);
                while($arNextSection = $navChain->GetNext()){
                    $arSectionID[$arNextSection["ID"]] = $arNextSection["ID"];
                }
                if(!empty($arSectionID)){
                    $rsSection = CIBlockSection::GetList(array("DEPTH_LEVEL" => "DESC"), array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ID" => $arSectionID, "ACTIVE" => "Y", "GLOBAL_ACTIVE" => "Y"), false, array("ID", "NAME", "IBLOCK_ID", "UF_BANNER", "UF_BANNER_LINK"));
                    while($arSection = $rsSection->GetNext()){
                        //check section first banners
                        if(!empty($arSection["UF_BANNER"])){
                            foreach($arSection["UF_BANNER"] as $ib => $bannerID){
                                if(!empty($bannerID)){
                                    $arResult["SECTION_BANNERS"][$ib]["IMAGE"] = CFile::ResizeImageGet($bannerID, array("width" => 2560, "height" => 1440), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                                    if(!empty($arSection["UF_BANNER_LINK"][$ib])){
                                        $arResult["SECTION_BANNERS"][$ib]["LINK"] = $arSection["UF_BANNER_LINK"][$ib];
                                    }
                                }
                            }
                            break(1);
                        }
                    }
                }
            }
        }

        $obCache->EndDataCache(
            array(
                "SECTION" => $arCurSection,
                "BASE_PRICE" => $arResult["BASE_PRICE"],
                "SECTION_BANNERS" => $arResult["SECTION_BANNERS"],
                "IPROPERTY_VALUES" => $arResult["IPROPERTY_VALUES"],
                "PRICE_SORT_FROM_PROPERTY" => $arResult["PRICE_SORT_FROM_PROPERTY"]
            )
        );

     }

    if($arResult["PRICE_SORT_FROM_PROPERTY"] == "N"){
        $arSortFields["PRICE_ASC"] = array(
            "ID" => "PRICE_ASC",
            "ORDER"=> "ASC",
            "CODE" => "CATALOG_PRICE_".$arResult["BASE_PRICE"]["ID"],
            "NAME" => GetMessage("CATALOG_SORT_FIELD_PRICE_ASC")
        );
        $arSortFields["PRICE_DESC"] = array(
            "ID" => "PRICE_DESC",
            "ORDER"=> "DESC",
            "CODE" => "CATALOG_PRICE_".$arResult["BASE_PRICE"]["ID"],
            "NAME" => GetMessage("CATALOG_SORT_FIELD_PRICE_DESC")
        );
    }

    if(!empty($_REQUEST["SORT_FIELD"]) && !empty($arSortFields[$_REQUEST["SORT_FIELD"]])){
        setcookie("CATALOG_SORT_FIELD", $_REQUEST["SORT_FIELD"], time() + 60 * 60 * 24 * 30 * 12 * 2, "/");
        $arParams["ELEMENT_SORT_FIELD"] = $arSortFields[$_REQUEST["SORT_FIELD"]]["CODE"];
        $arParams["ELEMENT_SORT_ORDER"] = $arSortFields[$_REQUEST["SORT_FIELD"]]["ORDER"];
        $arSortFields[$_REQUEST["SORT_FIELD"]]["SELECTED"] = "Y";
    }

    elseif(!empty($_COOKIE["CATALOG_SORT_FIELD"]) && !empty($arSortFields[$_COOKIE["CATALOG_SORT_FIELD"]])){ // COOKIE
        $arParams["ELEMENT_SORT_FIELD"] = $arSortFields[$_COOKIE["CATALOG_SORT_FIELD"]]["CODE"];
        $arParams["ELEMENT_SORT_ORDER"] = $arSortFields[$_COOKIE["CATALOG_SORT_FIELD"]]["ORDER"];
        $arSortFields[$_COOKIE["CATALOG_SORT_FIELD"]]["SELECTED"] = "Y";
    }

    $arSortProductNumber = array(
        30 => array("NAME" => 30),
        60 => array("NAME" => 60),
        90 => array("NAME" => 90)
    );

    if(!empty($_REQUEST["SORT_TO"]) && $arSortProductNumber[$_REQUEST["SORT_TO"]]){
        setcookie("CATALOG_SORT_TO", $_REQUEST["SORT_TO"], time() + 60 * 60 * 24 * 30 * 12 * 2, "/");
        $arSortProductNumber[$_REQUEST["SORT_TO"]]["SELECTED"] = "Y";
        $arParams["PAGE_ELEMENT_COUNT"] = $_REQUEST["SORT_TO"];
    }
    elseif(!empty($_COOKIE["CATALOG_SORT_TO"]) && $arSortProductNumber[$_COOKIE["CATALOG_SORT_TO"]]){
        $arSortProductNumber[$_COOKIE["CATALOG_SORT_TO"]]["SELECTED"] = "Y";
        $arParams["PAGE_ELEMENT_COUNT"] = $_COOKIE["CATALOG_SORT_TO"];
    }

    $arTemplates = array(
         "SQUARES" => array(
            "CLASS" => "squares"
        ),
         "LINE" => array(
            "CLASS" => "line"
        ),
         "TABLE" => array(
            "CLASS" => "table"
        )
    );

    if(!empty($_REQUEST["VIEW"]) && $arTemplates[$_REQUEST["VIEW"]]){
        setcookie("CATALOG_VIEW", $_REQUEST["VIEW"], time() + 60 * 60 * 24 * 30 * 12 * 2);
        $arTemplates[$_REQUEST["VIEW"]]["SELECTED"] = "Y";
        $arParams["CATALOG_TEMPLATE"] = $_REQUEST["VIEW"];
    }
    elseif(!empty($_COOKIE["CATALOG_VIEW"]) && $arTemplates[$_COOKIE["CATALOG_VIEW"]]){
        $arTemplates[$_COOKIE["CATALOG_VIEW"]]["SELECTED"] = "Y";
        $arParams["CATALOG_TEMPLATE"] = $_COOKIE["CATALOG_VIEW"];
    }
    else{
        $arTemplates[key($arTemplates)]["SELECTED"] = "Y";
    }
    ?>
    <h1><?$APPLICATION->ShowTItle(true);?></h1>
    <?if(!empty($arParams["CATALOG_SHOW_TAGS"]) && $arParams["CATALOG_SHOW_TAGS"] == "Y"):?>
        <?$arTags = $APPLICATION->IncludeComponent(
        "dresscode:catalog.tags",
        "",
        array(
            "CACHE_TIME" => $arParams["CACHE_TIME"],
            "CACHE_TYPE" => $arParams["CACHE_TYPE"],
            "IBLOCK_ID" => $arParams["IBLOCK_ID"],
            "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
            "SEF_FOLDER" => $arResult["FOLDER"],
            "SEF_URL_TEMPLATES" => $arParams["SEF_URL_TEMPLATES"],
            "SECTION_ID" => !empty($arCurSection["ID"]) ? $arCurSection["ID"] : $arResult["VARIABLES"]["SECTION_ID"],
            "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
            "SECTION_CODE_PATH" => $arResult["VARIABLES"]["SECTION_CODE_PATH"],
            "HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
            "INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
            "USE_IBLOCK_MAIN_SECTION" => $arParams["CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION"],
            "USE_IBLOCK_MAIN_SECTION_TREE" => $arParams["CATALOG_TAGS_USE_IBLOCK_MAIN_SECTION_TREE"],
            "CURRENT_TAG" => $arResult["VARIABLES"]["TAG"],
            "MESSAGE_404" => $arParams["MESSAGE_404"],
            "SET_STATUS_404" => $arParams["SET_STATUS_404"],
            "SHOW_404" => $arParams["SHOW_404"],
            "FILE_404" => $arParams["FILE_404"],
            "MAX_TAGS" => $arParams["CATALOG_MAX_TAGS"],
            "SECTION_DEPTH_LEVEL" => $arCurSection["DEPTH_LEVEL"],
            "TAGS_MAX_DEPTH_LEVEL" => $arParams["CATALOG_TAGS_MAX_DEPTH_LEVEL"],
            "MAX_VISIBLE_TAGS_DESKTOP" => $arParams["CATALOG_MAX_VISIBLE_TAGS_DESKTOP"],
            "MAX_VISIBLE_TAGS_MOBILE" => $arParams["CATALOG_MAX_VISIBLE_TAGS_MOBILE"],
            "HIDE_TAGS_ON_MOBILE" => $arParams["CATALOG_HIDE_TAGS_ON_MOBILE"],
            "SORT_FIELD" => $arParams["CATALOG_TAGS_SORT_FIELD"],
            "SORT_TYPE" => $arParams["CATALOG_TAGS_SORT_TYPE"],
        ),
        false,
            array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
        );?>
    <?endif;?>
    <?if(!empty($arCurSection["UF_TOP_DESCRIPTION"]) && empty($arTags)):?>
        <?if(!DwSettings::isPagen()):?>
            <div class="sectionTopDescription"><?=$arCurSection["~UF_TOP_DESCRIPTION"]?></div>
        <?endif;?>
    <?endif;?>
    <?if(empty($arResult["SECTION_BANNERS"]) && isset($arParams["SHOW_MIDDLE_SLIDER"]) && $arParams["SHOW_MIDDLE_SLIDER"] == "Y"):?>
        <?$APPLICATION->IncludeComponent(
            "dresscode:slider",
            "middle",
            array(
                "LAZY_LOAD_PICTURES" => !empty($arParams["LAZY_LOAD_PICTURES"]) ? $arParams["LAZY_LOAD_PICTURES"] : "N",
                "IBLOCK_TYPE" => $arParams["MIDDLE_SLIDER_IBLOCK_TYPE"],
                "IBLOCK_ID" => $arParams["MIDDLE_SLIDER_IBLOCK_ID"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "COMPONENT_TEMPLATE" => "middle",
                "PICTURE_WIDTH" => "1476",
                "PICTURE_HEIGHT" => "202"
            ),
            false
         );?>
    <?endif;?>
    <?$this->SetViewTarget("smartFilter");?>
    <?if(empty($arTags)):?>
        <?$APPLICATION->IncludeComponent(
            "bitrix:catalog.section.list",
            "level2",
            array(
                "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
                "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                "COUNT_ELEMENTS" => $arParams["SECTION_COUNT_ELEMENTS"],
                "TOP_DEPTH" => 1,
                "SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
                "VIEW_MODE" => $arParams["SECTIONS_VIEW_MODE"],
                "SHOW_PARENT_NAME" => $arParams["SECTIONS_SHOW_PARENT_NAME"],
                "HIDE_SECTION_NAME" => (isset($arParams["SECTIONS_HIDE_SECTION_NAME"]) ? $arParams["SECTIONS_HIDE_SECTION_NAME"] : "N"),
                "ADD_SECTIONS_CHAIN" => "N",
            ),
            $component,
            array("HIDE_ICONS" => "Y")
        );?>
    <?endif;?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:catalog.smart.filter",
        ".default",
        array(
            "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
            "IBLOCK_ID" => $arParams["IBLOCK_ID"],
            "SECTION_ID" => $arCurSection["ID"],
            "FILTER_NAME" => $arParams["FILTER_NAME"],
            "PREFILTER_NAME" => "preFilter",
            "PRICE_CODE" => $arParams["FILTER_PRICE_CODE"],
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => $arParams["CACHE_TIME"],
            "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
            "SAVE_IN_SESSION" => "N",
            "FILTER_VIEW_MODE" => $arParams["FILTER_VIEW_MODE"],
            "XML_EXPORT" => "Y",
            "SECTION_TITLE" => "NAME",
            "SECTION_DESCRIPTION" => "DESCRIPTION",
            "HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
            "TEMPLATE_THEME" => $arParams["TEMPLATE_THEME"],
            "CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
            "CURRENCY_ID" => $arParams["CURRENCY_ID"],
            "SEF_MODE" => "Y",
            "SEF_RULE" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["smart_filter"],
            "SMART_FILTER_PATH" => $arResult["VARIABLES"]["SMART_FILTER_PATH"],
            "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
            "COMPONENT_TEMPLATE" => ".default",
            "DISPLAY_ELEMENT_COUNT" => "Y",
            "AJAX_MODE" => "N",
            "INSTANT_RELOAD" => !empty($arParams["FILTER_INSTANT_RELOAD"]) ? $arParams["FILTER_INSTANT_RELOAD"] : "Y",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_HISTORY" => "Y",
            "SECTION_CODE" => "",
            "SECTION_CODE_PATH" => ""
        ),
        false
    );?>
    <?
    }
    $this->EndViewTarget();?>
    <div id="catalog">
        <?if(!empty($arResult["SECTION_BANNERS"])):?>
            <div id="catalog-section-banners">
                <ul class="slideBox">
                    <?foreach ($arResult["SECTION_BANNERS"] as $isc => $arNextBanner):?>
                    <?if(!empty($arNextBanner["IMAGE"])):?>
                        <li>
                            <a<?if(!empty($arNextBanner["LINK"])):?> href="<?=$arNextBanner["LINK"]?>"<?endif;?>>
                            <?if(!empty($arParams["LAZY_LOAD_PICTURES"]) && $arParams["LAZY_LOAD_PICTURES"] == "Y"):?>
                                <img src="<?=$templateFolder?>/images/lazy.jpg" data-lazy="<?=$arNextBanner["IMAGE"]["src"]?>" class="lazy" alt="">
                            <?else:?>
                                <img src="<?=$arNextBanner["IMAGE"]["src"]?>" class="lazy" alt="">
                            <?endif;?>
                            </a>
                        </li>
                    <?endif;?>
                    <?endforeach;?>
                </ul>
                <a href="#" class="catalog-section-banners-btn-left"></a>
                <a href="#" class="catalog-section-banners-btn-right"></a>
                <script>
                    $(function() {
                        $("#catalog-section-banners").dwSlider({
                            rightButton: ".catalog-section-banners-btn-right",
                            leftButton: ".catalog-section-banners-btn-left",
                            delay: 6000,
                            speed: 1000
                        });
                    });
                </script>
            </div>
        <?endif;?>
        <?$APPLICATION->IncludeComponent(
        "bitrix:catalog.section.list",
        "catalog-pictures",
        array(
            "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
            "IBLOCK_ID" => $arParams["IBLOCK_ID"],
            "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
            "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
            "CACHE_TYPE" => $arParams["CACHE_TYPE"],
            "CACHE_TIME" => $arParams["CACHE_TIME"],
            "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
            "COUNT_ELEMENTS" => $arParams["SECTION_COUNT_ELEMENTS"],
            "TOP_DEPTH" => 1,
            "SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
            "VIEW_MODE" => $arParams["SECTIONS_VIEW_MODE"],
            "SHOW_PARENT_NAME" => $arParams["SECTIONS_SHOW_PARENT_NAME"],
            "LAZY_LOAD_PICTURES" => !empty($arParams["LAZY_LOAD_PICTURES"]) ? $arParams["LAZY_LOAD_PICTURES"] : "N",
            "HIDE_SECTION_NAME" => (isset($arParams["SECTIONS_HIDE_SECTION_NAME"]) ? $arParams["SECTIONS_HIDE_SECTION_NAME"] : "N"),
            "ADD_SECTIONS_CHAIN" => "N",
        ),
        $component,
        array("HIDE_ICONS" => "Y")
        );?>
        <div id="catalogLine">
            <div class="column oFilter">
                <a href="#" class="oSmartFilter btn-simple btn-micro"><span class="ico"></span><?=GetMessage("CATALOG_FILTER")?></a>
            </div>
            <?if(!empty($arSortFields)):?>
                <div class="column">
                    <div class="label">
                        <?=GetMessage("CATALOG_SORT_LABEL");?>
                    </div>
                    <div class="dropDownList sortFields panel-change">
                        <?
                            //check selected item
                            foreach($arSortFields as $arSortField){
                                if($arSortField["SELECTED"] == "Y"){
                                    $arSortSelected = $arSortField;
                                }
                            }
                            //check found selected item
                            if(empty($arSortSelected)){
                                $selectItemIndex = array_key_first($arSortFields);
                                $arSortSelected = $arSortFields[$selectItemIndex];
                                $arSortFields[$selectItemIndex]["SELECTED"] = "Y";
                            }
                        ?>
                        <?if(!empty($arSortSelected)):?>
                            <div class="dropDownSelected"><?=$arSortSelected["NAME"]?></div>
                        <?endif;?>
                        <div class="dropDownItems">
                            <?foreach($arSortFields as $arSortFieldCode => $arSortField):?>
                                <div class="dropDownItem<?if($arSortField["SELECTED"] == "Y"):?> selected<?endif;?>" data-value="<?=$APPLICATION->GetCurPageParam("SORT_FIELD=".$arSortFieldCode, array("SORT_FIELD"));?>" data-direction='<?=\Bitrix\Main\Web\Json::encode(array("SORT_FIELD" => $arSortField))?>'><?=$arSortField["NAME"]?></div>
                            <?endforeach;?>
                        </div>
                    </div>
                </div>
            <?endif;?>
            <?if(!empty($arSortProductNumber)):?>
                <div class="column">
                    <div class="label">
                        <?=GetMessage("CATALOG_SORT_TO_LABEL");?>
                    </div>
                     <div class="dropDownList countElements panel-change">
                         <?
                            //check selected item
                            foreach($arSortProductNumber as $arSortNumberElement){
                                if($arSortNumberElement["SELECTED"] == "Y"){
                                    $arNumberSelected = $arSortNumberElement;
                                }
                            }
                            //check found selected item
                            if(empty($arNumberSelected)){
                                $selectItemIndex = array_key_first($arSortProductNumber);
                                $arNumberSelected = $arSortProductNumber[$selectItemIndex];
                                $arSortProductNumber[$selectItemIndex]["SELECTED"] = "Y";
                            }
                        ?>
                        <?if(!empty($arNumberSelected)):?>
                            <div class="dropDownSelected"><?=$arNumberSelected["NAME"]?></div>
                        <?endif;?>
                        <div class="dropDownItems">
                            <?foreach($arSortProductNumber as $arSortNumberElementId => $arSortNumberElement):?>
                                <div class="dropDownItem<?if($arSortNumberElement["SELECTED"] == "Y"):?> selected<?endif;?>" data-value="<?=$APPLICATION->GetCurPageParam("SORT_TO=".$arSortNumberElementId, array("SORT_TO"));?>" data-direction='<?=\Bitrix\Main\Web\Json::encode(array("SORT_TO" => $arSortNumberElementId))?>'><?=$arSortNumberElement["NAME"]?></div>
                            <?endforeach;?>
                        </div>
                    </div>
                </div>
             <?endif;?>
            <?if(!empty($arTemplates)):?>
                <div class="column">
                    <div class="label">
                        <?=GetMessage("CATALOG_VIEW_LABEL");?>
                    </div>
                    <div class="viewList">
                        <?foreach($arTemplates as $arTemplatesCode => $arNextTemplate):?>
                            <div class="element"><a href="#" class="panel-click <?=$arNextTemplate["CLASS"]?><?if($arNextTemplate["SELECTED"] == "Y"):?> selected<?endif;?>" data-direction='<?=\Bitrix\Main\Web\Json::encode(array("VIEW" => $arTemplatesCode))?>'></a></div>
                        <?endforeach;?>
                    </div>
                 </div>
             <?endif;?>
        </div>
        <div id="ajaxSection">
            <?reset($arTemplates);?>
            <?$APPLICATION->IncludeComponent(
            "dresscode:catalog.section",
            !empty($arParams["CATALOG_TEMPLATE"]) ? strtolower($arParams["CATALOG_TEMPLATE"]) : strtolower(key($arTemplates)),
            array(
                "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                "ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
                "ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"] ,
                "ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
                "ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
                "PROPERTY_CODE" => $arParams["LIST_PROPERTY_CODE"],
                "META_KEYWORDS" => $arParams["LIST_META_KEYWORDS"],
                "META_DESCRIPTION" => $arParams["LIST_META_DESCRIPTION"],
                "BROWSER_TITLE" => $arParams["LIST_BROWSER_TITLE"],
                "INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
                "BASKET_URL" => $arParams["BASKET_URL"],
                "ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
                "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
                "SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
                "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
                "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
                "FILTER_NAME" => $arParams["FILTER_NAME"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "CACHE_FILTER" => $arParams["CACHE_FILTER"],
                "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                "SET_TITLE" => $arParams["SET_TITLE"],
				"MESSAGE_404" => $arParams["MESSAGE_404"],
				"SET_STATUS_404" => $arParams["SET_STATUS_404"],
				"SHOW_404" => $arParams["SHOW_404"],
				"FILE_404" => $arParams["FILE_404"],
                "DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
                "PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
                "LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
                "PRICE_CODE" => $arParams["PRICE_CODE"],
                "USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
                "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
                "HIDE_MEASURES" => $arParams["HIDE_MEASURES"],
                "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
                "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
                "ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
                "PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
                "PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
                "SHOW_SECTION_BANNER" => !empty($arParams["SHOW_SECTION_BANNER"]) ? $arParams["SHOW_SECTION_BANNER"] : "Y",
                "DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
                "DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
                "PAGER_TITLE" => $arParams["PAGER_TITLE"],
                "PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
                "PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
                "PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
                "PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
                "PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
                "OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
                "OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
                "OFFERS_PROPERTY_CODE" => $arParams["LIST_OFFERS_PROPERTY_CODE"],
                "OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
                "OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
                "OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
                "OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
                "OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],
                "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
                "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
                "SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
                "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
                'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
                'CURRENCY_ID' => $arParams['CURRENCY_ID'],
                'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
                'LABEL_PROP' => $arParams['LABEL_PROP'],
                'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
                'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
                'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
                'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
                'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
                'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
                'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
                'MESS_BTN_BUY' => $arParams['MESS_BTN_BUY'],
                'MESS_BTN_ADD_TO_BASKET' => $arParams['MESS_BTN_ADD_TO_BASKET'],
                'MESS_BTN_SUBSCRIBE' => $arParams['MESS_BTN_SUBSCRIBE'],
                'MESS_BTN_DETAIL' => $arParams['MESS_BTN_DETAIL'],
                'MESS_NOT_AVAILABLE' => $arParams['MESS_NOT_AVAILABLE'],
                "USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
                'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
                "LAZY_LOAD_PICTURES" => !empty($arParams["LAZY_LOAD_PICTURES"]) ? $arParams["LAZY_LOAD_PICTURES"] : "N",
                'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
                "ENABLED_SKU_FILTER" => "Y",
                "ADD_SECTIONS_CHAIN" => (isset($arParams["ADD_SECTIONS_CHAIN"]) ? $arParams["ADD_SECTIONS_CHAIN"] : ''),
                "HIDE_DESCRIPTION_TEXT" => "Y",
                "AJAX_OPTION_HISTORY" => "Y",
                "AJAX_OPTION_JUMP" => "N",
                "ADD_EDIT_BUTTONS" => "Y",
                "AJAX_MODE" => "N",
            ),
            $component
            );?>
        </div>
        <?if(isset($arParams["DISPLAY_SUBSCRIBE"]) && $arParams["DISPLAY_SUBSCRIBE"] == "Y"):?>
            <?$APPLICATION->IncludeComponent(
            "dresscode:catalog.subscribe",
            ".default",
            array(
                "COMPONENT_TEMPLATE" => ".default",
                "RUBRIC_ID" => intval($arParams["SUBSCRIBE_RUBRIC_ID"]),
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "SITE_ID" => SITE_ID,
            ),
            false,
            array("HIDE_ICONS" => "Y", "COMPONENT_ACTIVE" => "Y")
            );?>
        <?endif;?>
        <?if(!empty($arCurSection["DESCRIPTION"]) && empty($arTags)):?>
            <?if(!DwSettings::isPagen()):?>
                <div class="sectionTopDescription"><?=$arCurSection["~DESCRIPTION"]?></div>
            <?endif;?>
        <?endif;?>
        <?if(!empty($arParams["CATALOG_SHOW_TAGS"]) && $arParams["CATALOG_SHOW_TAGS"] == "Y"):?>
            <?if(!empty($arTags)):?>
                <?$APPLICATION->IncludeComponent(
                "dresscode:catalog.tags.meta",
                "",
                Array(
                "META_HEADING" => $arTags["SEO"]["SEO_HEADING"],
                "META_TITLE" => $arTags["SEO"]["SEO_TITLE"],
                "META_KEYWORDS" => $arTags["SEO"]["SEO_KEYWORDS"],
                "META_DESCRIPTION" => $arTags["SEO"]["SEO_DESCRIPTION"],
                "TAG_NAME" => $arTags["CURRENT_TAG"]["NAME"]
                ),
                false,
                array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                )?>
            <?endif;?>
        <?endif;?>
    </div>
    <script>
        var catalogSiteId = "<?=SITE_ID?>";
        var catalogAjaxPath = "<?=$templateFolder?>/ajax/ajax.php";
        var catalogAdditonal = <?=\Bitrix\Main\Web\Json::encode(array("SORT_NUMBER" => $arSortProductNumber, "SORT" => $arSortFields, "TEMPLATES" => $arTemplates));?>
    </script>
    <?$this->addExternalJS($templateFolder."/js/catalog-panel.js");?>
    <?$this->SetViewTarget("menuRollClass");?> menuRolled<?$this->EndViewTarget();?>
    <?$this->SetViewTarget("hiddenZoneClass");?>hiddenZone<?$this->EndViewTarget();?>
