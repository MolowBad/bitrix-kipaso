<?php

declare(strict_types=1);

use Bitrix\Main\Loader;

Loader::requireModule('iblock');
Loader::requireModule('catalog');
Loader::requireModule('sale');
Loader::requireModule('currency');
Loader::requireModule('location');
loader::requireModule('highloadblock');

Loader::registerAutoLoadClasses(
	'dw.deluxe',
	[
		'DigitalWeb\Basket' => 'classes/general/basket.php',
		'DigitalWeb\BasketAjax' => 'classes/general/basket-ajax.php',
		'DigitalWeb\Tools' => 'classes/general/tools.php',
		'DwSkuOffers' => 'classes/general/sku-offers.php',
		'DwProductEvents' => 'classes/general/product-events.php',
		'DwItemInfo' => 'classes/general/item-info.php',
		'DwSettings' => 'classes/general/settings.php',
		'DwBuffer' => 'classes/general/buffer.php',
		'DwPrices' => 'classes/general/prices.php',
		'DwBonus' => 'classes/general/bonus.php'
	]
);
