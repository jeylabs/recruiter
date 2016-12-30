<?php

return [
    'secret_key' => env('RECRUITER_ACCESS_TOKEN', 'YOUR_RECRUITER_ACCESS_TOKEN'),
    'recruiter_api_babe_uri' => env('RECRUITER_API_BASE_URI', 'https://dd.jeylabs.com'),
    'async_requests' => env('RECRUITER_ASYNC_REQUESTS', false),
];
