<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (
	!CModule::IncludeModule("sale") ||
	!CModule::IncludeModule("catalog") ||
	!CModule::IncludeModule("iblock")
){
	return;
}

global $USER;

$userId = false;
$userEmail = false;
$arResult["ITEMS"] = array();

if(!empty($_SESSION["SUBSCRIBE"]["EMAIL"])){
	$userEmail = addslashes($_SESSION["SUBSCRIBE"]["EMAIL"]);
}

if($USER->isAuthorized()){
	$userId = $USER->getId();
	$userEmail = $USER->getEmail();
}

$result = \Bitrix\Catalog\SubscribeTable::getList(
	array(
		"select" => array(
			"ID",
			"ITEM_ID",
			"TYPE" => "PRODUCT.TYPE",
			"IBLOCK_ID" => "IBLOCK_ELEMENT.IBLOCK_ID",
		),
		"filter" => array(
			"USER_CONTACT" => $userEmail,
			"SITE_ID" => SITE_ID,
			"USER_ID" => $userId,
		),
	)
);

while($subscribeItem = $result->fetch()){
	$arResult["ITEMS"][$subscribeItem["ID"]] = $subscribeItem["ITEM_ID"];
}

$this->IncludeComponentTemplate();
