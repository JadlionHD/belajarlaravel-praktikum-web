<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ApiTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('login-ip:127.0.0.1');

    $this->category = Category::factory()->create(['name' => 'Jaringan']);

    $this->ani = User::factory()->create([
        'name' => 'Ani',
        'email' => 'ani@example.test',
        'password' => Hash::make('LatihanWeb2!2026'),
        'is_admin' => false,
    ]);

    $this->budi = User::factory()->create([
        'name' => 'Budi',
        'email' => 'budi@example.test',
        'password' => Hash::make('LatihanWeb2!2026'),
        'is_admin' => false,
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Admin',
        'email' => 'admin@example.test',
        'password' => Hash::make('LatihanWeb2!2026'),
    ]);
    $this->admin->forceFill(['is_admin' => true])->save();
});

// TC-01: Login
test('TC-01: login returns 200 with token, 401 on invalid credentials with identical message, and 422 on empty fields', function () {
    $res = $this->postJson('/api/v1/auth/login', [
        'email' => 'ani@example.test',
        'password' => 'LatihanWeb2!2026',
        'device_name' => 'test-runner',
    ]);

    $res->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonStructure([
            'token_type',
            'access_token',
            'expires_at',
            'user' => ['id', 'name'],
        ]);
    expect($res->json('token_type'))->toBe('Bearer');

    $wrongPass = $this->postJson('/api/v1/auth/login', [
        'email' => 'ani@example.test',
        'password' => 'WrongPassword!',
        'device_name' => 'test-runner',
    ]);
    $wrongPass->assertStatus(401)
        ->assertJson(['message' => 'Kredensial tidak valid.']);

    $wrongEmail = $this->postJson('/api/v1/auth/login', [
        'email' => 'notfound@example.test',
        'password' => 'LatihanWeb2!2026',
        'device_name' => 'test-runner',
    ]);
    $wrongEmail->assertStatus(401)
        ->assertJson(['message' => 'Kredensial tidak valid.']);

    $emptyFields = $this->postJson('/api/v1/auth/login', []);
    $emptyFields->assertStatus(422);
});

// TC-02: Without authentication
test('TC-02: protected endpoints return 401 without valid token', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
    ]);

    $this->getJson('/api/v1/tickets')->assertStatus(401);
    $this->postJson('/api/v1/tickets', [])->assertStatus(401);
    $this->getJson('/api/v1/tickets/'.$ticket->id)->assertStatus(401);
    $this->putJson('/api/v1/tickets/'.$ticket->id, [])->assertStatus(401);
    $this->deleteJson('/api/v1/tickets/'.$ticket->id)->assertStatus(401);

    // Random invalid token
    $this->withToken('random-invalid-token')
        ->getJson('/api/v1/tickets')
        ->assertStatus(401);
});

// TC-03: Server identity
test('TC-03: create ticket derives owner from actor and rejects body spoofing user_id, is_admin, or status', function () {
    $token = $this->ani->createToken('test')->plainTextToken;

    $res = $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Wi-Fi ruang kuliah putus',
        'description' => 'Koneksi putus sejak pagi.',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Laporan awal.',
    ]);

    $res->assertStatus(201)
        ->assertHeader('Location');

    $ticketId = $res->json('data.id');
    $this->assertDatabaseHas('tickets', [
        'id' => $ticketId,
        'user_id' => $this->ani->id,
        'status' => 'open',
    ]);
    $this->assertDatabaseHas('comments', [
        'ticket_id' => $ticketId,
        'user_id' => $this->ani->id,
        'body' => 'Laporan awal.',
    ]);

    // Spoof user_id
    $spoofUserId = $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Tiket palsu',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Catatan',
        'user_id' => $this->budi->id,
    ]);
    $spoofUserId->assertStatus(422);

    // Spoof is_admin
    $spoofAdmin = $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Tiket palsu admin',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Catatan',
        'is_admin' => true,
    ]);
    $spoofAdmin->assertStatus(422);

    // Send status
    $sendStatus = $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Tiket palsu status',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Catatan',
        'status' => 'closed',
    ]);
    $sendStatus->assertStatus(422);
});

