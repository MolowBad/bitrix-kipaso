<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

	$arComponentParameters = array(
		"PARAMETERS" => array(
			"META_HEADING" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("CATALOG_TAGS_META_HEADING"),
				"TYPE" => "STRING",
			),
			"META_TITLE" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("CATALOG_TAGS_META_TITLE"),
				"TYPE" => "STRING",
			),
			"META_KEYWORDS" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("CATALOG_TAGS_META_KEYWORDS"),
				"TYPE" => "STRING",
			),
			"META_DESCRIPTION" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("CATALOG_TAGS_META_DESCRIPTION"),
				"TYPE" => "STRING",
			),
			"TAG_NAME" => array(
				"PARENT" => "DATA_SOURCE",
				"NAME" => GetMessage("CATALOG_TAG_NAME"),
				"TYPE" => "STRING",
			),
			"CACHE_TIME" => Array("DEFAULT" => "36000000")
		)
	);

?>
