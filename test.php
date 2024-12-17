<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

$d1 = new DateTime();
$d2 = new DateTime();
$d3 = new DateTime();

$reservations = [
    [
        'reservation_from' => $d1->modify('+45 minutes')->format('Y-m-d H:i'),
        'car_number' => 'ABC717',
        'duration' => 60
    ],
    [
        'reservation_from' => $d2->modify('+5 minutes')->format('Y-m-d H:i'),
        'car_number' => 'ABC717',
        'duration' => 30
    ],
    [
        'reservation_from' => $d3->modify('+15 minutes')->format('Y-m-d H:i'),
        'car_number' => 'ABC717',
        'duration' => 60
    ]
];

//print_r($reservations);

$url =  $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) .'/';
//$data = ['reservation_date'=> "2024-12-14 12:00", 'reservation_from' => 'bullshit', 'reservation_time' => 60, 'car_number' => 'ABC123'];
$content = json_encode($reservations);
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