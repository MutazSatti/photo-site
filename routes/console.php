<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// التقييمات لا تتغيّر بالساعة — مرّة يوميًا تكفي وتوفّر حصّة الاستدعاءات.
// يتطلّب تشغيل جدولة Laravel على الخادم:
//   * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('reviews:sync')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->onOneServer();
