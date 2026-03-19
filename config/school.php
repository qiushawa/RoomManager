<?php

return [
    'semester' => [
        // 學期範圍，預設為 3/01 到 6/30
        'start_date' => env('SEMESTER_START_DATE', '03-01'),
        'end_date' => env('SEMESTER_END_DATE', '06-30'),
    ],
];
