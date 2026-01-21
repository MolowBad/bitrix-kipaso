<?
if(!empty($arResult["SECTIONS"])){
	foreach ($arResult["SECTIONS"]  as $inc => $arNextSection) {
		if(empty($arNextSection["PICTURE"]) || $arNextSection["DEPTH_LEVEL"] <= 1){
			unset($arResult["SECTIONS"][$inc]);
		}
	}
}?>