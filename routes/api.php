<?php

use App\Http\Controllers\Resepsionis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentication;
use App\Http\Controllers\Administrator;
use App\Http\Controllers\Tamu;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [Authentication::class, 'register']);
Route::post('login', [Authentication::class, 'login']);
Route::post('refresh', [Authentication::class, 'refresh']);
Route::post('logout', [Authentication::class, 'logout']);

Route::group(['middleware' => ['auth:api', 'role:admin']], function () {
    // CRUD TIPE KAMAR
    Route::get('getTipeKamar/{id?}', [Administrator::class, 'getTipeKamar']);
    Route::post('createTipeKamar', [Administrator::class, 'createTipeKamar']);
    Route::put('updateTipeKamar/{id}', [Administrator::class, 'updateTipeKamar']);
    Route::delete('deleteTipeKamar/{id}', [Administrator::class, 'deleteTipeKamar']);

    // CRUD KAMAR
    Route::get('getKamar', [Administrator::class, 'getKamar']);
    Route::post('createKamar', [Administrator::class, 'createKamar']);
    Route::put('updateKamar/{id}', [Administrator::class, 'updateKamar']);
    Route::delete('deleteKamar/{id}', [Administrator::class, 'deleteKamar']);
    
    // CRUD USER
    Route::get('getProfile/{id?}', [Authentication::class, 'getProfile']);
    Route::put('updateProfile/{id}', [Authentication::class, 'updateProfile']);
    Route::delete('deleteProfile/{id}', [Authentication::class, 'deleteProfile']);
});

Route::group(['middleware' => ['auth:api', 'role:tamu']], function () {
    Route::get('getAvailable', [Tamu::class, 'getAvailable']);
    Route::post('createOrder', [Tamu::class, 'createOrder']);
    Route::put('updateOrder/{id_pemesanan}', [Tamu::class, 'updateOrder']);
    Route::get('getNota/{id_pemesanan}', [Tamu::class, 'getNota']);
});

Route::group(['middleware' => ['auth:api', 'role:resepsionis']], function () {
    Route::post('checkIn', [Resepsionis::class, 'checkIn']);
    Route::post('checkOut', [Resepsionis::class, 'checkOut']);
    Route::get('getPemesanan', [Resepsionis::class, 'getPemesanan']);
    Route::get('getFiltered', [Resepsionis::class, 'getFiltered']);
});