// TC-04: Private list
test('TC-04: ticket list returns only user own tickets with pagination', function () {
    Ticket::factory()->count(2)->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
    ]);
    Ticket::factory()->create([
        'user_id' => $this->budi->id,
        'category_id' => $this->category->id,
    ]);

    $tokenAni = $this->ani->createToken('ani')->plainTextToken;

    $res = $this->withToken($tokenAni)->getJson('/api/v1/tickets?per_page=1&page=1');
    $res->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(1, 'data');

    expect($res->json('meta.total'))->toBe(2);
    expect($res->json('data.0.owner.id'))->toBe($this->ani->id);

    // Out of bounds per_page
    $this->withToken($tokenAni)->getJson('/api/v1/tickets?per_page=51')->assertStatus(422);
    $this->withToken($tokenAni)->getJson('/api/v1/tickets?per_page=0')->assertStatus(422);
    $this->withToken($tokenAni)->getJson('/api/v1/tickets?page=0')->assertStatus(422);
});

// TC-05: IDOR detail
test('TC-05: user cannot view another user ticket and receives 403 without data leakage', function () {
    $ticketAni = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
        'subject' => 'Rahasia Ani',
        'description' => 'Deskripsi rahasia Ani',
    ]);

    $tokenBudi = $this->budi->createToken('budi')->plainTextToken;

    $res = $this->withToken($tokenBudi)->getJson('/api/v1/tickets/'.$ticketAni->id);
    $res->assertStatus(403);
    $res->assertDontSee('Rahasia Ani');
    $res->assertDontSee('Deskripsi rahasia Ani');
});

// TC-06: IDOR update/delete
test('TC-06: user cannot update or delete another user ticket (403) and DB remains unchanged', function () {
    $ticketAni = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
        'subject' => 'Tiket Asli Ani',
        'status' => 'open',
    ]);
    Comment::create([
        'ticket_id' => $ticketAni->id,
        'user_id' => $this->ani->id,
        'body' => 'Komentar awal',
    ]);

    $tokenBudi = $this->budi->createToken('budi')->plainTextToken;

    $updateRes = $this->withToken($tokenBudi)->putJson('/api/v1/tickets/'.$ticketAni->id, [
        'subject' => 'Dibajak Budi',
        'description' => 'Deskripsi Budi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'status' => 'pending',
        'note' => 'Catatan Budi',
    ]);
    $updateRes->assertStatus(403);

    $deleteRes = $this->withToken($tokenBudi)->deleteJson('/api/v1/tickets/'.$ticketAni->id);
    $deleteRes->assertStatus(403);

    expect($ticketAni->fresh()->subject)->toBe('Tiket Asli Ani');
    expect($ticketAni->fresh()->comments()->count())->toBe(1);
});

// TC-07: Owner update
test('TC-07: owner can update ticket with note added as comment, maintaining ownership', function () {
    $ticket = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
        'subject' => 'Subjek Sebelum',
        'status' => 'open',
    ]);
    Comment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $this->ani->id,
        'body' => 'Catatan pertama',
    ]);

    $token = $this->ani->createToken('ani')->plainTextToken;

    $res = $this->withToken($token)->putJson('/api/v1/tickets/'.$ticket->id, [
        'subject' => 'Wi-Fi sedang diperiksa',
        'description' => 'Petugas memeriksa koneksi.',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'status' => 'pending',
        'note' => 'Perubahan status oleh pemilik.',
    ]);

    $res->assertOk();
    expect($ticket->fresh()->subject)->toBe('Wi-Fi sedang diperiksa');
    expect($ticket->fresh()->status)->toBe('pending');
    expect($ticket->fresh()->comments()->count())->toBe(2);

    // Prohibited user_id or is_admin
    $prohibitedRes = $this->withToken($token)->putJson('/api/v1/tickets/'.$ticket->id, [
        'subject' => 'Wi-Fi update',
        'description' => 'Petugas memeriksa koneksi.',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'status' => 'pending',
        'note' => 'Catatan lagi',
        'user_id' => $this->budi->id,
    ]);
    $prohibitedRes->assertStatus(422);
});

