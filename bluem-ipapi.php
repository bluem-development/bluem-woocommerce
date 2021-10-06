<?php


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Request IP information given a certain IP. If no IP is given, it is inferred from the server
 *
 * @param string $ip
 * @return void
 */
function bluem_ipapi_call($ip = "")
{
    $debug = false;

    
    // set IP address and API access key
    $access_key = "ec7b6c41a0f51d87cfc8c53fcf64fe83";

    if ($ip=="") {
        $ip = bluem_ipapi_getip();
    }

    $base_url = "http://api.ipstack.com/";

    $call_url = "{$base_url}{$ip}";

    // Initialize CURL:
    $ch = curl_init('http://api.ipstack.com/'.$ip.'?access_key='.$access_key.'');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Store the data:
    $json = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response:
    $api_result = json_decode($json, true);

    if ($debug == true) {
        var_dump($api_result);
    }


    // Output the "capital" object inside "location"
    return $api_result;
    // $api_result['location']['capital'];
}

/**
 * Verify if a given or server IP is country coded NL
 * reference: https://www.javatpoint.com/how-to-get-the-ip-address-in-php
 * 
 * @param string $ip
 * @return void
 */
function bluem_ipapi_call_nlcheck($ip ="")
{
    $result = bluem_ipapi_call($ip);

    if (isset($result['success'])
        && $result['success'] === false
    ) {
        // if we can't check for IP, return true for now
        return true;
    }
    if (is_null($result['country_code'])) {
        // if we can't check for IP, return true for now
        return true;
    }
    return ($result['country_code'] === "NL");
}

/**
 * Retrieve the current IP from the server, if possible
 *
 * @return string
 */
function bluem_ipapi_getip()
{
    $ip = "";
    //whether ip is from the remote address
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //whether ip is from the share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //whether ip is from the proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
}
