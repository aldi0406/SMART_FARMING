<?php

return [
    'dashboard' => [
        'default_device_uid' => env('SMART_FARMING_DEVICE_UID', 'ESP32-01'),
        'refresh_interval_seconds' => 1,
    ],

    'pump' => [
        'soil_moisture_on_below' => 40,
        'soil_moisture_off_above' => 70,
        'cooldown_seconds' => 60,
    ],
];
