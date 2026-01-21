<?
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

	$arParams["MASKED_FORMAT"] = !empty($arParams["MASKED_FORMAT"]) ? $arParams["MASKED_FORMAT"] : "";
	$arParams["USE_MASKED"] = !empty($arParams["USE_MASKED"]) ? $arParams["USE_MASKED"] : "N";

	if($GLOBALS["USER"]->IsAuthorized()){
		$rsUser = CUser::GetByID($GLOBALS["USER"]->GetID());
		$arUser = $rsUser->Fetch();
		if(!empty($arUser)){
			$arResult["USER_NAME"] = $GLOBALS["USER"]->GetFullName();
			$arResult["USER_PHONE"] = $arUser["PERSONAL_MOBILE"];
		}
	}

	$this->IncludeComponentTemplate();
