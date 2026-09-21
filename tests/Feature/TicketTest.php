<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Route;

test('tickets index displays list of tickets', function () {
    $ticket = Ticket::factory()->create([
        'subject' => 'Tidak dapat login',
    ]);

    $response = $this->get(route('tickets.index'));

    $response->assertOk();
    $response->assertViewIs('tickets.index');
    $response->assertSee('Daftar tiket');
    $response->assertSee('Tidak dapat login');
    $response->assertSee($ticket->category->name);
    $response->assertSee($ticket->user->name);
});

test('tickets show displays ticket details', function () {
    $ticket = Ticket::factory()->create([
        'subject' => 'Tidak dapat login',
        'status' => 'open',
    ]);

    $response = $this->get(route('tickets.show', $ticket));

    $response->assertOk();
    $response->assertViewIs('tickets.show');
    $response->assertSee('#'.$ticket->id.' — Tidak dapat login');
    $response->assertSee('Status: open');
    $response->assertSee(route('tickets.index'));
});

test('tickets show returns 404 for non-existent ticket', function () {
    $response = $this->get('/tickets/999');

    $response->assertNotFound();
});

test('tickets show rejects non-numeric parameter', function () {
    $response = $this->get('/tickets/abc');

    $response->assertNotFound();
});

test('all seven ticket resource routes have valid names', function () {
    expect(Route::has('tickets.index'))->toBeTrue();
    expect(Route::has('tickets.create'))->toBeTrue();
    expect(Route::has('tickets.store'))->toBeTrue();
    expect(Route::has('tickets.show'))->toBeTrue();
    expect(Route::has('tickets.edit'))->toBeTrue();
    expect(Route::has('tickets.update'))->toBeTrue();
    expect(Route::has('tickets.destroy'))->toBeTrue();
});
