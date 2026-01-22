<?php

if (php_sapi_name() === 'cli') {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';


use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    echo "Не удалось подключить модуль iblock\n";
    exit(1);
}


if (!loader::includeModule('catalog')) {
    echo "Не удалось подключить модуль catalog\n";
    exit(1);
}

 $productId = 6486;

 $priceRes = \CPrice::GetList(
    [], 
    [
        'PRODUCT_ID' => $productId
    ]
);



echo "Цены для товара #{$productId}\n";

while ($price = $priceRes->Fetch()) {
    print_r($price);

    $priceId = (int)$price['ID'];
    $currentPrice = (float)$price['PRICE'];

    break;
}

if ($priceId === null) {
    echo "Для товара {$productId} не найдено ни одной цены\n";
    exit;
}

$newPrice = $currentPrice * 1.1;

echo "Старая цена: {$currentPrice}\n";
echo "Новая цена (пока только расчёт): {$newPrice}\n";


echo "ID записи цены: {$priceId}\n";
echo "Текущая цена: {$currentPrice}\n";

$updateFields = [
    'PRODUCT_ID' => $productId,
    'CATALOG_GROUP_ID' => $price['CATALOG_GROUP_ID'],
    'PRICE' => $newPrice,
    'CURRENCY' => $price['CURRENCY'],
];

echo "Обновляем запись цены...\n";
print_r($updateFields);
