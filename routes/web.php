<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\TicketController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia("/", "Welcome")->name("home");

Route::prefix("{current_team}")
    ->middleware(["auth", "verified", EnsureTeamMembership::class])
    ->group(function () {
        Route::get("dashboard", DashboardController::class)->name("dashboard");
    });

Route::middleware(["auth"])->group(function () {
    Route::post("invitations/{invitation}/accept", [
        TeamInvitationController::class,
        "accept",
    ])->name("invitations.accept");
    Route::delete("invitations/{invitation}", [
        TeamInvitationController::class,
        "decline",
    ])->name("invitations.decline");
});

Route::pattern("ticket", "[0-9]+");
Route::resource("tickets", TicketController::class);

require __DIR__ . "/settings.php";
