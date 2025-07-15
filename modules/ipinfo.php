<?php

function is_ip($string)
{
    $ip_pattern = '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/';
    if (preg_match($ip_pattern, $string)) {
        return true;
    } else {
        return false;
    }
}

function ip_info($ip) {
    if (is_ip($ip) === false) {
        $ip_address_array = dns_get_record($ip, DNS_A);
        $randomKey = array_rand($ip_address_array);
        $ip = $ip_address_array[$randomKey]["ip"];
    }

    $endpoints = [
        'https://ipapi.co/{ip}/json/',
        'https://ipwhois.app/json/{ip}',
        'http://www.geoplugin.net/json.gp?ip={ip}',
        'https://api.ipbase.com/v1/json/{ip}'
    ];

    $result = (object) [
        'country' => "XX"
    ];

    foreach ($endpoints as $index => $endpoint) {
        $url = str_replace('{ip}', $ip, $endpoint);

        $options = [
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us)
AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.10\r\n"
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response !== false) {
            $data = json_decode($response);

            $countryCode = null;

            switch ($index) {
                case 0: // ipapi.co
                    $countryCode = $data->country_code ?? null;
                    break;
                case 1: // ipwhois.app
                    $countryCode = $data->country_code ?? null;
                    break;
                case 2: // geoplugin.net
                    $countryCode = $data->geoplugin_countryCode ?? null;
                    break;
                case 3: // ipbase.com
                    $countryCode = $data->country_code ?? null;
                    break;
            }

            if (!empty($countryCode)) {
                $result->country = $countryCode;
                break; // Found valid, stop here
            }
        }
    }

    // If all endpoints failed or returned empty, result stays XX
    if (empty($result->country)) {
        $result->country = "XX";
    }

    return $result;
}

?>
