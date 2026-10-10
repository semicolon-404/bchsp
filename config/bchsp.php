<?php

/*
 * Settings of this site that are not Laravel's own.
 */
return [

    'security' => [
        // Whether everyone who uses the panel must set up two-step sign-in before doing anything else (App\Support\TwoStep).
        'staff_two_step' => (bool) env('STAFF_TWO_STEP', true),
    ],

];
