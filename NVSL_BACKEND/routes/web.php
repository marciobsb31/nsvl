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

/*
 * URL frequentemente cadastrada no MGI junto com a base e /redirect-gov.
 * O encerramento de sessão da API continua em POST /api/auth/logout (Bearer).
 */
Route::get('/logout', function () {
    $base = rtrim((string) config('govbr.frontend_url'), '/');
    $login = (string) config('govbr.frontend_login_path', '/login');

    return redirect()->away($base . $login . (str_contains($login, '?') ? '&' : '?') . 'from=logout');
})->name('web.logout');