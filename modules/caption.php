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
    date_default_timezone_set('Asia/Tehran');
    $currentHour = date('H');

    $greeting = "";
    if ($currentHour >= 6 && $currentHour < 10) {
        $formatter = new IntlDateFormatter(
            "fa_IR@calendar=persian",
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
            'EEEE d MMMM y');
        $now = new DateTime();
        $randomMorningText = str_replace('{0}', getCurrentDay(), $morning[array_rand($morning)]);
        $greeting = str_replace('{0}', $randomMorningText, $morningText[array_rand($morningText)]);
        $greeting .= "\n\n📅 " . $formatter->format($now);
    } elseif (($currentHour >= 22) || ($currentHour === 0)) {
        $greeting = $nightText[array_rand($nightText)];
    }
    $greeting = str_replace('.', '\\.', $greeting);
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
        $decoded_response = json_decode($response, true);

        $quoteText = $decoded_response['data']['text'];
        $replacements = [
            '&nbsp;' => '',
            '</div><div>' => '\n',
            '</div><span[^>]*>' => '\n',
            '</div>' => '',
            '<div>' => '',
            '<span>' => '',
            '</span>' => '',
            '.' => '\\.',
            '<b>' => '*',
            '</b>' => '*',
            '<i>' => '_',
            '</i>' => '_',
            '<br>' => '\n',
            '</br>' => '\n',
            '<br\>' => '\n',
            ' target="_blank"' => '',
            ' target=\"_blank\"' => '',
        ];
        
        $quoteText = str_replace(array_keys($replacements), array_values($replacements), $quoteText);
        $quoteText = strip_tags($quoteText);
        if (strlen($quoteText) > 350) {
            return generateCaption();
        }

        $reference = $decoded_response['data']['reference'];
        $authorFirstName = $decoded_response['data']['author']['firstname'];
        $authorLastName = $decoded_response['data']['author']['lastname'];

        if (empty($authorFirstName) && !empty($authorLastName)) {
            $authorName = $authorLastName;
        } elseif (!empty($authorFirstName) && empty($authorLastName)) {
            $authorName = $authorFirstName;
        } else {
            $authorName = $authorFirstName . '\\_' . $authorLastName;
        }

        $authorName = str_replace(' ', '\\_', $authorName);

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
    /*if (!empty($caption["reference"])) {
        $result .= "📚 " . $caption["reference"];
        $result .= "\n\n";
    }*/
    if (!empty($caption["author"])) {
        $result .= "👤 \\#" . $caption["author"];
        $result .= "\n\n";
    }
    if (!empty($caption["greeting"])) {
        $result .= $caption["greeting"];
        $result .= "\n\n";
    }
    $result .= "🔔 @ProxyCollector";

    return $result;
}

function getPrices() {
    $url = 'https://irarz.com/Aj.php';
    $data = array('signal' => 'getdata');
    
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_message = 'Error: ' . curl_error($ch);
        return "";
    } else {
        $dataArray = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = 'Error: ' . curl_error($ch);
            return "";
        } else {
            $message = "💰 \\#نرخ\\_ارز\n\n";
    
            $currencyKeys = [
                'dollar_tavafogh' => '🏦دلار تا سقف دو هزار یورو با کارت ملی',
                'usdmax' => '🇺🇸دلار آزاد',
                'price_eur' => '🇪🇺یورو',
                'price_gbp' => '🇬🇧پوند انگلیس',
                'price_aed' => '🇦🇪درهم امارات',
                'price_try' => '🇹🇷لیر ترکیه',
                'price_gel' => '🇬🇪لاری گرجستان',
                'price_iqd' => '🇮🇶دینار عراق',
                'price_kwd' => '🇰🇼دینار کویت',
                'price_cad' => '🇨🇦دلار کانادا',
                'price_aud' => '🇦🇺دلار استرالیا',
                'price_sgd' => '🇸🇬دلار سنگاپور',
                'afghan_usd' => '🇦🇫دلار افغانستان',
                'price_rub' => '🇷🇺روبل روسیه',
                'price_cny' => '🇨🇳یوان چین',
                'price_sar' => '🇸🇦ریال عربستان',
                'price_omr' => '🇴🇲ریال عمان',
            ];
    
            foreach ($currencyKeys as $key => $persianName) {
                foreach ($dataArray as $item) {
                    if (isset($item[$key])) {
                        $price = str_replace('.', '\\.', $item[$key]);
                        $message .= "$persianName: *_" . $item[$key] . "_* ریال\n";
                        break;
                    }
                }
            }
            $message .= "\n🔔 @ProxyCollector";

            return $message;
        }
    }
    
    curl_close($ch);
}
