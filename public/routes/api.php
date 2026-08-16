<?php

use App\Http\Controllers\Api\InternTicketApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.api')->group(function () {
    Route::get('/intern/tickets', [InternTicketApiController::class, 'index']);
    Route::post('/intern/tickets', [InternTicketApiController::class, 'store']);
});
