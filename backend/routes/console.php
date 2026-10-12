<?php

use Illuminate\Support\Facades\Schedule;

// Backup database harian (lihat README). Jalankan scheduler: php artisan schedule:work
Schedule::command('app:db-backup')->dailyAt('02:00');
