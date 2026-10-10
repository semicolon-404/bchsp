<?php

/*
 * Settings of this site that are not Laravel's own.
 */
return [

    'access_log' => [
        // How long a line of the access log is kept before the nightly clean-up removes it. Never fewer than 90 days:
        // Thai law (the Computer Crime Act) asks for traffic logs to be kept at least that long.
        'retention_days' => (int) env('ACCESS_LOG_RETENTION_DAYS', 365),
    ],

    'security' => [
        // Whether everyone who uses the panel must set up two-step sign-in before doing anything else (App\Support\TwoStep).
        'staff_two_step' => (bool) env('STAFF_TWO_STEP', true),
    ],

];
