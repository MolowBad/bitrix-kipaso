<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
	$arComponentParameters = array(
		"PARAMETERS" => array(
			"HIDE_MEASURES" => array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("BASKET_WINDOW_HIDE_MEASURES"),
				"TYPE" => "CHECKBOX",
				"REFRESH" => "Y"
			),
			"PRODUCT_ID" => array(
				"NAME" => GetMessage("BASKET_WINDOW_PRODUCT_ID"),
				"PARENT" => "BASE",
				"TYPE" => "STRING"
			),
			"SITE_ID" => array(
				"NAME" => GetMessage("BASKET_WINDOW_SITE_ID"),
				"PARENT" => "BASE",
				"TYPE" => "STRING"
			),
			"CACHE_TIME" => array(
				"DEFAULT" => "36000000"
			),
		)
	);
?>
