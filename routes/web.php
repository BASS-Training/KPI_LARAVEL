<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes — SPA catch-all
|--------------------------------------------------------------------------
| Semua request web (termasuk path multi-segment seperti /hr/dashboard)
| dilayani oleh Vue SPA. Route API (/api/*) ditangani di routes/api.php.
|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('spa');
})->where('any', '.*');
