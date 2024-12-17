<?php


class Api
{
    const REQUIRED_PARAMS = ['reservation_date', 'reservation_from', 'reservation_time', 'car_number'];

    private $errors = [];

    /**
     * @var ReservationsManager $reservationsManager
     */
    private $reservationsManager;

    public function __construct($reservationsManager)
    {
        $this->reservationsManager = $reservationsManager;
    }

    public function processRequest(string $requestData)
    {
       // $this->validateRequestData($requestData);
        if (!$this->isValidJson($requestData)) {
            $this->JsonResponse(['errors' => ['date is invalid json format']]);
        }
        //print_r($requestData); exit;
        $processedReservationsData = $this->reservationsManager->processData(json_decode($requestData, true));

        $this->JsonResponse($processedReservationsData);
    }

    private function validateRequestData($data)
    {
        if (!$this->isValidJson($data)) {
            $this->JsonResponse(['errors' => ['date is invalid json format']]);
        }

        $data = json_decode($data, true);
        //@TODO: check if multidimensional array (multiple reservations)
        foreach (self::REQUIRED_PARAMS as $param) {
            if (!isset($data[$param])) {
                $this->errors[] = 'Missing param ' . $param;
            }
        }

        if ($this->errors) {
            $this->JsonResponse(['errors' => $this->errors]);
        }
    }

    private function validateDate(string $date, string $format = 'Y-m-d H:i')
    {
        $date = DateTime::createFromFormat($format, $date);
        // check if date format is valid
        if ($date === false) {
            $this->errors[] = 'Invalid date';
        } else {
            //check if date is not in the past
            if (new DateTime() > $date) {
                $this->errors[] = 'Date is in the past';
            }
        }
    }

    /**
     * @param $data
     * @return bool
     */
    private function isValidJson($data): bool
    {
        json_decode($data);
        return json_last_error() == JSON_ERROR_NONE;
    }

    private function JsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        $response = json_encode($data);
        if ($response === false) {
            $response = ["Errors" => ["Error during data encoding"]];
        }
        echo json_encode($response);
        exit;
    }
}