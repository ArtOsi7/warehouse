
Response example

```array
(
    [successfulReservations] => Array
        (
            [0] => Array
                (
                    [ramp_name] => RampOne
                    [ramp_code] => R1007
                    [car_number] => ABC717
                    [reservation_from] => 2024-12-18 08:00
                    [reservation_to] => 2024-12-18 09:00
                )

            [2] => Array
                (
                    [ramp_name] => RampTwo
                    [ramp_code] => R2007
                    [car_number] => PHP720
                    [reservation_from] => 2024-12-18 14:30
                    [reservation_to] => 2024-12-18 15:30
                )

        )

    [invalidReservations] => Array
        (
            [1] => Array
                (
                    [errors] => Array
                        (
                            [0] => Reservation date is in the past
                        )

                    [reservation] => Array
                        (
                            [reservation_from] => 2020-02-04 14:00
                            [car_number] => ZEZ505
                            [duration] => 30
                        )

                )

        )

)```