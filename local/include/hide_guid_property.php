<?php
/**
 * Хелпер для исключения свойства GUID из данных компонентов на публичной части.
 * Подключение в result_modifier.php нужных шаблонов:
 *   require $_SERVER['DOCUMENT_ROOT'].'/local/include/hide_guid_property.php';
 *   hideGuidFromResult($arResult);
 */

if (!function_exists('hideGuidFromResult')) {
    function hideGuidFromResult(array &$arResult): void
    {
        // Элемент (catalog.element)
        if (!empty($arResult['DISPLAY_PROPERTIES']['GUID'])) {
            unset($arResult['DISPLAY_PROPERTIES']['GUID']);
        }
        if (!empty($arResult['PROPERTIES']['GUID'])) {
            unset($arResult['PROPERTIES']['GUID']);
        }

        // OFFERS одного элемента
        if (!empty($arResult['OFFERS']) && is_array($arResult['OFFERS'])) {
            foreach ($arResult['OFFERS'] as &$offer) {
                if (!empty($offer['DISPLAY_PROPERTIES']['GUID'])) {
                    unset($offer['DISPLAY_PROPERTIES']['GUID']);
                }
                if (!empty($offer['PROPERTIES']['GUID'])) {
                    unset($offer['PROPERTIES']['GUID']);
                }
            }
            unset($offer);
        }

        // Списки (catalog.section)
        if (!empty($arResult['ITEMS']) && is_array($arResult['ITEMS'])) {
            foreach ($arResult['ITEMS'] as &$item) {
                if (!empty($item['DISPLAY_PROPERTIES']['GUID'])) {
                    unset($item['DISPLAY_PROPERTIES']['GUID']);
                }
                if (!empty($item['PROPERTIES']['GUID'])) {
                    unset($item['PROPERTIES']['GUID']);
                }
                if (!empty($item['OFFERS']) && is_array($item['OFFERS'])) {
                    foreach ($item['OFFERS'] as &$offer) {
                        if (!empty($offer['DISPLAY_PROPERTIES']['GUID'])) {
                            unset($offer['DISPLAY_PROPERTIES']['GUID']);
                        }
                        if (!empty($offer['PROPERTIES']['GUID'])) {
                            unset($offer['PROPERTIES']['GUID']);
                        }
                    }
                    unset($offer);
                }
            }
            unset($item);
        }
    }
}

/**
 * TODO: Подключай этот файл в result_modifier.php шаблонов, где нужно гарантированно скрыть GUID.
 * Альтернатива: не добавляй свойство GUID в PROPERTY_CODE/SELECT компонента — тогда оно не попадёт в $arResult.
 */
