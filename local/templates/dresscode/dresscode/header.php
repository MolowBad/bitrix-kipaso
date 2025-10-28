<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use	Bitrix\Main\Page\Asset;
use Bitrix\Main\Application;
use	Bitrix\Main\Localization\Loc;

/**
 * @link https://dw24.su/lib/base/internet_magazin_deluxe/kastomizatsiya_izmenenie_shablona/personalizatsiya_yazykovykh_lang_fraz/
 */
Loc::loadCustomMessages(Application::getDocumentRoot() . SITE_DIR . "lang.php");
Loc::loadMessages(__FILE__);

Loader::requireModule("dw.deluxe");

$settings = DwSettings::getInstance();
extract($settings->getSiteOptions());

$asset = Asset::getInstance();

?>
<!DOCTYPE html>
<html lang="<?=LANGUAGE_ID?>">
	<head>
		<meta charset="<?=SITE_CHARSET?>">
		<meta name="format-detection" content="telephone=no">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
		<link rel="shortcut icon" type="image/x-icon" href="<?=SITE_DIR?>favicon.ico" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="theme-color" content="#3498db">
		<?
			$asset->addCss(SITE_TEMPLATE_PATH . "/fonts/roboto/roboto.css");
			$asset->addCss(SITE_TEMPLATE_PATH . "/themes/" . $TEMPLATE_BACKGROUND_NAME . "/" . $TEMPLATE_THEME_NAME . "/style.css");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/jquery-1.11.0.min.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/jquery.easing.1.3.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/componentLoader.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/rangeSlider.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/maskedinput.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/system.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/topMenu.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/topSearch.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/dwCarousel.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/dwSlider.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/dwZoomer.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/dwTimer.js");
			$asset->addJs(SITE_TEMPLATE_PATH . "/js/colorSwitcher.js");

			CJSCore::Init(["fx", "ajax", "window", "popup", "date", "easing"]);

			if (DwSettings::isPagen()) {
				$asset->addString('<link rel="canonical" href="' . DwSettings::getPagenCanonical() . '" />');
			}

			if (!DwSettings::isBot() && !empty($TEMPLATE_METRICA_CODE)) {
				$asset->addString($TEMPLATE_METRICA_CODE);
			}

			$APPLICATION->ShowHead();
		?>
		<title><?$APPLICATION->ShowTitle();?></title>
	</head>
	<body class="loading <?if(defined("INDEX_PAGE") && INDEX_PAGE == "Y"):?>index<?endif;?><?if(!empty($TEMPLATE_PANELS_COLOR) && $TEMPLATE_PANELS_COLOR != "default"):?> panels_<?=$TEMPLATE_PANELS_COLOR?><?endif;?>">
		<div id="panel">
			<?$APPLICATION->ShowPanel();?>
		</div>
		<div id="foundation">
			<?require_once($settings->getTemplateHeaderPath())?>
			<div id="main"<?if($TEMPLATE_BACKGROUND_NAME != ""):?> class="color_<?=$TEMPLATE_BACKGROUND_NAME?>"<?endif;?>>
				<div class="limiter">
					<div class="compliter">
						<?if(!defined("ERROR_404")):?>
							<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default", Array(
								"AREA_FILE_SHOW" => "sect",
									"AREA_FILE_SUFFIX" => "leftBlock",
									"AREA_FILE_RECURSIVE" => "Y",
									"EDIT_TEMPLATE" => "",
								),
								false
							);?>
						<?endif;?>
						<div id="right">
							<?if(!defined("INDEX_PAGE") && !defined("ERROR_404")):?>
								<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", ".default", Array(
									"START_FROM" => "0",
										"PATH" => "",
										"SITE_ID" => "-",
									),
									false
								);?>
							<?endif;?>
							<?$APPLICATION->ShowViewContent("after_breadcrumb_container");?>
							<?$APPLICATION->ShowViewContent("landing_page_banner_container");?>
							<?$APPLICATION->ShowViewContent("landing_page_top_text_container");?>
