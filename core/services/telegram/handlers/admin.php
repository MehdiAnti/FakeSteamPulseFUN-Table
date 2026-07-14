<?php

function isAdmin($user_id)
{
    return (string)$user_id === (string)getenv('ADMIN_ID');
}

function sendAdminMessage($chat_id, $text)
{
    sendMessage($chat_id, "<b>🛠 Admin</b>\n\n" . $text);
}

function getCacheStats()
{
    $cache = getPriceCache();

    $entries = count($cache);
    $oldest = 0;

    foreach ($cache as $item) {
        if (isset($item['time'])) {
            $oldest = max($oldest, time() - $item['time']);
        }
    }

    $size = file_exists(PRICE_CACHE_FILE)
        ? filesize(PRICE_CACHE_FILE)
        : 0;

    return [
        'entries' => $entries,
        'oldest'  => $oldest,
        'size'    => $size
    ];
}

function clearPriceCache()
{
    file_put_contents(
        PRICE_CACHE_FILE,
        '{}',
        LOCK_EX
    );
}

function getDebugDump()
{
    return
        "=== cooldowns.json ===\n" .
        (file_exists(COOLDOWN_FILE)
            ? file_get_contents(COOLDOWN_FILE)
            : "(missing)") .

        "\n\n=== price_cache.json ===\n" .

        (file_exists(PRICE_CACHE_FILE)
            ? file_get_contents(PRICE_CACHE_FILE)
            : "(missing)");
}

function getStats()
{
    $cache = getCacheStats();

    return
        "Version: " . BOT_VERSION . "\n" .
        "Cache entries: {$cache['entries']}\n" .
        "Cache size: {$cache['size']} bytes\n" .
        "Oldest cache: {$cache['oldest']} sec\n";
}

function handleAdminCommand($text, $chat_id, $user_id, $callback_id = null)
{
    if (!isAdmin($user_id)) {
        return false;
    }

    global $regions;

    $parts = preg_split('/\s+/', trim($text));
    $command = strtolower($parts[0]);

    switch ($command) {

        case '/admin':
            sendAdminMessage(
                $chat_id,
                "Available commands\n\n" .
                "/cache — Cache statistics\n" .
                "/cacheclear — Clear cache\n" .
                "/stats — Bot statistics\n" .
                "/health — Bot health check\n" .
                "/debug — Dump cache files\n" .
                "/testprice — Default test\n" .
                "/testprice key usd\n" .
                "/testprice ticket eur\n" .
                "/testrich — Show generated HTML\n" .
                "/testrich key\n" .
                "/testrich ticket"
            );

            return true;
            
        case '/cache':

            $cache = getCacheStats();

            sendAdminMessage(
                $chat_id,
                "Entries: {$cache['entries']}\n" .
                "Oldest: {$cache['oldest']} sec\n" .
                "Size: {$cache['size']} bytes"
            );

            return true;

        case '/cacheclear':

            clearPriceCache();

            sendAdminMessage(
                $chat_id,
                "✅ Price cache cleared."
            );

            return true;

        case '/stats':

            sendAdminMessage(
                $chat_id,
                getStats()
            );

            return true;

        case '/health':
        
            $telegram = telegramHealth();
            $steam = steamMarketHealth();
            
        
            $cacheOk = 
                file_exists(PRICE_CACHE_FILE) &&
                is_readable(PRICE_CACHE_FILE);
        
            $cooldownOk =
                file_exists(COOLDOWN_FILE) &&
                is_readable(COOLDOWN_FILE);
        
            $fails = 0;
        
            if (!$telegram['success']) $fails++;
            if (!$steam['success']) $fails++;
            if (!$cacheOk) $fails++;
            if (!$cooldownOk) $fails++;
        
            if ($fails === 0) {
                $icon = "🟢";
                $overall = "Healthy";
            } elseif ($fails === 1) {
                $icon = "🟡";
                $overall = "Degraded";
            } else {
                $icon = "🔴";
                $overall = "Critical";
            }
        
            $msg =
                "{$icon} <b>Bot Health</b>\n\n" .
                
                "Telegram API: " .
                ($telegram['success']
                 ? "✅"
                 : "❌") .
                
                "\nSteam Market: " .
                ($steam['success']
                 ? "✅"
                 : "❌ Timeout") .
                
                "\nPrice cache: " .
                ($cacheOk
                 ? "✅"
                 : "❌ Missing") .
                
                "\nCooldown file: " .
                ($cooldownOk
                 ? "✅"
                 : "❌ Missing") .
                "\n\n<b>Overall:</b> {$overall}";
        
            sendMessage($chat_id, $msg);
            return true;

        case '/debug':

            sendMessage(
                $chat_id,
                "<pre>" . htmlspecialchars(getDebugDump()) . "</pre>"
            );

            return true;

        case '/testrich':
            $type = strtolower($parts[1] ?? 'key');
            
            $html = buildPriceTable($type, $regions);
        
            sendMessage(
                $chat_id,
                "<pre>" . htmlspecialchars($html) . "</pre>"
            );
            
            return true;

        case '/testprice':

            $item = strtolower($parts[1] ?? 'key');
            $regionKey = strtolower($parts[2] ?? 'usd');

            $marketName = match ($item) {
                'ticket' => 'Tour of Duty Ticket',
                default  => 'Mann Co. Supply Crate Key'
            };

            $region = null;

            foreach ($regions as $r) {
                if ($r['cachekey'] === $regionKey) {
                    $region = $r;
                    break;
                }
            }

            if (!$region) {

                sendAdminMessage(
                    $chat_id,
                    "❌ Unknown region '{$regionKey}'."
                );

                return true;
            }

            $test = steamTestPrice(
                $marketName,
                $region['currency']
            );

            $msg =
                "🧪 <b>Steam Price Test</b>\n\n" .
                "━━━━━━━━━━━━━━━━━━\n\n" .
                "<b>Item</b>\n{$marketName}\n\n" .
                "<b>Region</b>\n{$region['name']}\n\n" .
                "<b>Currency</b>\n" .
                strtoupper($region['cachekey']) .
                " (ID: {$region['currency']})\n\n" .
                "━━━━━━━━━━━━━━━━━━\n\n" .
                "<b>Status</b>\n" .
                ($test['success']
                    ? "✅ Success"
                    : "❌ {$test['status']}") .
                "\n\n" .
                "<b>Response</b>\n{$test['time']} ms";

            if ($test['success']) {

                $msg .=
                    "\n\n<b>Parsed Price</b>\n" .
                    (
                        $test['price'] !== null
                            ? "{$test['price']} {$region['symbol']}"
                            : "N/A"
                    );

                $msg .=
                    "\n\n━━━━━━━━━━━━━━━━━━\n\n" .
                    "<b>Raw Steam JSON</b>\n<pre>" .
                    htmlspecialchars(
                        json_encode(
                            $test['json'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                        )
                    ) .
                    "</pre>";

            } else {

                $msg .=
                    "\n\n━━━━━━━━━━━━━━━━━━\n\n" .
                    "<b>Raw Response</b>\n<pre>" .
                    htmlspecialchars(
                        is_string($test['raw'])
                            ? $test['raw']
                            : 'false'
                    ) .
                    "</pre>";
            }

            sendMessage($chat_id, $msg);

            return true;
    }

    sendAdminMessage(
        $chat_id,
        "❌ Unknown admin command.\n" .
        "Use /admin to see all available commands."
    );
    
    return true;
}
