<?php
include "modules/get_proxy.php";
include "modules/config.php";

$final_data = [];
foreach ($sources as $source) {
    $final_data = array_merge($final_data, proxy_array_maker($source));
    $final_output = remove_duplicate($final_data);
}

file_put_contents("proxy/mtproto.json", json_encode($final_output, JSON_PRETTY_PRINT));

$message = generateMessage($final_output);
sendMessageToTelegram($message);

function numberToEmoji($number)
{
    $map = [
        "0" => "0️⃣",
        "1" => "1️⃣",
        "2" => "2️⃣",
        "3" => "3️⃣",
        "4" => "4️⃣",
        "5" => "5️⃣",
        "6" => "6️⃣",
        "7" => "7️⃣",
        "8" => "8️⃣",
        "9" => "9️⃣",
    ];

    $emoji = "";
    $digits = str_split($number);

    foreach ($digits as $digit) {
        if (count($digits) === 1) {
            $emoji = $map["0"];
        }
        if (isset($map[$digit])) {
            $emoji .= $map[$digit];
        }
    }

    return $emoji;
}

function generateMessage($finalOutput)
{
    $keyboard = [];
    $numberMap = [];

    foreach ($finalOutput as $proxyData) {
        $flag = $proxyData['flag'];
        $link = $proxyData['link'];

        // Get the flag number and increment it
        $number = isset($numberMap[$flag]) ? ++$numberMap[$flag] : ($numberMap[$flag] = 1);

        // Add each button to the keyboard
        $keyboard[] = [
            'text' => "$flag " . numberToEmoji($number),
            'url' => $link,
        ];
    }

    $inlineKeyboard = json_encode(['inline_keyboard' => array_chunk($keyboard, 5)]);

    return $inlineKeyboard;
}

function sendMessageToTelegram($inlineKeyboard)
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
        'text' => 'Click on a flag:',
        'reply_markup' => $inlineKeyboard,
        "parse_mode" => "markdown"
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