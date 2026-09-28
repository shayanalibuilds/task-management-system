<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('tasks:due-reminders')->dailyAt('08:00');
