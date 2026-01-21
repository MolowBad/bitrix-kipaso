<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die;
}

$arServices = [
	'main' => [
		'NAME' => GetMessage('SERVICE_MAIN_SETTINGS'),
		'STAGES' => [
			'files.php',
			'template.php',
			'menu.php',
			'settings.php'
		]
	],
	'iblock' => [
		'NAME' => GetMessage('SERVICE_IBLOCK_DEMO_DATA'),
		'STAGES' => [
			'types.php',
			'brands.php',
			'banners.php',
			'advantages.php',
			'blog.php',
			'faq.php',
			'landing.php',
			'sales.php',
			'slider.php',
			'slider_bottom.php',
			'slider_content.php',
			'collection.php',
			'services.php',
			'reviews.php',
			'reviewsMagazine.php',
			'news.php',
			'catalog.php',
			'catalog2.php',
			'catalog3.php'
		]
	],
	'sale' => [
		'NAME' => GetMessage('SERVICE_SALE_DEMO_DATA'),
		'STAGES' => [
			'locations.php',
			'step1.php',
			'step2.php',
			'step3.php'
		]
	],
	'catalog' => [
		'NAME' => GetMessage('SERVICE_CATALOG_SETTINGS'),
		'STAGES' => [
			'index.php'
		]
	]
];
