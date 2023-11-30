<?php

function getRandomQuote() {
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
            return getRandomQuote();
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
            'text' => $quoteText,
            'reference' => $reference,
            'author' => $authorName
        );

        return $result;
    }

    curl_close($ch);
}

function getCaption() {
    $quote = getRandomQuote();

    $result = $quote["text"];
    $result .= "<br><br>";
    if (!empty($quote["reference"])) {
        $result .= "📚 " . $quote["reference"];
        $result .= "<br><br>";
    }
    if (!empty($quote["author"])) {
        $result .= "👤 #" . $quote["author"];
        $result .= "<br><br>";
    }
    $result .= "🔔 @Free_Tg_Proxy";

    return $result;
}
?>