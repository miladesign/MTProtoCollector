<?php
include "ping.php";
include "flag.php";
include "ipinfo.php";

function getProxies($channel)
{
    $get = file_get_contents("https://t.me/s/" . $channel);
    preg_match_all(
        '#href="(.*?)/proxy?(.*?)" target="_blank" rel="noopener"#',
        $get,
        $prxs
    );
    preg_match_all(
        '#class="tgme_widget_message_inline_button url_button" href="(.*?)/proxy?(.*?)"#',
        $get,
        $in_prxs
    );

    return $prxs[2] ?: $in_prxs[2];
}

function parse_proxy($proxy, $name)
{
    $proxy_array = [];
    $url = html_entity_decode($proxy);
    $parts = parse_url($proxy);
    $query_string = str_replace("amp;", "", $parts["query"]);
    parse_str($query_string, $query_params);
    $server = $query_params["server"];
    if ($server != "") {
        //if (!filtered_or_not("https://" . $server)) {
            foreach ($query_params as $key => $value) {
                if (stripos($key, "@") !== false) {
                    unset($query_params[$key]);
                    break; 
                }
            }
            $ip_data = ip_info($server);
            if ($ip_data->country != "XX") {
                $location = $ip_data->country;
                $flag = getFlags($location);
            } else {
                $flag = "🚩";
            }
            $query_params["name"] = "@" . $name . "|" . $flag;
            $proxy_array = $parts;
            unset($proxy_array["query"]);
            $proxy_array["query"] = $query_params;
        //}
    }
    return $proxy_array;
}

function proxy_array_maker($source)
{
    $exception = ["alephproxy"];
    $key_limit = in_array($source, $exception) ? count(getProxies($source)) - 9 : count(getProxies($source)) - 2;
    $output = [];
    foreach (getProxies($source) as $key => $proxy) {
        if ($key >= $key_limit) {
            $proxy = "https://t.me/proxy" . $proxy;
            $data = parse_proxy($proxy, $source);
            if ($data === []) {
                null;
            } else {
                $output[$key - $key_limit] = $data;
            }
        }
    }
    return $output;
}

function remove_duplicate($input)
{
    $new_proxy_array = [];
    foreach ($input as $proxy_data) {
        $name = $proxy_data["query"]["name"];
        unset($proxy_data["query"]["name"]);
        $key = serialize($proxy_data["query"]);
        $new_proxy_array[$key][] = $name;
    }
    $output = [];
    $query = [];
    $counter = 0;
    foreach ($new_proxy_array as $query_params => $name_array) {
        $query = unserialize($query_params);
        $flag = "🚩";
        $name_parts = explode("|", $name_array[0]);
        if (count($name_parts) === 2) {
            $flag = $name_parts[1];
        }
        $query["name"] = $name_parts[0];
        $output[$counter]["scheme"] = "https";
        $output[$counter]["host"] = "t.me";
        $output[$counter]["path"] = "/proxy";
        $output[$counter]["query"] = $query;
        $output[$counter]["flag"] = $flag;
        $output[$counter]["link"] =
            $output[$counter]["scheme"] .
            "://" .
            $output[$counter]["host"] .
            $output[$counter]["path"] .
            "?server=" .
            $output[$counter]["query"]["server"] .
            "&port=" .
            $output[$counter]["query"]["port"] .
            "&secret=" .
            $output[$counter]["query"]["secret"] .
            "&" .
            $output[$counter]["query"]["name"];
        $counter++;
    }
    return $output;
}

function proxy_array_from_file()
{
    $urls = [
        "https://raw.githubusercontent.com/V2RAYCONFIGSPOOL/TELEGRAM_PROXY_SUB/refs/heads/main/telegram_proxy.txt",
        "https://raw.githubusercontent.com/ALIILAPRO/MTProtoProxy/main/mtproto.txt",
        "https://raw.githubusercontent.com/MmdBay/proxy_collector/refs/heads/main/proxy-list.txt"
    ];

    $output = [];

    foreach ($urls as $url) {
        $proxies_text = @file_get_contents($url);

        if ($proxies_text === false) {
            continue;
        }

        $lines = explode("\n", trim($proxies_text));

        foreach ($lines as $proxy_url) {
            $proxy_url = trim($proxy_url);

            if ($proxy_url === '' || stripos($proxy_url, 'http') !== 0) {
                continue;
            }

            $data = parse_proxy($proxy_url, "external");

            if (!empty($data)) {
                $output[] = $data;
            }
        }

        // If we found proxies from this URL, return them
        if (!empty($output)) {
            return $output;
        }
    }

    return $output;
}

function build_mtproto_url($proxy_array)
{
    if (empty($proxy_array)) return '';

    $scheme = $proxy_array['scheme'] ?? 'https';
    $host   = $proxy_array['host'] ?? '';
    $path   = $proxy_array['path'] ?? '';
    $query  = isset($proxy_array['query']) ? http_build_query($proxy_array['query']) : '';

    return "{$scheme}://{$host}{$path}?" . $query;
}
?>
