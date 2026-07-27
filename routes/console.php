<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\CreditAccountService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(CreditAccountService::class)->suspendOverdueAccounts())
    ->hourly()
    ->name('suspend-overdue-credit-accounts')
    ->withoutOverlapping();
