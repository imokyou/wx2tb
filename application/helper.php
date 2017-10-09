<?php

function curl_post($url, $data='', $timeout=60, $agent='', $cookie='')
{
    $fn = curl_init();
    curl_setopt($fn, CURLOPT_URL, $url);
    curl_setopt($fn, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($fn, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($fn, CURLOPT_REFERER, $url);
    curl_setopt($fn, CURLOPT_HEADER, 0);
    curl_setopt($fn, CURLOPT_POST, TRUE);
    curl_setopt($fn, CURLOPT_POSTFIELDS, $data);
    if ($agent) {
        curl_setopt($fn, CURLOPT_USERAGENT, $agent);    
    }
    if ($cookie) {
       curl_setopt($fn,CURLOPT_COOKIE,$cookie);
    }
    $fm = curl_exec($fn);
    curl_close($fn);
    return $fm;
}

function curl_get($url, $timeout=60, $agent='', $cookie='')
{
    $fn = curl_init();
    curl_setopt($fn, CURLOPT_URL, $url);
    curl_setopt($fn, CURLOPT_TIMEOUT, 60);
    curl_setopt($fn, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($fn, CURLOPT_REFERER, $url);
    curl_setopt($fn, CURLOPT_HEADER, 0);
    if ($agent) {
        curl_setopt($fn, CURLOPT_USERAGENT, $agent);    
    }
    if ($cookie) {
       curl_setopt($fn,CURLOPT_COOKIE,$cookie);
    }
    $fm = curl_exec($fn);
    curl_close($fn);
    return $fm;
}