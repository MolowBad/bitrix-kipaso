<?php

use Bitrix\Main\Page\Asset;

try {
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($reqUri, '/personal/cart/order/') === 0) {
      
        Asset::getInstance()->addString('<script>window.EGRUL_AUTOFILL_CONFIG = {
            debug: false,
            innSelector:    "#soa-property-10, input[name=\"ORDER_PROP_10\"]",
            companyNameSelector: "#soa-property-8, input[name=\"ORDER_PROP_8\"]",
            legalAddressSelector: "#soa-property-9, textarea[name=\"ORDER_PROP_9\"]",
            kppSelector:    "#soa-property-11, input[name=\"ORDER_PROP_11\"]",
            contactPersonSelector: "#soa-property-12, input[name=\"ORDER_PROP_12\"]",
            postalCodeSelector: "input[name=\"ORDER_PROP_16\"], input[name*=\"INDEX\" i], input[name*=\"ZIP\" i]",
            endpoint: "/local/ajax/egrul_lookup.php",
            debounceMs: 500
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/EGRUL-INN/egrul-autofill.js');
    }
    // Подключение на странице редактирования профиля юр. лица
    if (strpos($reqUri, '/personal/profile/') === 0) {
        // Фиксируем точные селекторы для полей профиля ЮЛ (есть дублирующиеся ID, поэтому приоритет по name)
        Asset::getInstance()->addString('<script>window.EGRUL_AUTOFILL_CONFIG = {
            debug: false,
            // ИНН
            innSelector: "input[name=\"ORDER_PROP_10\"], #sppd-property-2",
            // Название компании
            companyNameSelector: "input[name=\"ORDER_PROP_8\"], #sppd-property-0",
            // Юридический адрес (textarea)
            legalAddressSelector: "textarea[name=\"ORDER_PROP_9\"], #sppd-property-1",
            // КПП
            kppSelector: "input[name=\"ORDER_PROP_11\"], #sppd-property-3",
            // Контактное лицо (ID дублируется, используем name)
            contactPersonSelector: "input[name=\"ORDER_PROP_12\"]",
            // Индекс
            postalCodeSelector: "input[name=\"ORDER_PROP_16\"]",
            endpoint: "/local/ajax/egrul_lookup.php",
            debounceMs: 500
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/EGRUL-INN/egrul-autofill.js');
    }
    // Подсказки адресов DaData на странице оформления заказа
    if (strpos($reqUri, '/personal/cart/order/') === 0) {
        Asset::getInstance()->addString('<script>window.ADDRESS_SUGGEST_CONFIG = {
            debug: false,
            endpoint: "/local/ajax/dadata_address.php",
            // Селекторы по умолчанию покрывают большинство шаблонов Bitrix, при необходимости можно уточнить
            citySelector: "#soa-property-17, input[name=\\\"ORDER_PROP_17\\\"]",
            streetSelector: "#soa-property-31, input[name=\\\"ORDER_PROP_31\\\"]",
            houseSelector: "#soa-property-32, input[name=\\\"ORDER_PROP_32\\\"]",
            count: 10,
            language: "ru",
            debounceMs: 250
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/DADATA-ADDRESS/address-suggest.js');
    }
    // Подсказки адресов DaData на странице профиля
    if (strpos($reqUri, '/personal/profile/') === 0) {
        Asset::getInstance()->addString('<script>window.ADDRESS_SUGGEST_CONFIG = {
            debug: false,
            endpoint: "/local/ajax/dadata_address.php",
            count: 10,
            language: "ru",
            debounceMs: 250
        };</script>');
        Asset::getInstance()->addJs('/local/scripts/DADATA-ADDRESS/address-suggest.js');
    }
} catch (\Throwable $e) {
    // silent
}
