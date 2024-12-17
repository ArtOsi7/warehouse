<?php


class Api
{
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
        if (!$this->isValidJson($requestData)) {
            $this->JsonResponse(['errors' => ['date is invalid json format']]);
        }

        $processedReservationsData = $this->reservationsManager->processData(json_decode($requestData, true));

        $this->JsonResponse($processedReservationsData);
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