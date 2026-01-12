<?php
// Подключаем ядро
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die('Не удалось подключить модуль iblock');
}

$iblockId = 16; // ID вашего инфоблока
$el = new CIBlockElement;

// Счётчик обновлённых элементов
$updated = 0;

// Проходим по всем активным элементам
$nav = false; // без разбивки на страницы
$rs = CIBlockElement::GetList(
    ['ID'=>'ASC'],
    [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE'    => 'Y',
    ],
    false,
    $nav,
    ['ID', 'PREVIEW_TEXT']
);

while ($ar = $rs->Fetch()) {
    $id = (int)$ar['ID'];
    // Если уже пусто — можно пропустить
    if (trim($ar['PREVIEW_TEXT']) === '') {
        continue;
    }
    // Обновляем поле PREVIEW_TEXT в пустую строку
    $fields = ['PREVIEW_TEXT' => ''];
    if ($el->Update($id, $fields)) {
        $updated++;
    } else {
        // При необходимости логируем неудачные обновления
        trigger_error("Не удалось обновить элемент #$id: ".$el->LAST_ERROR, E_USER_WARNING);
    }
}

echo "Готово. Обновлено элементов: $updated\n";
