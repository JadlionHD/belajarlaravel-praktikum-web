<?php

use Illuminate\Support\Facades\Route;

test('tickets index displays list of tickets', function () {
    $response = $this->get(route('tickets.index'));

    $response->assertOk();
    $response->assertViewIs('tickets.index');
    $response->assertSee('Daftar Tiket');
    $response->assertSee('Tidak dapat login');
    $response->assertSee(route('tickets.show', ['ticket' => 1]));
});

test('tickets show displays ticket details', function () {
    $response = $this->get(route('tickets.show', ['ticket' => 1]));

    $response->assertOk();
    $response->assertViewIs('tickets.show');
    $response->assertSee('Detail Tiket #1');
    $response->assertSee('Subjek: Tidak dapat login');
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

test('api tickets show returns json with data key', function () {
    $response = $this->get(route('tickets.show-json', ['ticket' => 1]));

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id' => 1,
            'subject' => 'Tidak dapat login',
            'status' => 'open',
        ],
    ]);
});

test('api tickets show returns 404 for non-existent ticket', function () {
    $response = $this->get('/api/tickets/999');

    $response->assertNotFound();
});

test('api tickets show rejects non-numeric parameter', function () {
    $response = $this->get('/api/tickets/abc');

    $response->assertNotFound();
});

test('all three ticket routes have valid names', function () {
    expect(Route::has('tickets.index'))->toBeTrue();
    expect(Route::has('tickets.show'))->toBeTrue();
    expect(Route::has('tickets.show-json'))->toBeTrue();
});
