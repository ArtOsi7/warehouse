<?php

if ($_GET['debug']) {
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(-1);
}

spl_autoload_register(function ($class_name) {
    include __DIR__ . '/src/' . $class_name . '.php';
});

$dbParams = include __DIR__ . '/config/db_params.php';

$db = new DB($dbParams);
$rampManager = new RampManager($db);
$reservationsManager = new ReservationsManager($db, $rampManager);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['errors' => ['Service accepts only POST requests']]);
    exit;
}

$data = file_get_contents('php://input');

$api = new Api($reservationsManager);
//$api->processRequest($_POST);
$api->processRequest($data);

