<?php

spl_autoload_register(function ($class_name) {
    include __DIR__ . '/src/' . $class_name . '.php';
});

$dbParams = include __DIR__ . '/config/db_params.php';

$db = new DB($dbParams);
$rampManager = new RampManager($db);
$reservationsManager = new ReservationsManager($db, $rampManager);

//var_dump($_POST);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['errors' => ['Service accepts only POST requests']]);
    exit;
}

$data = file_get_contents('php://input');
//echo json_encode($data);exit;


$api = new Api($reservationsManager);
//$api->processRequest($_POST);
$api->processRequest($data);

