<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

spl_autoload_register(function ($class_name) {
    include __DIR__ . '/src/' . $class_name . '.php';
});

$dbParams = include __DIR__ . '/config/db_params.php';

$db = new DB($dbParams);
$rampManager = new RampManager($db);
$reservationsManager = new ReservationsManager($db, $rampManager);

//var_dump($_POST);
/*if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['errors' => ['Service accepts only POST requests']]);
    exit;
}

$data = file_get_contents('php://input');*/
//echo json_encode($data);exit;
$d1 = new DateTime('2024-12-18 8:00');
$d2 = new DateTime('2024-12-18 6:00');
$d3 = new DateTime('2024-12-18 14:00');
$reservs = [
    [
        'reservation_from' => $d1->format('Y-m-d H:i'),
        'car_number' => 'ABC717',
        'duration' => 60
    ],
    [
        'reservation_from' => '2020-02-04 14:00',
        'car_number' => 'ZEZ505',
        'duration' => 30
    ],
    [
        'reservation_from' => $d3->modify('+15 minutes')->format('Y-m-d H:i'),
        'car_number' => 'PHP720',
        'duration' => 60
    ]
];
$res = ['reservation_from' => '2024-12-18 10:00',
    'car_number' => 'ABC717',
    'duration' => 90];
$data = json_encode($reservs);

$api = new Api($reservationsManager);
//$api->processRequest($_POST);
$api->processRequest($data);

