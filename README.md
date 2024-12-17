Service accepts array of arrays encoded to json (multiple reservations)
```[{"from":"2024-12-14 14:00","duration":60,"car_number":"ABC123"},{"from":"2024-12-13 17:00","duration":30,"car_number":"HAV178"},{"from":"2024-12-15 8:00","duration":60,"car_number":"ROP698"}]```

and array encoded to json (in case of single reservation
```{"from":"2024-12-14 14:00","duration":"2024-12-14 15:00","car_number":"ABC123"}```


Response example (Response could also have [failedReservations] element in case reservation data is valid but reservation cannot be made for other reason)

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