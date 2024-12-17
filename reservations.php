<html>
<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: gainsboro;
        }
        .reserv-table {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.5)
        }
        .reserv-table th {
            background-color: #6c0606;
            color:white;
        }

    </style>
</head>
<body>
<?php

if ($_GET['debug']) {
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(-1);
}

include __DIR__ . '/src/ReservationsManager.php';
include __DIR__ . '/src/RampManager.php';
include __DIR__ . '/src/DB.php';
$dbParams = include __DIR__ . '/config/db_params.php';

$db = new DB($dbParams);
$rampManager = new RampManager($db);
$reservationsManager = new ReservationsManager($db, $rampManager);

$ramps = $rampManager->getAllRamps();

$rampsData = [];
foreach ($ramps as $ramp) {
    $reservations = $rampManager->getRampReservations($ramp['id']);
    $reservs = [];
    foreach ($reservations as $res) {
        $dateFrom = new DateTime($res['reservation_from']);
        $res['reservation_from'] = $dateFrom->format('Y-m-d H:i');
        $dateTo = new DateTime($res['reservation_till']);
        $res['reservation_till'] = $dateTo->format('Y-m-d H:i');
        $reservs[] = $res;
    }
    $ramp['reservations'] = $reservs;
    $rampsData[] = $ramp;
}
?>
<div class="container pt-2">
    <div class="row">
        <div class="col-md-12">
            <?php foreach ($rampsData as $ramp): ?>
            <table class="table table-dark table-hover reserv-table">
                <thead>
                    <tr><th colspan="3" class="text-center"><?= $ramp['name'] ?> (<?= $ramp['code'] ?>) </th></tr>
                    <tr><th>From</th><th>To</th><th>Car number</th></tr>
                </thead>
                <tbody>
                <?php foreach ($ramp['reservations'] as $reservation): ?>
                <tr>
                    <td><?= $reservation['reservation_from'] ?></td>
                    <td><?= $reservation['reservation_till'] ?></td>
                    <td><?= $reservation['car_number'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>

</html>