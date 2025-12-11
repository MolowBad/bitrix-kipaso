<?php

if (!function_exists('parseCdekAddress')) {
    function parseCdekAddress(string $fullAddress): array {
        $result = [
            'CITY' => '',
            'STREET' => '',
            'HOUSE' => '',
            'KORPUS' => '',
            'BUILDING' => '',
            'OFFICE' => '',
            'CODE' => ''
        ];

        if (preg_match('/#([A-Z0-9]+)$/u', $fullAddress, $matches)) {
            $result['CODE'] = $matches[1];
            $fullAddress = trim(preg_replace('/#[A-Z0-9]+$/u', '', $fullAddress));
        }

        $parts = array_map('trim', explode(',', $fullAddress));

        if (count($parts) >= 1) {
            $result['CITY'] = preg_replace('/^(г\.?|город)\s*/ui', '', $parts[0]);
        }

        if (count($parts) >= 2) {
            $result['STREET'] = preg_replace('/^(ул\.?|улица|пр\.?|проспект|пер\.?|переулок)\s*/ui', '', $parts[1]);
        }

        if (count($parts) >= 3) {
            $addressPart = $parts[2];

            if (preg_match('/(?:^|\bд\.?\s*|\bдом\s*)(\d+[\da-zA-Zа-яА-Я]*?(?:[\/.\-]\d+[\da-zA-Zа-яА-Я]*)*)/ui', $addressPart, $matches)) {
                $result['HOUSE'] = $matches[1];
            }

            if (preg_match('/(?:к\.?|корп\.?|корпус)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['KORPUS'] = $matches[1];
            }

            if (preg_match('/(?:с\.?|стр\.?|строение)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['BUILDING'] = $matches[1];
            }

            if (preg_match('/(?:оф\.?|офис|кв\.?|квартира)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $addressPart, $matches)) {
                $result['OFFICE'] = $matches[1];
            }
        }

        if (count($parts) > 3) {
            for ($i = 3; $i < count($parts); $i++) {
                $part = $parts[$i];

                if (!$result['KORPUS'] && preg_match('/(?:к\.?|корп\.?|корпус)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $part, $matches)) {
                    $result['KORPUS'] = $matches[1];
                }

                if (!$result['BUILDING'] && preg_match('/(?:с\.?|стр\.?|строение)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $part, $matches)) {
                    $result['BUILDING'] = $matches[1];
                }

                if (!$result['OFFICE'] && preg_match('/(?:оф\.?|офис|кв\.?|квартира)\s*(\d+[а-яА-Яa-zA-Z]*)/ui', $part, $matches)) {
                    $result['OFFICE'] = $matches[1];
                }
            }
        }

        return $result;
    }
}

if (!function_exists('kipasoOnSaleComponentOrderProcess')) {
    function kipasoOnSaleComponentOrderProcess($order): void {
        try {
            if (!$order || !is_object($order)) {
                return;
            }

            $propertyCollection = $order->getPropertyCollection();

            if (!$propertyCollection) {
                return;
            }

            $addressProperty = null;
            $allProps = [];

            foreach ($propertyCollection as $property) {
                $code = (string)$property->getField('CODE');
                $name = (string)$property->getField('NAME');
                $value = trim((string)$property->getValue());

                $allProps[] = [
                    'CODE' => $code,
                    'NAME' => $name,
                    'VALUE' => mb_substr($value, 0, 100)
                ];

                if (in_array(mb_strtoupper($code), ['ADDRESS', 'DELIVERY_ADDRESS', 'АДРЕС_ДОСТАВКИ', 'LOCATION', 'COMPANY_ADR'], true) ||
                    mb_stripos($name, 'Адрес доставки') !== false ||
                    (mb_stripos($name, 'адрес') !== false && mb_stripos($name, 'юридический') === false)) {

                    if (!empty($value) && preg_match('/#[A-Z0-9]+$/u', $value)) {
                        $addressProperty = $property;
                        break;
                    }
                }
            }

            if (!$addressProperty) {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                    'ERROR' => 'Address property with CDEK code not found',
                    'ALL_PROPS' => $allProps,
                ], 'CDEK_ADDRESS_PARSER', '/upload/cdek_parser.log');
                return;
            }

            $fullAddress = trim((string)$addressProperty->getValue());

            if (empty($fullAddress) || !preg_match('/#[A-Z0-9]+$/u', $fullAddress)) {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                    'ERROR' => 'Address does not contain CDEK code',
                    'ADDRESS' => $fullAddress,
                ], 'CDEK_ADDRESS_PARSER', '/upload/cdek_parser.log');
                return;
            }

            $parsed = parseCdekAddress($fullAddress);

            $getPropByCodes = function(array $codes) use ($propertyCollection) {
                $upper = array_map(fn($s) => mb_strtoupper($s), $codes);
                foreach ($propertyCollection as $p) {
                    $code = (string)$p->getField('CODE');
                    if ($code !== '' && in_array(mb_strtoupper($code), $upper, true)) {
                        return $p;
                    }
                }

                foreach ($propertyCollection as $p) {
                    $name = (string)$p->getField('NAME');
                    if ($name !== '' && in_array(mb_strtoupper($name), $upper, true)) {
                        return $p;
                    }
                }
                return null;
            };

            $updated = false;

            if ($parsed['CITY'] && ($cityProp = $getPropByCodes(PROP_CODES_CITY))) {
                $currentValue = trim((string)$cityProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $cityProp->setValue($parsed['CITY']);
                    $updated = true;
                }
            }

            if ($parsed['STREET'] && ($streetProp = $getPropByCodes(PROP_CODES_STREET))) {
                $currentValue = trim((string)$streetProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $streetProp->setValue($parsed['STREET']);
                    $updated = true;
                }
            }

            if ($parsed['HOUSE'] && ($houseProp = $getPropByCodes(PROP_CODES_HOUSE))) {
                $currentValue = trim((string)$houseProp->getValue());
                if (empty($currentValue) || $currentValue === $fullAddress) {
                    $houseProp->setValue($parsed['HOUSE']);
                    $updated = true;
                }
            }

            if ($parsed['KORPUS'] && ($korpusProp = $getPropByCodes(PROP_CODES_KORPUS))) {
                $currentValue = trim((string)$korpusProp->getValue());
                if (empty($currentValue)) {
                    $korpusProp->setValue($parsed['KORPUS']);
                    $updated = true;
                }
            }

            if ($parsed['BUILDING'] && ($buildingProp = $getPropByCodes(PROP_CODES_BUILDING))) {
                $currentValue = trim((string)$buildingProp->getValue());
                if (empty($currentValue)) {
                    $buildingProp->setValue($parsed['BUILDING']);
                    $updated = true;
                }
            }

            if ($parsed['OFFICE'] && ($officeProp = $getPropByCodes(PROP_CODES_OFFICE))) {
                $currentValue = trim((string)$officeProp->getValue());
                if (empty($currentValue)) {
                    $officeProp->setValue($parsed['OFFICE']);
                    $updated = true;
                }
            }

            \Bitrix\Main\Diag\Debug::writeToFile([
                'ORDER_ID' => method_exists($order, 'getId') ? $order->getId() : 'new',
                'FULL_ADDRESS' => $fullAddress,
                'PARSED' => $parsed,
                'UPDATED' => $updated,
                'TIMESTAMP' => date('Y-m-d H:i:s'),
            ], 'CDEK_ADDRESS_PARSER', '/upload/cdek_parser.log');

        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile([
                'ERROR' => $e->getMessage(),
                'FILE' => $e->getFile(),
                'LINE' => $e->getLine(),
                'TRACE' => $e->getTraceAsString(),
            ], 'CDEK_ADDRESS_PARSER_ERROR', '/upload/cdek_parser.log');
        }
    }
}
