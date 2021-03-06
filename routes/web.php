<?php

use App\Http\Controllers\ZohoController;
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
    return view('welcome');
});

Route::get('zohocrmauth', [ZohoController::class, 'auth'])->name('zohocrmauth');
Route::get('zohocrm', [ZohoController::class, 'store'])->name('zohocrm');
