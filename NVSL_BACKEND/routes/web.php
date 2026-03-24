<?php

use App\Http\Controllers\Auth\GovBrAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response()->json(['status' => 'ok', 'message' => 'NVSL API is running']);
});

Route::get('/redirect-gov', [GovBrAuthController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('govbr.callback');