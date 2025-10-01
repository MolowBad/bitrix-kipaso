<?php
/**
 * Скрипт для тестирования парсера адресов СДЭК
 * 
 * Использование: откройте в браузере /test_cdek_parser.php
 * После проверки работоспособности - удалите этот файл!
 */

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->SetTitle("Тест парсера адресов СДЭК");

// Проверяем, что функция существует
if (!function_exists('parseCdekAddress')) {
    echo '<div style="color: red; padding: 20px; background: #fee;">
        <h2>Ошибка!</h2>
        <p>Функция parseCdekAddress() не найдена.</p>
        <p>Убедитесь, что код добавлен в /local/php_interface/init.php</p>
    </div>';
    require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
    exit;
}

?>

<style>
    .test-container {
        max-width: 1200px;
        margin: 20px auto;
        font-family: Arial, sans-serif;
    }
    .test-case {
        border: 1px solid #ddd;
        margin: 10px 0;
        padding: 15px;
        border-radius: 5px;
        background: #f9f9f9;
    }
    .test-case h3 {
        margin-top: 0;
        color: #333;
    }
    .original {
        background: #e3f2fd;
        padding: 10px;
        border-radius: 3px;
        margin: 10px 0;
        font-family: monospace;
    }
    .result {
        background: #fff;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 3px;
        margin: 10px 0;
    }
    .result table {
        width: 100%;
        border-collapse: collapse;
    }
    .result table td {
        padding: 5px;
        border-bottom: 1px solid #eee;
    }
    .result table td:first-child {
        font-weight: bold;
        width: 150px;
        color: #555;
    }
    .success {
        color: green;
    }
    .info {
        background: #fff3cd;
        border: 1px solid #ffc107;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }
</style>

<div class="test-container">
    <h1>Тестирование парсера адресов СДЭК</h1>
    
    <div class="info">
        <strong>Важно:</strong> После проверки работоспособности удалите этот тестовый файл!
        <br>Путь: <code>/test_cdek_parser.php</code>
    </div>

    <?php
    // Тестовые адреса
    $testCases = [
        [
            'title' => 'Базовый формат',
            'address' => 'Воронеж, ул. Ильюшина, 13 #SVRN129'
        ],
        [
            'title' => 'С корпусом',
            'address' => 'Москва, ул. Ленина, д. 5 корп. 2 #MSK001'
        ],
        [
            'title' => 'С офисом',
            'address' => 'Санкт-Петербург, пр. Невский, 100 оф. 25 #SPB123'
        ],
        [
            'title' => 'Полный адрес',
            'address' => 'Казань, ул. Баумана, д. 10, корп. 1, стр. 2, оф. 301 #KZN456'
        ],
        [
            'title' => 'С сокращениями',
            'address' => 'Екатеринбург, пер. Короткий, д. 7А #EKB789'
        ],
        [
            'title' => 'Без префиксов',
            'address' => 'Новосибирск, Красный проспект, 1 #NSK999'
        ],
        [
            'title' => 'С "г."',
            'address' => 'г. Краснодар, ул. Красная, д. 145 #KRD555'
        ],
        [
            'title' => 'Сложный адрес',
            'address' => 'Челябинск, ул. Труда, 15Б к. 3 стр. 1 кв. 42 #CHL111'
        ]
    ];

    foreach ($testCases as $test) {
        $result = parseCdekAddress($test['address']);
        ?>
        <div class="test-case">
            <h3><?= htmlspecialchars($test['title']) ?></h3>
            
            <div class="original">
                <strong>Исходный адрес:</strong><br>
                <?= htmlspecialchars($test['address']) ?>
            </div>
            
            <div class="result">
                <strong>Результат парсинга:</strong>
                <table>
                    <tr>
                        <td>Город:</td>
                        <td><?= htmlspecialchars($result['CITY']) ?: '<em style="color:#999;">не определено</em>' ?></td>
                    </tr>
                    <tr>
                        <td>Улица:</td>
                        <td><?= htmlspecialchars($result['STREET']) ?: '<em style="color:#999;">не определено</em>' ?></td>
                    </tr>
                    <tr>
                        <td>Дом:</td>
                        <td><?= htmlspecialchars($result['HOUSE']) ?: '<em style="color:#999;">не определено</em>' ?></td>
                    </tr>
                    <tr>
                        <td>Корпус:</td>
                        <td><?= htmlspecialchars($result['KORPUS']) ?: '<em style="color:#999;">не определено</em>' ?></td>
                    </tr>
                    <tr>
                        <td>Строение:</td>
                        <td><?= htmlspecialchars($result['BUILDING']) ?: '<em style="color:#999;">не определено</em>' ?></td>
                    </tr>
                    <tr>
                        <td>Офис/Квартира:</td>
                        <td><?= htmlspecialchars($result['OFFICE']) ?: '<em style="color:#999;">не определено</em>' ?></td>
                    </tr>
                    <tr>
                        <td>Код СДЭК:</td>
                        <td><strong><?= htmlspecialchars($result['CODE']) ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    ?>

    <div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 5px;">
        <h3 class="success">✓ Тест завершен</h3>
        <p>Если все адреса парсятся корректно, можно переходить к тестированию на реальном заказе.</p>
        
        <h4>Следующие шаги:</h4>
        <ol>
            <li>Убедитесь, что в админке созданы необходимые свойства заказа (Город, Улица, Дом и т.д.)</li>
            <li>Оформите тестовый заказ с доставкой СДЭК</li>
            <li>Проверьте лог: <code>/upload/cdek_parser.log</code></li>
            <li>Проверьте заполнение полей в админке заказа</li>
            <li><strong style="color: red;">Удалите этот тестовый файл!</strong></li>
        </ol>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
        <h4>Проверка свойств заказа</h4>
        <?php
        use Bitrix\Main\Loader;
        use Bitrix\Sale\Internals\OrderPropsTable;
        
        if (Loader::includeModule('sale')) {
            echo '<p>Проверяем наличие свойств заказа для типов плательщиков...</p>';
            
            // Получаем типы плательщиков через таблицу
            $personTypesRes = \Bitrix\Sale\Internals\PersonTypeTable::getList([
                'filter' => ['ACTIVE' => 'Y'],
                'order' => ['SORT' => 'ASC']
            ]);
            
            while ($personType = $personTypesRes->fetch()) {
                echo '<h5>Тип плательщика: ' . htmlspecialchars($personType['NAME']) . ' (ID: ' . $personType['ID'] . ')</h5>';
                
                $props = OrderPropsTable::getList([
                    'filter' => ['PERSON_TYPE_ID' => $personType['ID']],
                    'order' => ['SORT' => 'ASC']
                ])->fetchAll();
                
                if ($props) {
                    echo '<table style="width:100%; border-collapse: collapse; margin: 10px 0;">';
                    echo '<tr style="background: #f5f5f5;">
                            <th style="text-align:left; padding: 8px; border: 1px solid #ddd;">Название</th>
                            <th style="text-align:left; padding: 8px; border: 1px solid #ddd;">Код</th>
                            <th style="text-align:left; padding: 8px; border: 1px solid #ddd;">Тип</th>
                          </tr>';
                    
                    $addressProps = ['CITY', 'STREET', 'HOUSE', 'KORPUS', 'BUILDING', 'OFFICE', 'ADDRESS', 'ГОРОД', 'УЛИЦА', 'ДОМ'];
                    
                    foreach ($props as $prop) {
                        $isAddressRelated = in_array(strtoupper($prop['CODE']), $addressProps) || 
                                          stripos($prop['NAME'], 'адрес') !== false ||
                                          stripos($prop['NAME'], 'город') !== false ||
                                          stripos($prop['NAME'], 'улица') !== false;
                        
                        if ($isAddressRelated) {
                            $style = 'background: #e8f5e9;';
                        } else {
                            continue; // Показываем только адресные поля
                        }
                        
                        echo '<tr style="' . $style . '">
                                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($prop['NAME']) . '</td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><code>' . htmlspecialchars($prop['CODE']) . '</code></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($prop['TYPE']) . '</td>
                              </tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p style="color: orange;">Свойства заказа не найдены для этого типа плательщика.</p>';
                }
            }
        }
        ?>
    </div>

</div>

<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>
