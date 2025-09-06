<?php
function get_bing_daily_image() {
    $json = @file_get_contents('https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1');
    if ($json === FALSE) return '';
    $data = json_decode($json, true);
    if (isset($data['images'][0]['url'])) {
        return 'https://www.bing.com' . $data['images'][0]['url'];
    }
    return '';
}