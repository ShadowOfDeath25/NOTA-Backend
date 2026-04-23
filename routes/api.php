<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/v1/api.php';
});

Route::prefix('v2')->group(function () {
    require __DIR__.'/v2/api.php';
});
