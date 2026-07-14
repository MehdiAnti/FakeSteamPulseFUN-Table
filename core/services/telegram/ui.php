<?php

$mainMenu = [

    'inline_keyboard' => [

        [
            [
                'text' => '🔑 Key Prices',
                'callback_data' => 'show_keys',
                'style' => 'primary'
            ]
        ],

        [
            [
                'text' => '🎫 Ticket Prices',
                'callback_data' => 'show_tickets',
                'style' => 'primary'
            ]
        ],

        [
            [
                'text' => 'ℹ️ About',
                'callback_data' => 'about'
            ]
        ]

    ]

];

function buildWelcomeText()
{
    return
        "👋 <b>" . BOT_NAME . "</b>\n\n" .
        "Check real-time prices for 🔑 Keys and 🎫 Tickets across different Steam regions.\n\n" .
        "Choose an option below to begin:";
}

function buildAboutText()
{
    return
        "<b>" . BOT_NAME . "</b>\n" .
        "Version: " . BOT_VERSION . "\n\n" .
        "A hobby project inspired by SteamPulse Web.\n\n" .
        "❤️ Shoutout to my best friend:\n" .
        "@Amirhoseindavat\n\n" .
        "GitHub:\n" .
        "https://github.com/MehdiAnti/FakeSteamPulseFUN-Table\n\n" .
        "Original:\n" .
        "https://github.com/CodeMageIR/SteamPulse_Web";
}

function buildPriceTable($type, $regions)
{
    $marketName =
        ($type === 'key')
        ? 'Mann Co. Supply Crate Key'
        : 'Tour of Duty Ticket';
    
    $title =
        ($type === 'key')
        ? '🔑 Key Price'
        : '🎟 Ticket Price';
    
    $subtitle = $marketName;
    
    $html = "
    <h2>{$title}</h2>" .
    "<p>{$subtitle}</p>";

    $html .= "
    <table bordered striped>";

    $html .= "
    <tr>
        <th>🌎 Region</th>
        <th>💰 Full Price</th>
        <th>💴 Net Price</th>
        <th>📊 Tax</th>
    </tr>
    ";

    $liveCount = 0;
    $cachedCount = 0;
    $offlineCount = 0;
    $cacheAge = 0;

    $sharedPrices = [];

    foreach ($regions as $region) {

        $regionName = $region['name'];
        $currency   = $region['currency'];
        $divide     = $region['divide'];
        $cacheKey   = $region['cachekey'];
        $symbol     = $region['symbol'];

        if (!isset($sharedPrices[$cacheKey])) {

            $sharedPrices[$cacheKey] = getPrice(
                440,
                $currency,
                $marketName,
                $divide
            );
        }

        $result = $sharedPrices[$cacheKey];
        $price = $result['price'];

        switch ($result['status']) {
            case 'live':
                $liveCount++;
                break;

            case 'cached':
                $cachedCount++;
                $cacheAge = max($cacheAge, $result['age']);
                break;

            case 'offline':
                $offlineCount++;
                break;
        }

        if ($price === null) {
            continue;
        }

        $net = $price / 1.15;
        $tax = $price - $net;

        $html .= "
        <tr>
            <td>{$regionName}</td>
            <td>" . number_format($price, 2) . " {$symbol}</td>
            <td>" . number_format($net, 2) . " {$symbol}</td>
            <td>" . number_format($tax, 2) . " {$symbol}</td>
        </tr>";
    }

    $html .= "</table>";

    $total = $liveCount + $cachedCount + $offlineCount;

    if ($total > 0 && $liveCount === $total) {
        $status = "⚡ Data: Live";
    } elseif ($liveCount > 0 || $cachedCount > 0) {

        if ($cachedCount > 0) {

            $mins = floor($cacheAge / 60);
            $secs = $cacheAge % 60;

            $age = $mins > 0
                ? "{$mins}m {$secs}s ago"
                : "{$secs}s ago";

            $status = "⚠️ Data: Mixed (Cached {$age})";

        } else {

            $status = "⚠️ Data: Partial";
        }

    } else {

        $status = "❌ Data: Unavailable";
    }

    $timestamp = time();

    $html .= "
    <footer>
    {$status}<br>
    🕒 Last refreshed:
    <tg-time unix='{$timestamp}' format='wDT'>
    " . date('Y-m-d H:i:s') . "
    </tg-time>
    </footer>";

    return $html;
}
