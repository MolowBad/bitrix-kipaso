<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$APPLICATION->RestartBuffer();
//ajax filter
if($arParams["INSTANT_RELOAD"] == true){
	$arResult["INSTANT_RELOAD"] = "Y";
	$arResult["COMPONENT_CONTAINER_ID"] = "ajaxSection";
}
unset($arResult["COMBO"]);
echo CUtil::PHPToJSObject($arResult, true);
?>