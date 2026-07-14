<?php

function getCooldowns()
{
    if (!file_exists(COOLDOWN_FILE)) {
        return [];
    }

    $json = file_get_contents(COOLDOWN_FILE);
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function saveCooldowns($data)
{
    file_put_contents(
        COOLDOWN_FILE,
        json_encode($data),
        LOCK_EX
    );
}

function canRefresh($userId, $type)
{
    $cooldowns = getCooldowns();

    $key = $userId . '_' . $type;

    if (!isset($cooldowns[$key])) {
        return true;
    }

    return (time() - $cooldowns[$key]) >= REFRESH_COOLDOWN;
}

function markRefresh($userId, $type)
{
    $cooldowns = getCooldowns();

    $now = time();

    foreach ($cooldowns as $key => $timestamp) {
        if (($now - $timestamp) >= REFRESH_COOLDOWN) {
            unset($cooldowns[$key]);
        }
    }

    $cooldowns[$userId . '_' . $type] = $now;

    saveCooldowns($cooldowns);
}

function getPriceCache()
{
    if (!file_exists(PRICE_CACHE_FILE)) {
        return [];
    }

    $json = file_get_contents(PRICE_CACHE_FILE);
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function savePriceCache($data)
{
    file_put_contents(
        PRICE_CACHE_FILE,
        json_encode($data),
        LOCK_EX
    );
}
