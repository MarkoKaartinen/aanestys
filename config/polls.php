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

];