// TC-08: Validation
test('TC-08: payload validation rejects malformed fields with 422', function () {
    $token = $this->ani->createToken('ani')->plainTextToken;

    // subject empty
    $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => '',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Catatan',
    ])->assertStatus(422);

    // subject > 150
    $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => str_repeat('a', 151),
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Catatan',
    ])->assertStatus(422);

    // description > 5000
    $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Subjek',
        'description' => str_repeat('a', 5001),
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => 'Catatan',
    ])->assertStatus(422);

    // note > 1000
    $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Subjek',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'note' => str_repeat('a', 1001),
    ])->assertStatus(422);

    // category_id invalid
    $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Subjek',
        'description' => 'Deskripsi',
        'category_id' => 99999,
        'is_urgent' => false,
        'note' => 'Catatan',
    ])->assertStatus(422);

    // is_urgent non-boolean
    $this->withToken($token)->postJson('/api/v1/tickets', [
        'subject' => 'Subjek',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => 'bukan-boolean',
        'note' => 'Catatan',
    ])->assertStatus(422);
});

// TC-09: Invalid ID
test('TC-09: non-existent numeric ID and non-numeric ID return 404', function () {
    $token = $this->ani->createToken('ani')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/tickets/99999')->assertStatus(404);
    $this->withToken($token)->getJson('/api/v1/tickets/abc')->assertStatus(404);
});

// TC-10: Closed and cascade
test('TC-10: deleting closed ticket returns 422, deleting open ticket returns 204 with cascading comments deleted', function () {
    $token = $this->ani->createToken('ani')->plainTextToken;

    $closedTicket = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
        'status' => 'closed',
    ]);
    $this->withToken($token)->deleteJson('/api/v1/tickets/'.$closedTicket->id)->assertStatus(422);
    expect(Ticket::find($closedTicket->id))->not->toBeNull();

    $openTicket = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
        'status' => 'open',
    ]);
    Comment::create([
        'ticket_id' => $openTicket->id,
        'user_id' => $this->ani->id,
        'body' => 'Komentar terbuka',
    ]);

    $res = $this->withToken($token)->deleteJson('/api/v1/tickets/'.$openTicket->id);
    $res->assertStatus(204);
    expect($res->content())->toBe('');

    $this->assertDatabaseMissing('tickets', ['id' => $openTicket->id]);
    $this->assertDatabaseMissing('comments', ['ticket_id' => $openTicket->id]);

    $this->withToken($token)->getJson('/api/v1/tickets/'.$openTicket->id)->assertStatus(404);
});

// TC-11: Role & Gate
test('TC-11: admin gate allows summary report, non-admin is forbidden (403), and admin cannot modify user tickets', function () {
    Ticket::factory()->count(3)->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
    ]);

    $tokenAni = $this->ani->createToken('ani')->plainTextToken;
    $tokenAdmin = $this->admin->createToken('admin')->plainTextToken;

    // Ani denied summary
    $this->withToken($tokenAni)->getJson('/api/v1/reports/summary')->assertStatus(403);

    app('auth')->forgetGuards();

    // Admin allowed summary
    $res = $this->withToken($tokenAdmin)->getJson('/api/v1/reports/summary');
    $res->assertOk()
        ->assertJson(['data' => ['ticket_count' => 3]]);

    app('auth')->forgetGuards();

    // Admin cannot modify Ani's ticket
    $ticketAni = Ticket::where('user_id', $this->ani->id)->first();
    $this->withToken($tokenAdmin)->putJson('/api/v1/tickets/'.$ticketAni->id, [
        'subject' => 'Admin mengubah',
        'description' => 'Deskripsi',
        'category_id' => $this->category->id,
        'is_urgent' => false,
        'status' => 'pending',
        'note' => 'Catatan admin',
    ])->assertStatus(403);
});

// TC-12: Rahasia / Sensitive fields
test('TC-12: response data does not expose sensitive attributes', function () {
    $token = $this->ani->createToken('ani')->plainTextToken;

    $ticket = Ticket::factory()->create([
        'user_id' => $this->ani->id,
        'category_id' => $this->category->id,
    ]);

    $resMe = $this->withToken($token)->getJson('/api/v1/me');
    $resMe->assertOk();
    expect($resMe->json('data'))->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret', 'is_admin']);

    $resDetail = $this->withToken($token)->getJson('/api/v1/tickets/'.$ticket->id);
    $resDetail->assertOk();

    $payload = json_encode($resDetail->json('data'));
    expect($payload)->not->toContain('password')
        ->not->toContain('remember_token')
        ->not->toContain('email')
        ->not->toContain('is_admin')
        ->not->toContain('access_token');
});

