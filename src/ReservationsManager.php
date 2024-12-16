<?php


class ReservationsManager
{
    const REQUIRED_PARAMS = [self::RESERVATION_FROM, self::RESERVATION_DURATION, self::CAR_NUMBER];

    const RESERVATION_FROM = 'reservation_from';
    const RESERVATION_DURATION = 'reservation_duration';
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
     */
    public function __construct($db, $rampManager)
    {
        $this->db = $db;
        $this->rampManager = $rampManager;
    }

    public function processData($data)
    {
        $this->checkRequiredParams($data);

        if ($this->validReservations) {
            $this->processReservations();
        }

        /*$validReservations = $this->validReservations;

        foreach ($validReservations as $reservation) {

        }*/
    }

    public function checkRequiredParams(array $params)
    {
        //$reservations = [];
        //$invalidReservations = [];
        foreach ($params as $k => $reservation) {
            $allArgumentsSet = 1;
            foreach (self::REQUIRED_PARAMS as $param) {
                if (!isset($reservation[$param])) {
                    $this->errors['errors'][$k][] = ['Missing argument ' . $param];
                    $allArgumentsSet = 0;
                }
            }
            //if all needed arguments present add reservation for further processing, otherwise add to invalid reservations
            if ($allArgumentsSet) {
                if ($this->isValidReservationDate($reservation[self::RESERVATION_FROM], $k)) {
                    $this->validReservations[$k] = $reservation; //@TODO: if all arguments set check here if dates are valid
                } else {
                    $this->invalidReservations[$k] = $reservation;
                }
            } else {
                $this->invalidReservations[$k] = $reservation;
            }
        }
    }

    /**
     * @param $date
     * @param $key
     * @param string $format
     * @return bool
     */
    public function isValidReservationDate($date, $key, $format = 'Y-m-d H:i'): bool
    {
        $reservationDate = DateTime::createFromFormat($format, $date);
        if ($reservationDate === false) {
            $this->errors['errors'][$key][] = ['Invalid reservation date format'];
            return false;
        }

        if (new DateTime() > $reservationDate) {
            $this->errors['errors'][$key][] = ['Reservation date is in the past'];
            return false;
        }

        return true;
    }

