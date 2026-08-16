<?php

use App\Http\Controllers\Api\InternTicketApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.api')->group(function () {
<<<<<<< HEAD
=======
    Route::get('/intern/tickets', [InternTicketApiController::class, 'index']);
>>>>>>> old-ticket-system/main
    Route::post('/intern/tickets', [InternTicketApiController::class, 'store']);
});
