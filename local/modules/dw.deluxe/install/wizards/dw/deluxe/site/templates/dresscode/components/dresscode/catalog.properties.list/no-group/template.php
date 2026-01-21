<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?$this->setFrameMode(true);?>
<?$propertyCounter=0;?>
<?if(!empty($arResult["DISPLAY_PROPERTIES"])):?>
    <div class="elementProperties">
        <div class="headingBox">
            <div class="heading">
                <?=GetMessage("CATALOG_ELEMENT_CHARACTERISTICS_SHORT");?>
            </div>
            <div class="moreProperties">
                <a href="#" class="morePropertiesLink"><?=GetMessage("CATALOG_ELEMENT_MORE_PROPERTIES")?></a>
            </div>
        </div>
        <div class="propertyList">
            <?foreach ($arResult["DISPLAY_PROPERTIES"] as $ip => $arProperty):?>
                <?if(!empty($arProperty["DISPLAY_VALUE"]) && ++$propertyCounter <= $arParams["COUNT_PROPERTIES"]):?>
                    <?if(gettype($arProperty["DISPLAY_VALUE"]) == "array"){
                        $arProperty["DISPLAY_VALUE"] = implode(" / ", $arProperty["DISPLAY_VALUE"]);
                    }?>
                    <div class="propertyTable">
                        <div class="propertyName"><?=preg_replace("/\[.*\]/", "", $arProperty["NAME"])?></div>
                        <div class="propertyValue">
                            <?if($arProperty["PROPERTY_TYPE"] == "E" || $arProperty["PROPERTY_TYPE"] == "S"):?>
                                <?=$arProperty["DISPLAY_VALUE"]?>
                            <?else:?>
                                <?if(!empty($arProperty["FILTER_URL"])):?>
                                    <a href="<?=$arProperty["FILTER_URL"]?>" class="analog"><?=$arProperty["DISPLAY_VALUE"]?></a>
                                <?else:?>
                                    <?=$arProperty["DISPLAY_VALUE"]?>
                                <?endif;?>
                            <?endif;?>
                        </div>
                    </div>
                <?endif;?>
            <?endforeach;?>
        </div>
    </div>
<?endif;?>