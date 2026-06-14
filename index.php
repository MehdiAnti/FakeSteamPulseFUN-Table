<?php
// ==========================================================
// Fake SteamPulse Telegram Bot - Support Table (RichMessage)
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($uri === '/ping') {
        echo "pong";
        http_response_code(200);
        exit;
    }
}

$BOT_TOKEN = getenv("TELEGRAM_BOT_TOKEN");
$API_URL = "https://api.telegram.org/bot{$BOT_TOKEN}/";

define('REFRESH_COOLDOWN', 60);
define('COOLDOWN_FILE', __DIR__ . '/cooldowns.json');

function telegramRequest($method, $data = []) {
    global $API_URL;

    $ch = curl_init($API_URL . $method);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function sendMessage($chat_id, $text, $reply_markup = null) {

    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    if ($reply_markup) {
        $payload['reply_markup'] = $reply_markup;
    }

    return telegramRequest('sendMessage', $payload);
}

function sendRichMessage($chat_id, $html, $reply_markup = null) {

    $payload = [
        'chat_id' => $chat_id,
        'rich_message' => [
            'html' => $html
        ]
    ];

    if ($reply_markup) {
        $payload['reply_markup'] = $reply_markup;
    }

    return telegramRequest('sendRichMessage', $payload);
}

function answerCallbackQuery($callback_id, $text = null, $alert = false) {

    $payload = [
        'callback_query_id' => $callback_id
    ];

    if ($text) {
        $payload['text'] = $text;
        $payload['show_alert'] = $alert;
    }

    telegramRequest('answerCallbackQuery', $payload);
}

function getCooldowns() {

    if (!file_exists(COOLDOWN_FILE)) {
        return [];
    }

    $json = file_get_contents(COOLDOWN_FILE);

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function saveCooldowns($data) {

    file_put_contents(
        COOLDOWN_FILE,
        json_encode($data)
    );
}

function canRefresh($userId, $type) {

    $cooldowns = getCooldowns();

    $key = $userId . '_' . $type;

    if (!isset($cooldowns[$key])) {
        return true;
    }

    return (time() - $cooldowns[$key]) >= REFRESH_COOLDOWN;
}

function markRefresh($userId, $type) {

    $cooldowns = getCooldowns();

    $cooldowns[$userId . '_' . $type] = time();

    saveCooldowns($cooldowns);
}

function getPrice($appid, $currency, $market_hash_name, $divide = 1) {

    $url =
        "https://steamcommunity.com/market/priceoverview/" .
        "?appid={$appid}" .
        "&currency={$currency}" .
        "&market_hash_name=" . urlencode($market_hash_name);

    $json = @file_get_contents($url);

    if (!$json) {
        return null;
    }

    $obj = json_decode($json);

    if (!$obj || !isset($obj->lowest_price)) {
        return null;
    }

    $price = preg_replace(
        "/[^0-9\.]/",
        '',
        $obj->lowest_price
    );

    return $price / $divide;
}

$regions = [
    ["USA",        1, 1],
    ["Argentina",  1, 1],
    ["Turkey",     1, 1],
    ["Ukraine",   18, 1],
    ["Russia",     5, 100],
    ["Brazil",     7, 100],
    ["India",     24, 1],
    ["Kazakhstan",37, 1],
    ["China",     23, 1]
];

$mainMenu = [
    'inline_keyboard' => [
        [
            [
                'text' => '🔑 Key Prices',
                'callback_data' => 'show_keys'
            ]
        ],
        [
            [
                'text' => '🎫 Ticket Prices',
                'callback_data' => 'show_tickets'
            ]
        ],
        [
            [
                'text' => 'ℹ️ Info',
                'callback_data' => 'about'
            ]
        ]
    ]
];

function getCurrencySymbol($region) {

    return match($region) {
        "Ukraine" => "₴",
        "Russia" => "руб.",
        "India" => "₹",
        "Brazil" => "R$",
        "Kazakhstan" => "₸",
        "China" => "¥",
        default => "$"
    };
}

function buildPriceTable($type, $regions) {

    $marketName =
        ($type === 'key')
            ? 'Mann Co. Supply Crate Key'
            : 'Tour of Duty Ticket';

    $title =
        ($type === 'key')
            ? '🔑 Key Prices'
            : '🎫 Ticket Prices';

    $html = "<h1>{$title}</h1>";

    $html .= "<table>";

    $html .= "
        <tr>
            <th>Region</th>
            <th>Full Price</th>
            <th>Net Price</th>
            <th>Tax</th>
        </tr>
    ";

    foreach ($regions as $region) {

        $regionName = $region[0];
        $currency   = $region[1];
        $divide     = $region[2];

        $price = getPrice(
            440,
            $currency,
            $marketName,
            $divide
        );

        if ($price === null) {
            continue;
        }

        $net = $price / 1.15;
        $tax = $price - $net;

        $symbol = getCurrencySymbol($regionName);

        $html .= "
            <tr>
                <td>{$regionName}</td>
                <td>" . number_format($price, 2, '.', '') . " {$symbol}</td>
                <td>" . number_format($net, 2, '.', '') . " {$symbol}</td>
                <td>" . number_format($tax, 2, '.', '') . " {$symbol}</td>
            </tr>
        ";
    }

    $html .= "</table>";

    return $html;
}
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$chat_id =
    $update["message"]["chat"]["id"]
    ?? $update["callback_query"]["message"]["chat"]["id"]
    ?? null;

$user_id =
    $update["message"]["from"]["id"]
    ?? $update["callback_query"]["from"]["id"]
    ?? null;

$text =
    $update["message"]["text"]
    ?? null;

$data =
    $update["callback_query"]["data"]
    ?? null;

$callback_id =
    $update["callback_query"]["id"]
    ?? null;

if (!$chat_id) {
    exit;
}

function sendWelcomeMenu($chat_id, $mainMenu) {

    $welcome =
        "👋 <b>Welcome to Fake SteamPulse Bot!</b>\n\n" .
        "Check real-time prices for 🔑 Keys and 🎫 Tickets across different Steam regions.\n\n" .
        "Choose an option below to begin:";

    sendMessage(
        $chat_id,
        $welcome,
        $mainMenu
    );
}

function sendAbout($chat_id) {

    $text =
        "This is a hobby project based on my best friend SteamPulse Web project.\n\n" .
        "Shoutout to him for his amazing work! @Amirhoseindavat ♡\n\n" .
        "Repo:\n" .
        "https://github.com/MehdiAnti/FakeSteamPulseFUN\n\n" .
        "Original:\n" .
        "https://github.com/CodeMageIR/SteamPulse_Web";

    sendMessage(
        $chat_id,
        $text,
        [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Back to Menu',
                        'callback_data' => 'back_menu'
                    ]
                ]
            ]
        ]
    );
}

