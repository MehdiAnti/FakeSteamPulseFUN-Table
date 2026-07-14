<?php

date_default_timezone_set('Asia/Tehran');

$BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN');
$API_URL   = "https://api.telegram.org/bot{$BOT_TOKEN}/";

$ADMIN_ID   = getenv('ADMIN_ID');
$DEBUG_TOKEN = getenv('DEBUG_TOKEN');

define('BOT_NAME', 'Fake SteamPulse');
define('BOT_VERSION', '2.0');

define('REFRESH_COOLDOWN', 60);

define('COOLDOWN_FILE', '/tmp/cooldowns.json');

define('PRICE_CACHE_FILE', '/tmp/price_cache.json');
define('PRICE_CACHE_TTL', 300);
