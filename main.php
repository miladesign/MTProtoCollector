<?php
include "modules/get_proxy.php";
include "modules/config.php";
include "modules/caption.php";

date_default_timezone_set('Asia/Tehran');
$currentHour = date('H');

if (($currentHour > 0) && ($currentHour < 7)) {
    
} else {
    $final_data = [];
    foreach ($sources as $source) {
        $final_data = array_merge($final_data, proxy_array_maker($source));
        $final_output = remove_duplicate($final_data);
    }

    //file_put_contents("proxy/mtproto.json", json_encode($final_output, JSON_PRETTY_PRINT));

    $keyboard = generateKeyboard($final_output);
    $message = "🔔 @ProxyCollector";
    if (($currentHour > 10) && ($currentHour < 14)) {
        $message = getPrices();
    } else {
        $message = getCaption();
    }
    sendMessageToTelegram($message, $keyboard);

    $dir = "api";
    if (!is_dir($dir)) {
        mkdir($dir);
    }
    
    file_put_contents("api/normal", $final_output);
}

function generateKeyboard($finalOutput)
{
    $keyboard = [];
    $numberMap = [];

    // Sort the finalOutput array by flag
    usort($finalOutput, function ($a, $b) {
        return strcmp($a['flag'], $b['flag']);
    });

    foreach ($finalOutput as $proxyData) {
        $flag = $proxyData['flag'];
        $link = $proxyData['link'];

        // Get the flag number and increment it
        $number = isset($numberMap[$flag]) ? ++$numberMap[$flag] : ($numberMap[$flag] = 1);

        // Add each button to the keyboard
        $keyboard[] = [
            'text' => "$flag $number",
            'url' => $link,
        ];
    }

    $inlineKeyboard = json_encode(['inline_keyboard' => array_chunk($keyboard, 5)]);

    return $inlineKeyboard;
}

function sendMessageToTelegram($message, $inlineKeyboard)
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
        'reply_markup' => $inlineKeyboard,
        "parse_mode" => "MarkdownV2"
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
