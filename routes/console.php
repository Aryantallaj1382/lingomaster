<?php


use Illuminate\Support\Facades\Schedule;

Schedule::command('app:send-birthday-sms')->daily();
Schedule::command('app:send-birthday-professor-sms')->daily();
Schedule::command('reservations:clear-unpaid')->everyThirtyMinutes();
//Schedule::command('app:check-professors-data')->twiceDaily(8,19);
Schedule::command('app:check-professors-data')->daily();
//Schedule::command('professors:generate-code')->everyMinute();
