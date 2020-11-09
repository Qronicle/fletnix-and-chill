<?php

use App\Http\Controllers\RoomController;
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

Route::get('/', [RoomController::class, 'index'])->name('index');
Route::get('/login', [RoomController::class, 'login'])->name('login');
Route::post('/login', [RoomController::class, 'loginPost']);
Route::get('/{roomType}/{roomId}', [RoomController::class, 'room'])->name('room');
