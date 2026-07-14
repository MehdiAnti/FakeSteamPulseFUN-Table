<?php

function getPrice($appid, $currency, $market_hash_name, $divide = 1)
{
    $cache = getPriceCache();

    $key = "{$appid}_{$currency}_" . rawurlencode($market_hash_name);

    if (
        isset($cache[$key]) &&
        (time() - $cache[$key]['time']) < PRICE_CACHE_TTL
    ) {
        return [
            'price'  => $cache[$key]['price'],
            'status' => 'cached',
            'age'    => time() - $cache[$key]['time']
        ];
    }

    $url =
        "https://steamcommunity.com/market/priceoverview/" .
        "?appid={$appid}" .
        "&currency={$currency}" .
        "&market_hash_name=" . urlencode($market_hash_name);

    $json = @file_get_contents($url);

    if (!$json) {

        if (isset($cache[$key])) {
            return [
                'price'  => $cache[$key]['price'],
                'status' => 'cached',
                'age'    => time() - $cache[$key]['time']
            ];
        }

        return [
            'price'  => null,
            'status' => 'offline',
            'age'    => 0
        ];
    }

    $obj = json_decode($json);

    if (!$obj || !isset($obj->lowest_price)) {

        if (isset($cache[$key])) {
            return [
                'price'  => $cache[$key]['price'],
                'status' => 'cached',
                'age'    => time() - $cache[$key]['time']
            ];
        }

        return [
            'price'  => null,
            'status' => 'offline',
            'age'    => 0
        ];
    }

    $price = preg_replace(
        "/[^0-9\.]/",
        '',
        $obj->lowest_price
    );

    $price /= $divide;

    $cache[$key] = [
        'price' => $price,
        'time'  => time()
    ];

    savePriceCache($cache);

    return [
        'price'  => $price,
        'status' => 'live',
        'age'    => 0
    ];
}

function steamTestPrice($marketHashName, $currency)
{
    $url =
        "https://steamcommunity.com/market/priceoverview/" .
        "?appid=440" .
        "&currency={$currency}" .
        "&market_hash_name=" . urlencode($marketHashName);

    $start = microtime(true);

    $raw = @file_get_contents($url);

    $time = round((microtime(true) - $start) * 1000);

    if ($raw === false) {
        return [
            'success' => false,
            'status' => 'Steam request failed',
            'time' => $time,
            'url' => $url,
            'raw' => false
        ];
    }

    $json = json_decode($raw, true);

    if (!is_array($json)) {
        return [
            'success' => false,
            'status' => 'Invalid JSON',
            'time' => $time,
            'url' => $url,
            'raw' => $raw
        ];
    }

    $price = null;

    if (isset($json['lowest_price'])) {
        $price = preg_replace(
            "/[^0-9\.]/",
            '',
            $json['lowest_price']
        );
    }

    return [
        'success' => true,
        'status' => 'OK',
        'time' => $time,
        'url' => $url,
        'raw' => $raw,
        'json' => $json,
        'price' => $price
    ];
}

function steamMarketHealth()
{
    $start = microtime(true);

    $context = stream_context_create([
        'http' => [
            'method' => 'HEAD',
            'timeout' => 5
        ]
    ]);

    $ok = @fopen(
        'https://steamcommunity.com/market/',
        'r',
        false,
        $context
    );

    $time = round((microtime(true) - $start) * 1000);

    return [
        'success' => $ok !== false,
        'time' => $time
    ];
}
