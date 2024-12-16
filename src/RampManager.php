<?php


class RampManager
{
    /**
     * @var DB
     */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllRamps()
    {
        return $this->db->fetchAll("SELECT * FROM ramps ORDER BY priority");
    }

    public function getAvailableRamps(string $weekDay, DateTimeInterface $reservationDate, DateTimeInterface $reservationEnd): array
    {
        $ramps = $this->getAllRamps();
        $availableRamps = [];
        foreach ($ramps as $k => $ramp) {
            $workTime = json_decode($ramp['worktime']);

            if (isset($workTime[$weekDay])) {
                $workTimeStartObject = new DateTimeImmutable($reservationDate->format('Y-m-d') . ' ' . $workTime[$weekDay]['open']);
                $workTimeEndObject = new DateTimeImmutable($reservationDate->format('Y-m-d') . ' ' . $workTime[$weekDay]['close']);
            //    $reservationFromHours = $reservationDate->format('H');
            //    $reservationEndHours = $reservationEnd->format('H');
                if ($reservationDate < $workTimeStartObject) { // if reservation_from time earlier then ramp open time re-assign reservation_from and to dates
                    $diff = $workTimeStartObject->diff($reservationDate);
                    $reservationDate = $reservationDate->add(new DateInterval('PT' . $diff->h . 'H' . $diff->i . 'M'));
                    $reservationEnd = $reservationEnd->add(new DateInterval('PT' . $diff->h . 'H' . $diff->i . 'M'));
                }

                if ($reservationEnd <= $workTimeEndObject) { // if reservation ends before ramp closes add this ramp to available
                    $ramp[$k]['worktime']['open'] = $workTimeStartObject; // uncomment this then change getAvailableTimesForRamp() function in ReservationsManager
                    $ramp[$k]['worktime']['close'] = $workTimeEndObject;

                    $availableRamps[] = $ramp;
                }
            }

        }

        return $availableRamps;
    }
}