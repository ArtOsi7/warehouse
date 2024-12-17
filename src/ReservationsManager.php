<?php


class ReservationsManager
{
    const REQUIRED_PARAMS = [self::RESERVATION_FROM, self::RESERVATION_DURATION, self::CAR_NUMBER];

    const RESERVATION_FROM = 'reservation_from';
    const RESERVATION_DURATION = 'duration';
    const CAR_NUMBER = 'car_number';

    /**
     * @var DB $db
     */
    private $db;

    /**
     * @var RampManager $rampManager
     */
    private $rampManager;

    /**
     * @var array
     */
    private $errors = [];

    private $validReservations = [];

    private $invalidReservations = [];

    private $successfulReservations = [];

    private $failedReservations = [];

    /**
     * ReservationsManager constructor.
     * @param $db
     * @param $rampManager
     */
    public function __construct($db, $rampManager)
    {
        $this->db = $db;
        $this->rampManager = $rampManager;
    }

    /**
     * @param array $data
     * @return array
     */
    public function processData(array $data): array
    {
        $response = [];
        // if data array is not multidimensional make multidimensional
        if (count($data) == count($data, COUNT_RECURSIVE)) {
            $data = [$data];
        }

        $this->checkRequiredParams($data);

        if ($this->validReservations) {
            try {
                $this->processReservations();
            } catch (Exception $e) {
                $response['errors'] = 'Error while processing reservations';
            }
        }

        if ($this->successfulReservations) {
            $response['successfulReservations'] = $this->successfulReservations;
        }

        if($this->failedReservations) {
            $response['failedReservations'] = $this->failedReservations;
        }

        if ($this->invalidReservations) {
            $response['invalidReservations'] = $this->invalidReservations;
        }

        return $response;
    }

    /**
     * @param array $requestData
     */
    public function checkRequiredParams(array $requestData)
    {

        foreach ($requestData as $k => $reservation) {
            $allArgumentsSet = 1;

            foreach (self::REQUIRED_PARAMS as $param) {
                if (!isset($reservation[$param])) {
                    $this->errors['errors'][$k][] = 'Missing argument ' . $param;
                    $allArgumentsSet = 0;
                }
            }

            //if all needed arguments present add reservation for further processing, otherwise add to invalid reservations
            if ($allArgumentsSet) {
                if ($this->isValidReservationDate($reservation[self::RESERVATION_FROM], $k)) {
                    $this->validReservations[$k] = $reservation;
                } else {
                    $this->invalidReservations[$k] = ['errors' => $this->errors['errors'][$k], 'reservation' => $reservation];
                }
            } else {
                $this->invalidReservations[$k] = ['errors' => $this->errors['errors'][$k], 'reservation' => $reservation];
            }
        }
    }

