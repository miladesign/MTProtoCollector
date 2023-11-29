<?php
include "modules/get_proxy.php";
include "modules/config.php";

$final_data = [];
foreach ($sources as $source) {
    $final_data = array_merge($final_data, proxy_array_maker($source));
    $final_output = remove_duplicate($final_data);
}

file_put_contents("proxy/mtproto.json", json_encode($final_output, JSON_PRETTY_PRINT));

function getQuote() {
    // Get HTML content from the URL
    $url = "https://time.ir";
    $html = file_get_contents($url);

    // Define the regular expression patterns for quoteText and quoteAuthor
    $quoteTextPattern = '/<span[^>]*class="h4 quoteText"[^>]*>(.*?)<\/span>/s';
    $quoteAuthorPattern = '/<a[^>]*class="h5 quoteAuthor"[^>]*>(.*?)<\/a>/s';

    // Perform the regular expression matches
    preg_match($quoteTextPattern, $html, $quoteTextMatches);
    preg_match($quoteAuthorPattern, $html, $quoteAuthorMatches);

    // Extract the quoteText and quoteAuthor from the matches
    $quoteText = isset($quoteTextMatches[1]) ? $quoteTextMatches[1] : '';
    $quoteAuthor = isset($quoteAuthorMatches[1]) ? $quoteAuthorMatches[1] : '';

    // Check if the text length is more than 200 characters or empty and run the function again
    if (mb_strlen($quoteText) > 200 || empty($quoteText)) {
        return getQuote();
    }

    // Trim the author's name and replace spaces with underscores
    $underscoredAuthor = str_replace(' ', '_', trim($quoteAuthor));

    // Return the results in an array
    return array("text" => $quoteText, "author" => '#' . $underscoredAuthor);
}

$message = generateMessage($final_output);
sendMessageToTelegram($message);

function generateMessage($finalOutput)
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
            'text' => "$flag ($number)",
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

    $quote = getQuote();

    $text = $quote['text'] . "\n" . $quote['author'] . "\n";
    $text .= "@Free_Tg_Proxy \n";
    $text .= "برای اتصال کشور مورد نظر را انتخاب کنید:";

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $text,
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