<?php

function sendWelcomeMenu($chat_id, $mainMenu)
{
    sendMessage(
        $chat_id,
        buildWelcomeText(),
        $mainMenu
    );
}

function sendAbout($chat_id)
{
    sendMessage(
        $chat_id,
        buildAboutText(),
        [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Back to Menu',
                        'callback_data' => 'back_menu',
                        'style' => 'primary'
                    ]
                ]
            ]
        ]
    );
}

function sendPriceTable($chat_id, $user_id, $type, $regions)
{
    markRefresh($user_id, $type);

    $buttons = [
        'inline_keyboard' => [
            [
                [
                    'text' => '🔄 Refresh',
                    'callback_data' => 'refresh_' . $type,
                    'style' => 'success'
                ]
            ],
            [
                [
                    'text' => '🏠 Back to Menu',
                    'callback_data' => 'back_menu',
                    'style' => 'primary'
                ]
            ]
        ]
    ];

    sendRichMessage(
        $chat_id,
        buildPriceTable($type, $regions),
        $buttons
    );
}

function handleBot(
    $chat_id,
    $user_id,
    $text,
    $data,
    $callback_id
)
{
    global $mainMenu, $regions;

    if ($text === '/start') {

        sendWelcomeMenu($chat_id, $mainMenu);
        return;
    }

    if ($text === '/help') {

        sendWelcomeMenu($chat_id, $mainMenu);
        return;
    }

    if ($text === '/about') {

        sendAbout($chat_id);
        return;
    }

    if ($text) {

        sendMessage(
            $chat_id,
            "❓ Unknown command.\n\nUse /start to open the main menu."
        );
        return;
    }

    if ($data === 'show_keys') {

        sendPriceTable(
            $chat_id,
            $user_id,
            'key',
            $regions
        );
        return;
    }

    if ($data === 'show_tickets') {

        sendPriceTable(
            $chat_id,
            $user_id,
            'ticket',
            $regions
        );
        return;
    }

    if ($data === 'refresh_key') {

        if (!canRefresh($user_id, 'key')) {

            $cooldowns = getCooldowns();

            $remaining =
                REFRESH_COOLDOWN -
                (time() - ($cooldowns[$user_id . '_key'] ?? 0));

            answerCallbackQuery(
                $callback_id,
                "⏳ Please wait {$remaining} seconds before refreshing again.",
                true
            );

            return;
        }

        answerCallbackQuery($callback_id);

        sendPriceTable(
            $chat_id,
            $user_id,
            'key',
            $regions
        );

        return;
    }

    if ($data === 'refresh_ticket') {

        if (!canRefresh($user_id, 'ticket')) {

            $cooldowns = getCooldowns();

            $remaining =
                REFRESH_COOLDOWN -
                (time() - ($cooldowns[$user_id . '_ticket'] ?? 0));

            answerCallbackQuery(
                $callback_id,
                "⏳ Please wait {$remaining} seconds before refreshing again.",
                true
            );

            return;
        }

        answerCallbackQuery($callback_id);

        sendPriceTable(
            $chat_id,
            $user_id,
            'ticket',
            $regions
        );

        return;
    }

    if ($data === 'back_menu') {

        sendWelcomeMenu(
            $chat_id,
            $mainMenu
        );

        return;
    }

    if ($data === 'about') {

        sendAbout($chat_id);

        return;
    }
}
