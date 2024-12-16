<?php

$url = "http://jimmybox.local/html/warehouse1/";
$data = ['reservation_date'=> "2024-12-14 12:00", 'reservation_from' => 'bullshit', 'reservation_time' => 60, 'car_number' => 'ABC123'];
$content = json_encode($data);
//var_dump($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT']);
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_PROXY, $_SERVER['SERVER_ADDR'] . ':' .  $_SERVER['SERVER_PORT']);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER,
    array("Content-type: application/json"));
curl_setopt($curl, CURLOPT_POST, true);
//curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

$json_response = curl_exec($curl);

$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

/*if ( $status != 201 ) {
    die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
}*/


curl_close($curl);

$response = json_decode($json_response, true);
print_r($response);