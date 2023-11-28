<?php
include "modules/get_proxy.php";
include "modules/config.php";

$final_data = [];
foreach ($sources as $source) {
    $final_data = array_merge($final_data, proxy_array_maker($source));
    $final_output = remove_duplicate($final_data);
}

file_put_contents("proxy/mtproto.json", json_encode($final_output, JSON_PRETTY_PRINT));

sendMessageToTelegram("TEST MESSAGE");

function sendMessageToTelegram($message)
{
    $botToken = getenv('TELEGRAM_BOT_TOKEN');
    $chatId = getenv('TELEGRAM_CHAT_ID');

    if (!$botToken || !$chatId) {
        echo "Error: Bot token or chat ID not set.";
        return;
    }

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch);
    } else {
        // Check the response from the Telegram API
        $responseData = json_decode($response, true);
        if (!$responseData || !$responseData['ok']) {
            echo "Telegram API error: " . json_encode($responseData);
        }
    }

    curl_close($ch);
}
?>