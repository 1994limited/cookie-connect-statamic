<?php

use Illuminate\Support\Facades\Route;
use NineteenNinetyFour\CookieConnect\Http\Controllers\CP\SitesController;

Route::prefix('cookie-connect')->name('cookie-connect.')->group(function () {
    Route::get('/', [SitesController::class, 'edit'])->name('index');
    Route::get('{site}', [SitesController::class, 'edit'])->name('edit');
    Route::patch('{site}', [SitesController::class, 'update'])->name('update');
});
