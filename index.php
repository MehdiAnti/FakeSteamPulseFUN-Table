<?php
// ==========================================================
// Fake SteamPulse Telegram Bot - Support Table (RichMessage)
// ==========================================================

require_once __DIR__ . '/core/config/config.php';
require_once __DIR__ . '/core/utils/cache.php';
require_once __DIR__ . '/core/services/telegram/api.php';
require_once __DIR__ . '/core/services/telegram/ui.php';
require_once __DIR__ . '/core/services/telegram/handlers/admin.php';
require_once __DIR__ . '/core/services/telegram/handlers/bot.php';
require_once __DIR__ . '/core/services/steam/market.php';
require_once __DIR__ . '/core/services/steam/regions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($uri === '/ping') {
        http_response_code(200);
        exit('pong');
    }

    if ($uri === '/debug') {

        $token = $_GET['token'] ?? '';

        if ($token !== getenv('DEBUG_TOKEN')) {
            http_response_code(403);
            exit('Forbidden');
        }

        header('Content-Type: text/plain');

        echo "=== cooldowns.json ===\n";
        echo file_exists(COOLDOWN_FILE)
            ? file_get_contents(COOLDOWN_FILE)
            : "(missing)";

        echo "\n\n=== price_cache.json ===\n";
        echo file_exists(PRICE_CACHE_FILE)
            ? file_get_contents(PRICE_CACHE_FILE)
            : "(missing)";

        exit;
    }
}

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$chat_id =
    $update['message']['chat']['id']
    ?? $update['callback_query']['message']['chat']['id']
    ?? null;

$user_id =
    $update['message']['from']['id']
    ?? $update['callback_query']['from']['id']
    ?? null;

$text =
    $update['message']['text']
    ?? null;

$data =
    $update['callback_query']['data']
    ?? null;

$callback_id =
    $update['callback_query']['id']
    ?? null;

if (!$chat_id) {
    exit;
}

if ($text && isAdmin($user_id)) {

    if (handleAdminCommand(
        $text,
        $chat_id,
        $user_id,
        $callback_id
    )) {
        exit;
    }
}

handleBot(
    $chat_id,
    $user_id,
    $text,
    $data,
    $callback_id
);