    /**
     * @param string $date
     * @param int $key
     * @param string $format
     * @return bool
     */
    public function isValidReservationDate(string $date, int $key = 0, $format = 'Y-m-d H:i'): bool
    {
        $reservationDate = DateTime::createFromFormat($format, $date);
        if ($reservationDate === false) {
            $this->errors['errors'][$key][] = 'Invalid reservation date format';
            return false;
        }

        if (new DateTime() > $reservationDate) {
            $this->errors['errors'][$key][] = 'Reservation date is in the past';
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function processReservations()
    {
        $reservations = $this->getValidReservations();

        foreach ($reservations as $k => $reservation) {
            $reservationDate = new DateTimeImmutable($reservation[self::RESERVATION_FROM]);
            $reservationDay = $reservationDate->format('N');
            $duration = $reservation[self::RESERVATION_DURATION];
            $reservationEnd = $reservationDate->add(new DateInterval('PT' . $duration . 'M')); // add reservation duration in minutes to get reservation end datetime
            //$reservationEnd = $reservationDate->modify("+{$duration} minutes");
            $availableRamps = $this->rampManager->getAvailableRamps($reservationDay, $reservationDate, $reservationEnd);

            if ($availableRamps) {
                $reservationSuccessful = false;
                foreach ($availableRamps as $ramp) {
                    $rampId = $ramp['id'];
                    $reservationPeriod = $this->getReservationDateAccordingRampWorkTime($ramp, $reservationDate, $reservationEnd);
                    $reservationFromDate = $reservationPeriod[0];
                    $reservationToDate = $reservationPeriod[1];
                    $reservationsInPeriod = $this->findRampReservationsInPeriod($rampId, $reservationFromDate->format('Y-m-d H:i'), $reservationToDate->format('Y-m-d H:i'));

                    // if not reservations in this period we can already make reservation
                    if (!$reservationsInPeriod) {
                        if ($this->makeReservation($reservationFromDate->format('Y-m-d H:i'), $reservationToDate->format('Y-m-d H:i'), $reservation[self::CAR_NUMBER], $rampId)) {
                            $this->successfulReservations[$k] = [
                                'ramp_name' => $ramp['name'],
                                'ramp_code' => $ramp['code'],
                                'car_number' => $reservation[self::CAR_NUMBER],
                                'reservation_from' => $reservationFromDate->format('Y-m-d H:i'),
                                'reservation_to' => $reservationToDate->format('Y-m-d H:i')
                            ];
                            $reservationSuccessful = true;
                            break;
                        }
                    }

                }

                if (!$reservationSuccessful) { // if no free time on any ramp for requested period find other free times
                    $closestAvailableTimes = [];
                    foreach ($availableRamps as $ramp) {
                        $closestAvailableTimes[] = $this->getClosestAvailableTimeForRamp($ramp, $reservationDate, $duration, $reservationEnd);
                    }

                    $closestTime = array_reduce(array_filter($closestAvailableTimes), function($carry, $item) {
                        if ($carry === null || $item['from'] < $carry['from']) {
                            return $item;
                        }
                        return $carry;
                    });

                    if ($closestTime) {
                        $newReservationFrom = new DateTimeImmutable($closestTime['from']);
                        $newEndTime = $newReservationFrom->add(new DateInterval('PT' . $duration . 'M'));

                        if ($this->makeReservation(
                            $newReservationFrom->format('Y-m-d H:i'),
                            $newEndTime->format('Y-m-d H:i'),
                            $reservation[self::CAR_NUMBER],
                            $closestTime['ramp']['id']
                        )) {
                            $this->successfulReservations[$k] = [
                                'ramp_name' => $closestTime['ramp']['name'],
                                'ramp_code' => $closestTime['ramp']['code'],
                                'car_number' => $reservation[self::CAR_NUMBER],
                                'reservation_from' => $newReservationFrom->format('Y-m-d H:i'),
                                'reservation_to' => $newEndTime->format('Y-m-d H:i')
                            ];
                        }
                    } else {
                        $this->errors['errors'][$k][] = 'No available times';
                        $this->failedReservations[$k] = ['errors' => $this->errors['errors'][$k], 'reservation' => $reservation];
                    }
                }
            } else {
                $this->errors['errors'][$k][] = 'No available ramps';
                $this->failedReservations[$k] = ['errors' => $this->errors['errors'][$k], 'reservation' => $reservation];
            }
        }
    }

    /**
     * @param string $rampId
     * @param string $reservationFrom
     * @param string $reservationTo
     * @return mixed
     */
    public function findRampReservationsInPeriod(string $rampId, string $reservationFrom, string $reservationTo)
    {
        $sql = "SELECT * FROM reservations WHERE ((reservation_from <= :reservation_from && reservation_till >= :reservation_from)
            OR (reservation_from >= :reservation_from && reservation_from <= :reservation_to)  
            OR (reservation_from = :reservation_from && reservation_till = :reservation_to))
            AND ramp_id = :ramp_id ORDER BY reservation_from";


        return $this->db->fetchAll($sql, ['reservation_from' => $reservationFrom, 'reservation_to' => $reservationTo,
            'ramp_id' => $rampId]);
    }

    /**
     * @param array $ramp
     * @param DateTimeInterface $reservationFrom
     * @param DateTimeInterface $reservationTo
     * @return array
     * @throws Exception
     */
    private function getReservationDateAccordingRampWorkTime(array $ramp, DateTimeInterface $reservationFrom, DateTimeInterface $reservationTo): array
    {
        $rampOpens = $ramp['worktime']['open'];
        $resFrom = $reservationFrom;
        $resTo = $reservationTo;

        if ($reservationFrom < $rampOpens) {
            $diff = $rampOpens->diff($reservationFrom);
            $resFrom = $reservationFrom->add(new DateInterval('PT' . $diff->h . 'H' . $diff->i . 'M'));
            $resTo = $reservationTo->add(new DateInterval('PT' . $diff->h . 'H' . $diff->i . 'M'));
        }

        return [$resFrom, $resTo];
    }

    /**
     * @param array $ramp
     * @param DateTimeInterface $reservationFrom
     * @param int $reservationDuration
     * @param DateTimeInterface $reservationTo
     * @return array|mixed
     * @throws Exception
     */
    private function getClosestAvailableTimeForRamp(array $ramp, DateTimeInterface $reservationFrom, int $reservationDuration, DateTimeInterface $reservationTo)
    {
        $availableTimes = $this->getAvailableTimesForRamp($ramp);

        $closestPeriod = [];

        $reservationDates = $this->getReservationDateAccordingRampWorkTime($ramp, $reservationFrom, $reservationTo);
        $reservationFromDate = $reservationDates[0];

        foreach ($availableTimes as $period) {
            if ($period['from'] >= $reservationFromDate->format('Y-m-d H:i') && $period['duration'] >= $reservationDuration) {
                $closestPeriod = $period;
            }
        }

        return $closestPeriod;
    }

    /**
     * @param array $ramp
     * @return array
     * @throws Exception
     */
    private function getAvailableTimesForRamp(array $ramp): array
    {
        $sql = "SELECT reservation_from, reservation_till, ramp_id FROM reservations WHERE ramp_id = :ramp_id ORDER BY reservation_from";

        $reservations = $this->db->fetchAll($sql, ['ramp_id' => $ramp['id']]);

        $rampOpenFrom = $ramp['worktime']['open'];
        $rampCloses = $ramp['worktime']['close'];

        $availableTimes = [];

        if (count($reservations)) {
            for ($i = 0; $i < count($reservations); $i++) {
                if ($i == 0) { //for first reservation time between ramp opening time and reservation start time
                    $d1 = $rampOpenFrom;
                    $d2 = new DateTimeImmutable($reservations[$i]['reservation_from']);
                    $diff = $d2->diff($d1);
                    $duration = ($diff->h * 60) + $diff->i; // calculate diff in minutes
                    $availableTimes[] = ['from' => $rampOpenFrom->format('Y-m-d H:i'), 'to' => $reservations[$i]['reservation_from'], 'duration' => $duration, 'ramp' => $ramp];
                } elseif ($i == (count($reservations) - 1)) { // for last reservation between last reservation end time and ramp closing time
                    $d1 = new DateTimeImmutable($reservations[count($reservations) -1]['reservation_till']);
                    $d2 = $rampCloses;
                    $diff = $d2->diff($d1);
                    $duration = ($diff->h * 60) + $diff->i; // calculate diff in minutes
                    $availableTimes[] = ['from' => $reservations[count($reservations) -1]['reservation_till'], 'to' => $rampCloses->format('Y-m-d H:i'), 'duration' => $duration, 'ramp' => $ramp];
                } else {
                    $d1 = new DateTimeImmutable($reservations[$i]['reservation_till']);
                    $d2 = new DateTimeImmutable($reservations[$i+1]['reservation_from']);
                    $diff = $d2->diff($d1);
                    $duration = ($diff->h * 60) + $diff->i; // calculate diff in minutes
                    $availableTimes[] = ['from' => $reservations[$i]['reservation_till'], 'to' => $reservations[$i+1]['reservation_from'], 'duration' => $duration, 'ramp' => $ramp];
                }
            }
        } else { // if there are no reservations available all time from ramp open time to ramp closing time
            $d1 = $rampOpenFrom;
            $d2 = $rampCloses;
            $diff = $d2->diff($d1);
            $duration = ($diff->h * 60) + $diff->i;
            $availableTimes[] = ['from' => $rampOpenFrom->format('Y-m-d H:i'), 'to' => $rampCloses->format('Y-m-d H:i'), 'duration' => $duration, 'ramp' => $ramp];
        }

        return array_filter($availableTimes);
    }

    /**
     * @param string $reservationFrom
     * @param string $reservationTill
     * @param string $carNumber
     * @param int $rampId
     * @return mixed
     */
    private function makeReservation(string $reservationFrom, string $reservationTill, string $carNumber, int $rampId)
    {
        $sql = "INSERT INTO reservations (reservation_from, reservation_till, car_number, ramp_id) 
                    VALUES (:reservation_from, :reservation_till, :car_number, :ramp_id)";


        return $this->db->executeQuery($sql, [
            'reservation_from' => $reservationFrom,
            'reservation_till' => $reservationTill,
            'car_number' => $carNumber,
            'ramp_id' => $rampId]
        );
    }

    /**
     * @return array
     */
    public function getValidReservations(): array
    {
        return $this->validReservations;
    }

    /**
     * @return array
     */
    public function getInvalidReservations(): array
    {
        return $this->invalidReservations;
    }
}