<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | A poll and all of its votes are deleted this many days after the poll
    | closes. Show this value to users wherever a poll is created or managed.
    |
    */

    'retention_days' => (int) env('POLL_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Operator
    |--------------------------------------------------------------------------
    |
    | Who is named as the data controller in the privacy policy. Both are
    | optional; the policy leaves out what is not set.
    |
    */

    'operator' => [
        'name' => env('OPERATOR_NAME'),
        'email' => env('CONTACT_EMAIL'),
    ],

];