    public function checkReservationDates(array $reservations, $format = 'Y-m-d H:i')
    {
        foreach ($reservations as $k => $reservation) {
            $date = DateTime::createFromFormat($format, $reservation[self::RESERVATION_FROM]);
            // check if date format is correct
            if ($date === false) {
                $this->errors['errors'][$k][] = ['Invalid reservation date format'];
                $this->invalidReservations[$k] = $reservation;
                unset($this->validReservations[$k]);
                continue;
            }
            // check if date is not in the past
            if (new DateTime() > $date) {
                $this->errors['errors'][$k][] = ['Reservation date is in the past'];
                $this->invalidReservations[$k] = $reservation;
                unset($this->validReservations[$k]);
            }
        }
    }

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
                    $reservationsInPeriod = $this->findRampReservationsInPeriod($rampId, $reservationDate->format('Y-m-d H:i'), $reservationEnd->format('Y-m-d H:i'));
                    // if not reservations in this period we can make reservation
                    if (!$reservationsInPeriod) {
                        if ($this->makeReservation($reservationDate, $reservationEnd, $reservation[self::CAR_NUMBER], $rampId)) {
                            $this->successfulReservations[$k] = [
                                'ramp_name' => $ramp['name'],
                                'ramp_code' => $ramp['code'],
                                'car_number' => $reservation[self::CAR_NUMBER],
                                'reservation_from' => $reservation[self::RESERVATION_FROM],
                                'reservation_to' => $reservationEnd
                            ];
                            $reservationSuccessful = true;
                            break;
                        }
                    } /*else {
                        // if there conflict find other times
                        $last = end($reservationsInPeriod);
                        $reservationDate = $last['reservation_to'];
                    }*/

                }
                if (!$reservationSuccessful) { // if no free time on any ramp for requested period find other free times
                    foreach ($availableRamps as $ramp) {
                        $closest = $this->getClosestAvailableTimeForRamp($ramp, $reservationDate, $reservationEnd);

                    }
                }
            } else {
                $this->errors['errors'][$k] = ['No available ramps'];
                $this->failedReservations[$k] = $reservation;
            }
        }
    }

    public function findRampReservationsInPeriod(string $rampId, string $reservationFrom, string $reservationTo)
    {
        $sql = "SELECT * FROM reservations WHERE (reservation_from <= :reservation_from && reservation_till >= :reservation_from)
            OR (reservation_from >= :reservation_from && reservation_from <= :reservation_to)  
            OR (reservation_from = :reservation_from && reservation_till = :reservation_to)
            AND ramp_id = :ramp_id ORDER BY reservation_from";

        return $this->db->fetchAll($sql, ['reservation_from' => $reservationFrom, 'reservation_till' => $reservationTo,
            'ramp_id' => $rampId]);
    }

    private function getClosestAvailableTimeForRamp(array $ramp, DateTimeInterface $reservationFrom, $reservationDuration)
    {
        $availableTimes = $this->getAvailableTimesForRamp($ramp);

        $closestPeriod = [];

        foreach ($availableTimes as $period) {
            if ($period['from'] >= $reservationFrom->format('Y-m-d H:i') && $period['duration'] >= $reservationDuration) {
                $closestPeriod = $period;
                //@TODO: get difference between required reservation date and found free to determine which ramp closer free time
            }
        }

        return $closestPeriod;
    }

    //@TODO: change function to get ramp work time from datetime object (set them in getAvailableRamps())
    private function getAvailableTimesForRamp(array $ramp): array
    {
        $sql = "SELECT reservation_from, reservation_till, ramp_id FROM reservations WHERE ramp_id = :ramp_id";

        $reservations = $this->db->fetchAll($sql, ['ramp_id' => $ramp['id']]);
        /*$rampWorkTime = json_decode($ramp['worktime']);
        $rampOpenFrom = $rampWorkTime[$dayOfWeek]['open'];
        $rampCloses = $rampWorkTime[$dayOfWeek]['close'];*/
        $rampOpenFrom = $ramp['worktime']['open'];
        $rampCloses = $ramp['worktime']['close'];

        $availableTimes = [];
        /*foreach ($reservations as $reservation) {
            $availableTimes[] =
        }*/
        for ($i = 0; $i < count($reservations); $i++) {
            if ($i == 0) { //for first reservation time between ramp opening time and reservation start time
                //$d1 = new DateTimeImmutable($rampOpenFrom);
                $d1 = $rampOpenFrom;
                $d2 = new DateTimeImmutable($reservations[$i]['reservation_from']);
                $diff = $d2->diff($d1);
                $duration = ($diff->h * 60) + $diff->i; // calculate diff in minutes
                $availableTimes[] = ['from' => $rampOpenFrom, 'to' => $reservations[$i]['reservation_from'], 'duration' => $duration];
            } elseif ($i == (count($reservations) - 1)) { // for last reservation between last reservation end time and ramp closing time
                $d1 = new DateTimeImmutable($reservations[count($reservations) -1]['reservation_till']);
                //$d2 = new DateTimeImmutable($rampCloses);
                $d2 = $rampCloses;
                $diff = $d2->diff($d1);
                $duration = ($diff->h * 60) + $diff->i; // calculate diff in minutes
                $availableTimes[] = ['from' => $reservations[count($reservations) -1]['reservation_till'], 'to' => $rampCloses, 'duration' => $duration];
            } else {
                $d1 = new DateTimeImmutable($reservations[$i]['reservation_till']);
                $d2 = new DateTimeImmutable($reservations[$i+1]['reservation_from']);
                $diff = $d2->diff($d1);
                $duration = ($diff->h * 60) + $diff->i; // calculate diff in minutes
                $availableTimes[] = ['from' => $reservations[$i]['reservation_till'], 'to' => $reservations[$i+1]['reservation_from'], 'duration' => $duration];
            }
        }

        return $availableTimes;
    }

    private function makeReservation($reservationFrom, $reservationTill, $carNumber, $rampId)
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

    public function getAvailableRamps($reservation)
    {

        $ramps = $this->rampManager->getAllRamps();

        $date = new DateTime($reservation[self::RESERVATION_FROM]);
        $duration = $reservation[self::RESERVATION_DURATION];
        $reservationEnd = $date->add(new DateInterval('PT' . $duration . 'M'));
        //$reservationEnd = $date->modify("+{$duration} minutes");
        $reservationDay = $date->format('N');
        foreach ($ramps as $ramp) {
            $rampWorkDays = json_encode($ramp['worktime']);
        }
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

    private function save()
    {
        //$this->db->save();
    }
}