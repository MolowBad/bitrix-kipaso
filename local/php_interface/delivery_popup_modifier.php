<?php

function modifyBasketPopupHTML($html, $productId)
{
    if (empty($html) || $productId <= 0) {
        return $html;
    }
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/delivery_functions.php';
    $deliveryInfo = getProductDeliveryInfo($productId, 1);
    
    // Генерируем новый HTML блока доставки
    $deliveryHtml = generateDeliveryHTML($deliveryInfo);
    
    // Ищем старый блок наличия и заменяем его
    $oldPattern = '/<div class="availabilityInfo[^>]*>.*?<\/div>/s';
    if (preg_match($oldPattern, $html)) {
        $html = preg_replace($oldPattern, $deliveryHtml, $html, 1);
    } else {
        // Если старый блок не найден, вставляем перед блоком количества
        $html = str_replace('<div class="qtyBlock">', $deliveryHtml . '<div class="qtyBlock">', $html);
    }
    
    return $html;
}

function generateDeliveryHTML($deliveryInfo)
{
    $html = '<div class="delivery-info-container"><div class="availability-status ' . ($deliveryInfo['CSS_CLASS'] ?? 'outOfStock') . '"><strong>' . ($deliveryInfo['TEXT'] ?? 'Нет в наличии') . '</strong>';
    
    // Показываем дату доставки для всех типов кроме "в наличии"
    if (!empty($deliveryInfo['DATE']) && $deliveryInfo['TYPE'] !== 'in_stock') {
        $html .= '<div class="delivery-date">Примерная доставка: ' . $deliveryInfo['DATE'] . '</div>';
    }
    
    if ($deliveryInfo['TYPE'] == 'hybrid') {
        $html .= '<div class="delivery-breakdown">';
        $html .= '<div class="breakdown-item"><span class="stock-badge">✓ В наличии:</span><span>' . ($deliveryInfo['STOCK_QTY'] ?? 0) . ' шт. - сразу</span></div>';
        $html .= '<div class="breakdown-item"><span class="order-badge">⏳ Под заказ:</span><span>' . ($deliveryInfo['DELIVERY_QTY'] ?? 0) . ' шт. (' . ($deliveryInfo['DELIVERY_DAYS'] ?? 0) . ' дн.)</span></div>';
        $html .= '</div>';
    }
    
    // Показываем примечание о сроках для всех заказных товаров
    if (in_array($deliveryInfo['TYPE'], ['on_order', 'hybrid']) && !empty($deliveryInfo['DELIVERY_DAYS'])) {
        $html .= '<div class="delivery-note"><small>Срок включает ' . ProductDeliveryManager::OWEN_DELIVERY_DAYS . ' дня на доставку до нашего склада</small></div>';
    }
    
    $html .= '</div></div>';
    return $html;
}