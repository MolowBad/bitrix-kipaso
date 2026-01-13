# ajax.php — Mind Map (Потоки и функции)

## Вход и инициализация
- Подключение Bitrix окружения
- Получение `$request`, определение `$act`, `$siteId`
- Подключение модулей: `sale` (и по необходимости `catalog`)

## Роутинг по `act`
- `act=addCart`
  - Парсинг параметров: `id`, `q`, `modification`, `modification_price`, `site_id`
  - Загрузка корзины: `SaleBasket::loadItemsForFUser(Fuser::getId(), $siteId)`
  - Вызов: `addToBasket($basket, $productId, $quantity, $currency, $modification, $modPrice)`
  - Сохранение корзины: `$basket->save()`
  - Ответ JSON: `status`, `item_id`, `debug_item_id`, `debug_props`
  - Логирование в `/upload/basket_debug.log`
- `act=getBasketProps`
  - Вход: `basket_id`
  - Чтение `Bitrix\Sale\Internals\BasketPropertyTable` по `BASKET_ID`
  - Ответ JSON: `props[]` (CODE, VALUE, NAME, ID)
- `act=getBasketDebugLog`
  - Вход: `lines` (<=1000, по умолчанию 200)
  - Чтение хвоста файла `/upload/basket_debug.log`
  - Ответ JSON: `content`
- `act=listBasketItems`
  - Загрузка корзины текущего FUSER
  - Перебор элементов: ID, PRODUCT_ID, QUANTITY
  - Получение свойств: `$item->getPropertyCollection()->getPropertyValues()`
  - Ответ JSON: `items[]` с `PROPS { CODE: VALUE }`
- Прочие HTML-акты (пример)
  - Отрисовка фрагментов через `$APPLICATION->IncludeComponent(...)`
  - Возврат HTML (обёртка в `ob_start` / `ob_get_clean`)

## Функции (ключевая: `addToBasket`)
- `addToBasket(\Bitrix\Sale\Basket $basket, int $productId, float $quantity, string $currency, string $modification = '', ?float $modPrice = null)`
  - Поиск существующего элемента с той же модификацией
    - Сравнение по PRODUCT_ID
    - Если модификация задана: сверка по свойству `MODIFICATION`
    - Найдён → увеличить `QUANTITY`; иначе → создать `$basket->createItem('catalog', $productId)`
  - Формирование `$fields` и установка цены
    - Если `modification_price` задан → `PRICE`, `BASE_PRICE`, `CUSTOM_PRICE='Y'`
    - Иначе → `CCatalogProduct::GetOptimalPrice()` (при наличии модуля `catalog`)
  - Порядок сохранения (исправленный)
    - `$item->setFields($fields)`
    - Первое сохранение: `$item->save()` → получить валидный `BASKET_ID`
    - Свойства модификации (если заданы)
      - Массив `$set = [ { CODE: MODIFICATION, VALUE }, { CODE: MODIFICATION_PRICE, VALUE } ]`
      - `$propCollection->setProperty($set)`
      - `$propCollection->save()`
      - Повторное `$item->save()`
    - Контрольная выборка: `BasketPropertyTable::getList(['filter' => ['=BASKET_ID' => $item->getId()]])`
    - Логи: этапы сохранения, найденные записи свойств
  - Возврат: ID элемента корзины (новый или существующий)

## Форматы ответов
- Успех `addCart`
  - `{ status: true, item_id, debug_item_id, debug_props: { MODIFICATION, MODIFICATION_PRICE } }`
- Ошибка
  - `{ status: false, error: "..." }`

## Логирование и диагностика
- Файл: `/upload/basket_debug.log`
  - `addCart`: входные параметры, сохранение, свойства
  - Проверка БД: число свойств для `BASKET_ID`
- Эндпоинты диагностики
  - `listBasketItems`, `getBasketProps`, `getBasketDebugLog`

## Зависимости и окружение
- Bitrix: `sale`, `catalog`
- PHP 7+
- Сайт: `$siteId` (обычно `s1`)
- Пользователь: `Fuser::getId()`
