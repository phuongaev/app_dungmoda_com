<?php

return [
    // https://api.telegram.org/bot<token>/setWebhook?url=https://...
    'token' => env("TELE_TOKEN"), // Your bot's token that got from @BotFather
    'chat_id' => env("CHAT_ID") // The user's(that you want to send a message) telegram chat id
];
