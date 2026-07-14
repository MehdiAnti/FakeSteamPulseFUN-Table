<?php

function telegramRequest($method, $data = [])
{
    global $API_URL;

    $ch = curl_init($API_URL . $method);

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT        => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function sendMessage($chat_id, $text, $reply_markup = null)
{
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
        'link_preview_options' => [
            'is_disabled' => true
        ]
    ];

    if ($reply_markup) {
        $payload['reply_markup'] = $reply_markup;
    }

    return telegramRequest('sendMessage', $payload);
}

function sendRichMessage($chat_id, $html, $reply_markup = null)
{
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

function answerCallbackQuery($callback_id, $text = null, $alert = false)
{
    $payload = [
        'callback_query_id' => $callback_id
    ];

    if ($text !== null) {
        $payload['text'] = $text;
        $payload['show_alert'] = $alert;
    }

    return telegramRequest('answerCallbackQuery', $payload);
}

function telegramHealth()
{
    global $API_URL;

    $start = microtime(true);

    $ch = curl_init($API_URL . "getMe");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $raw = curl_exec($ch);

    $time = round((microtime(true) - $start) * 1000);

    curl_close($ch);

    if ($raw === false) {
        return [
            'success' => false,
            'time' => $time
        ];
    }

    $json = json_decode($raw, true);

    return [
        'success' => isset($json['ok']) && $json['ok'],
        'time' => $time
    ];
}
