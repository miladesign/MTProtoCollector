<?php
include "config.php";

function getCurrentDay() {
    $dayNumber = date('N');

    switch ($dayNumber) {
        case 1:
            return "دوشنبتون";
        case 2:
            return "سه شنبتون";
        case 3:
            return "چهارشنبتون";
        case 4:
            return "پنجشنبتون";
        case 5:
            return "جمعتون";
        case 6:
            return "شنبتون";
        default:
            return "یکشنبتون";
    }
}

function generateGreeting() {
    global $morning, $morningText, $nightText;
    // Create greeting message
    date_default_timezone_set('Asia/Tehran');
    $currentHour = date('H');

    $greeting = "";
    if ($currentHour >= 6 && $currentHour < 11) {
        $randomMorningText = str_replace('{0}', getCurrentDay(), $morning[array_rand($morning)]);
        $greeting = str_replace('{0}', $randomMorningText, $morningText[array_rand($morningText)]);
    } elseif (($currentHour >= 21) || ($currentHour === 0)) {
        $greeting = $nightText[array_rand($nightText)];
    }

    return $greeting;
}

function generateCaption() {
    $url = "https://api.time.ir/v1/brainyquote/fa/quotes/randomquote";
    $headers = array(
        'X-Api-Key: ZAVdqwuySASubByCed5KYuYMzb9uB2f7'
    );

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_message = 'Error: ' . curl_error($ch);
        return json_encode(['error' => $error_message]);
    } else {
        // Decode JSON response
        $decoded_response = json_decode($response, true);

        // Extract quote text, reference, and author information
        $quoteText = $decoded_response['data']['text'];

        // Check if quote text is more than 250 characters
        if (strlen($quoteText) > 250) {
            // If yes, recursively get another quote
            return generateCaption();
        }

        $reference = $decoded_response['data']['reference'];
        $authorFirstName = $decoded_response['data']['author']['firstname'];
        $authorLastName = $decoded_response['data']['author']['lastname'];

        // Determine author name based on conditions
        if (empty($authorFirstName) && !empty($authorLastName)) {
            $authorName = $authorLastName;
        } elseif (!empty($authorFirstName) && empty($authorLastName)) {
            $authorName = $authorFirstName;
        } else {
            $authorName = $authorFirstName . '_' . $authorLastName;
        }

        // Replace spaces with underscores
        $authorName = str_replace(' ', '_', $authorName);

        // Create a new associative array with extracted information
        $result = array(
            'quote' => $quoteText,
            'reference' => $reference,
            'author' => $authorName,
            'greeting' => generateGreeting()
        );

        return $result;
    }

    curl_close($ch);
}

function getCaption() {
    $caption = generateCaption();

    $result = $caption["quote"];
    $result .= "\n\n";
    if (!empty($caption["reference"])) {
        $result .= "📚 " . $caption["reference"];
        $result .= "\n\n";
    }
    if (!empty($caption["author"])) {
        $result .= "👤 #" . $caption["author"];
        $result .= "\n\n";
    }
    if (!empty($caption["greeting"])) {
        $result .= $caption["greeting"];
        $result .= "\n\n";
    }
    $result .= "🔔 @ProxyCollector";

    return $result;
}
