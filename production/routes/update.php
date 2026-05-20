<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UpdateController;
// use App\Http\Controllers\TestController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/update', function () {


});
#->prefix('update')
#Route::get('update/details', [UpdateController::class, 'details']);

Route::post('update/update-photo', [UpdateController::class, 'update_details']);
