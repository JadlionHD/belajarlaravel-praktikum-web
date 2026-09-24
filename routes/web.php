<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\TicketController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::post('/login', [SessionController::class, 'login'])
    ->middleware('throttle:api-login')
    ->name('login.store');
Route::post('/logout', [SessionController::class, 'logout'])
    ->middleware('auth:web')
    ->name('logout');

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/pertemuan6', 'Pertemuan6')->name('pertemuan6');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [
        TeamInvitationController::class,
        'accept',
    ])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [
        TeamInvitationController::class,
        'decline',
    ])->name('invitations.decline');
});

// Rute tiket lama dinonaktifkan untuk keamanan sesuai modul Pertemuan 5
// Route::pattern("ticket", "[0-9]+");
// Route::resource("tickets", TicketController::class);

require __DIR__.'/settings.php';
