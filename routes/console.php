<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune')->daily();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('model:prune')->daily();
