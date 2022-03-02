<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/loans', [LoanController::class, 'index']);
Route::post('/loans', [LoanController::class, 'create']);
Route::get('/loans/{loanId}', [LoanController::class, 'show']);
Route::post('/loans/{loanId}/transactions', [LoanController::class, 'createTransaction']);
