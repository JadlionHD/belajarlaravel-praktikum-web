<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/status', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Laravel API sudah berjalan dengan baik',
        'framework' => 'Laravel',
        'frontend' => 'Vuejs',
    ]);
});

Route::pattern('ticket', '[0-9]+');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:api-v1'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('categories', function () {
            return response()->json(['data' => Category::orderBy('name')
                ->get(['id', 'name'])]);
        })->name('categories.index');

        Route::get('reports/summary', function () {
            Gate::authorize('view-ticket-summary');

            return response()->json(['data' => ['ticket_count' => Ticket::count()]]);
        })->name('reports.summary');

        Route::apiResource('tickets', TicketController::class);
    });
});