function sendPriceTable($chat_id, $user_id, $type, $regions) {

    markRefresh(
        $user_id,
        $type
    );

    $html = buildPriceTable(
        $type,
        $regions
    );

    $buttons = [
        'inline_keyboard' => [
            [
                [
                    'text' => '🔄 Refresh',
                    'callback_data' => 'refresh_' . $type
                ]
            ],
            [
                [
                    'text' => '🏠 Back to Menu',
                    'callback_data' => 'back_menu'
                ]
            ]
        ]
    ];

    sendRichMessage(
        $chat_id,
        $html,
        $buttons
    );
}

if ($text === '/start') {

    sendWelcomeMenu(
        $chat_id,
        $mainMenu
    );

    exit;
}

if ($text === '/about') {

    sendAbout($chat_id);

    exit;
}

if ($data && $callback_id) {
    answerCallbackQuery($callback_id);
}

if ($data === 'show_keys') {

    sendPriceTable(
        $chat_id,
        $user_id,
        'key',
        $regions
    );

    exit;
}

if ($data === 'show_tickets') {

    sendPriceTable(
        $chat_id,
        $user_id,
        'ticket',
        $regions
    );

    exit;
}

if ($data === 'refresh_key') {

    if (!canRefresh($user_id, 'key')) {

        answerCallbackQuery(
            $callback_id,
            'Please wait 60 seconds before refreshing again.',
            true
        );

        exit;
    }

    sendPriceTable(
        $chat_id,
        $user_id,
        'key',
        $regions
    );

    exit;
}

if ($data === 'refresh_ticket') {

    if (!canRefresh($user_id, 'ticket')) {

        answerCallbackQuery(
            $callback_id,
            'Please wait 60 seconds before refreshing again.',
            true
        );

        exit;
    }

    sendPriceTable(
        $chat_id,
        $user_id,
        'ticket',
        $regions
    );

    exit;
}

if ($data === 'back_menu') {

    sendWelcomeMenu(
        $chat_id,
        $mainMenu
    );

    exit;
}

if ($data === 'about') {

    sendAbout($chat_id);

    exit;
}