// TC-13: Logout
test('TC-13: logout revokes current token (204) while second token remains valid', function () {
    $token1 = $this->ani->createToken('device-1')->plainTextToken;
    $token2 = $this->ani->createToken('device-2')->plainTextToken;

    $logoutRes = $this->withToken($token1)->postJson('/api/v1/auth/logout');
    $logoutRes->assertStatus(204);

    app('auth')->forgetGuards();

    // Token 1 is now invalid
    $this->withToken($token1)->getJson('/api/v1/me')->assertStatus(401);

    app('auth')->forgetGuards();

    // Token 2 remains valid
    $this->withToken($token2)->getJson('/api/v1/me')->assertStatus(200);
});

// TC-14: Expiry
test('TC-14: expired token returns 401', function () {
    $token = $this->ani->createToken('expired-device', ['*'], now()->subMinute())->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);
});

// TC-15: Rate limit
test('TC-15: login rate limit returns 429 and Retry-After after 5 attempts', function () {
    RateLimiter::clear('login-ip:127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'ani@example.test',
            'password' => 'LatihanWeb2!2026',
            'device_name' => 'limiter-test',
        ]);
        $res->assertOk();
    }

    $sixth = $this->postJson('/api/v1/auth/login', [
        'email' => 'ani@example.test',
        'password' => 'LatihanWeb2!2026',
        'device_name' => 'limiter-test',
    ]);

    $sixth->assertStatus(429)
        ->assertHeader('Retry-After');
});

// TC-16: CORS
test('TC-16: allowed origin returns correct CORS headers while unlisted origin does not get origin permission', function () {
    $resAllowed = $this->options('/api/v1/tickets', [], [
        'Origin' => 'http://localhost:5173',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'authorization,content-type',
    ]);

    $resAllowed->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

    $resForbidden = $this->options('/api/v1/tickets', [], [
        'Origin' => 'http://localhost:5999',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'authorization,content-type',
    ]);

    // Origin 5999 tidak diizinkan sehingga header tidak mencocokkan origin pemohon
    expect($resForbidden->headers->get('Access-Control-Allow-Origin'))->not->toBe('http://localhost:5999');
});

// TC-17: Rollback / Transaction
test('TC-17: service rolls back create and update if comment fails', function () {
    $service = app(ApiTicketService::class);
    $initialTicketCount = Ticket::count();
    $initialCommentCount = Comment::count();

    $dispatcher = Comment::getEventDispatcher();
    Comment::setEventDispatcher(clone $dispatcher);

    try {
        Comment::creating(function () {
            throw new RuntimeException('Simulasi gagal komentar');
        });

        // Test create rollback
        try {
            $service->create($this->ani, [
                'category_id' => $this->category->id,
                'subject' => 'Tiket gagal',
                'description' => 'Deskripsi',
                'is_urgent' => false,
                'note' => 'Catatan',
            ]);
            $this->fail('Harusnya melempar exception.');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('Simulasi gagal komentar');
        }

        expect(Ticket::count())->toBe($initialTicketCount);
        expect(Comment::count())->toBe($initialCommentCount);

        // Test update rollback
        Comment::setEventDispatcher($dispatcher);
        $ticket = Ticket::factory()->create([
            'user_id' => $this->ani->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek sebelum gagal',
            'status' => 'open',
        ]);
        Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->ani->id,
            'body' => 'Catatan awal',
        ]);

        Comment::setEventDispatcher(clone $dispatcher);
        Comment::creating(function () {
            throw new RuntimeException('Simulasi gagal komentar');
        });

        try {
            $service->update($this->ani, $ticket, [
                'category_id' => $this->category->id,
                'subject' => 'Subjek baru',
                'description' => 'Deskripsi baru',
                'status' => 'pending',
                'is_urgent' => false,
                'note' => 'Catatan update',
            ]);
            $this->fail('Harusnya melempar exception.');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('Simulasi gagal komentar');
        }

        expect($ticket->fresh()->subject)->toBe('Subjek sebelum gagal');
        expect($ticket->fresh()->comments()->count())->toBe(1);
    } finally {
        Comment::setEventDispatcher($dispatcher);
    }
});

// TC-18: Old routes closed
test('TC-18: old ticket routes are closed and inaccessible', function () {
    $this->get('/tickets')->assertNotFound();
    $this->post('/tickets', [])->assertNotFound();
    $this->get('/tickets/1')->assertNotFound();
    $this->put('/tickets/1', [])->assertNotFound();
    $this->delete('/tickets/1')->assertNotFound();
});
