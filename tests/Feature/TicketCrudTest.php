<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    /** TC01: Create valid */
    public function test_tc01_create_valid(): void
    {
        $payload = [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Printer Rusak di Lantai 2',
            'description' => 'Printer tidak bisa menarik kertas sejak pagi hari.',
            'is_urgent' => '1',
            'note' => 'Segera ditindaklanjuti oleh teknisi.',
        ];

        $response = $this->post(route('tickets.store'), $payload);

        $response->assertStatus(303);
        $ticket = Ticket::where('subject', 'Printer Rusak di Lantai 2')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame('open', $ticket->status);
        $this->assertTrue($ticket->is_urgent);
        $this->assertCount(1, $ticket->comments);
        $this->assertSame('Segera ditindaklanjuti oleh teknisi.', $ticket->comments->first()->body);
    }

    /** TC02: Input kosong */
    public function test_tc02_empty_inputs_rejected(): void
    {
        $countBefore = Ticket::count();

        $response = $this->post(route('tickets.store'), [
            'user_id' => '',
            'category_id' => '',
            'subject' => '',
            'description' => '',
            'is_urgent' => '',
            'note' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['user_id', 'category_id', 'subject', 'description', 'is_urgent', 'note']);
        $this->assertDatabaseCount('tickets', $countBefore);
    }

    /** TC03: Spasi saja */
    public function test_tc03_whitespace_only_subject_rejected(): void
    {
        $countBefore = Ticket::count();

        $response = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => '   ',
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['subject']);
        $this->assertDatabaseCount('tickets', $countBefore);
    }

    /** TC04: Batas subject (149, 150, 151) */
    public function test_tc04_subject_boundaries(): void
    {
        // 149 karakter - lolos
        $res149 = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => str_repeat('A', 149),
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $res149->assertStatus(303);

        // 150 karakter - lolos
        $res150 = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => str_repeat('B', 150),
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $res150->assertStatus(303);

        // 151 karakter - gagal
        $res151 = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => str_repeat('C', 151),
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $res151->assertStatus(302);
        $res151->assertSessionHasErrors(['subject']);
    }

    /** TC05: Batas description (5000, 5001) & note (1000, 1001) */
    public function test_tc05_description_and_note_boundaries(): void
    {
        // 5000 description & 1000 note - lolos
        $resPass = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Valid',
            'description' => str_repeat('D', 5000),
            'is_urgent' => '0',
            'note' => str_repeat('N', 1000),
        ]);
        $resPass->assertStatus(303);

        // 5001 description - gagal
        $resDescFail = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Valid 2',
            'description' => str_repeat('D', 5001),
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $resDescFail->assertStatus(302);
        $resDescFail->assertSessionHasErrors(['description']);

        // 1001 note - gagal
        $resNoteFail = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Valid 3',
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => str_repeat('N', 1001),
        ]);
        $resNoteFail->assertStatus(302);
        $resNoteFail->assertSessionHasErrors(['note']);
    }

    /** TC06: Identifier relasi tidak sah */
    public function test_tc06_invalid_relation_identifiers(): void
    {
        // category_id tidak ada
        $res1 = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => 999999,
            'subject' => 'Subjek Valid',
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $res1->assertStatus(302);
        $res1->assertSessionHasErrors(['category_id']);

        // category_id string
        $res2 = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => 'abc',
            'subject' => 'Subjek Valid',
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $res2->assertStatus(302);
        $res2->assertSessionHasErrors(['category_id']);

        // user_id tidak ada
        $res3 = $this->post(route('tickets.store'), [
            'user_id' => 999999,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Valid',
            'description' => 'Deskripsi valid',
            'is_urgent' => '0',
            'note' => 'Catatan valid',
        ]);
        $res3->assertStatus(302);
        $res3->assertSessionHasErrors(['user_id']);
    }

    /** TC07: Identifier route tidak sah */
    public function test_tc07_invalid_route_identifiers(): void
    {
        // /tickets/abc -> 404 karena pattern regex [0-9]+
        $this->get('/tickets/abc')->assertStatus(404);

        // /tickets/999999 -> 404 karena record tidak ditemukan
        $this->get('/tickets/999999')->assertStatus(404);
        $this->get('/tickets/999999/edit')->assertStatus(404);
        $this->put('/tickets/999999', [])->assertStatus(404);
        $this->delete('/tickets/999999')->assertStatus(404);
    }

    /** TC08: Update valid */
    public function test_tc08_update_valid(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Lama',
            'status' => 'open',
        ]);

        $payload = [
            'category_id' => $this->category->id,
            'subject' => 'Subjek Diperbarui',
            'description' => 'Deskripsi baru lengkap',
            'status' => 'pending',
            'is_urgent' => '1',
            'note' => 'Catatan perubahan status ke pending',
        ];

        $response = $this->put(route('tickets.update', $ticket), $payload);

        $response->assertStatus(303);
        $response->assertRedirect(route('tickets.show', $ticket));

        $fresh = $ticket->fresh();
        $this->assertSame('Subjek Diperbarui', $fresh->subject);
        $this->assertSame('pending', $fresh->status);
        $this->assertSame($this->user->id, $fresh->user_id); // Pemilik tetap
        $this->assertCount(1, $fresh->comments);
        $this->assertSame('Catatan perubahan status ke pending', $fresh->comments->first()->body);
    }

    /** TC09: Update invalid */
    public function test_tc09_update_invalid(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Lama',
            'status' => 'open',
        ]);

        // Subject > 150
        $resSubject = $this->put(route('tickets.update', $ticket), [
            'category_id' => $this->category->id,
            'subject' => str_repeat('X', 151),
            'description' => 'Deskripsi',
            'status' => 'open',
            'is_urgent' => '0',
            'note' => 'Catatan',
        ]);
        $resSubject->assertStatus(302);
        $resSubject->assertSessionHasErrors(['subject']);
        $this->assertSame('Subjek Lama', $ticket->fresh()->subject);

        // Status invalid
        $resStatus = $this->put(route('tickets.update', $ticket), [
            'category_id' => $this->category->id,
            'subject' => 'Subjek Baru',
            'description' => 'Deskripsi',
            'status' => 'unknown_status',
            'is_urgent' => '0',
            'note' => 'Catatan',
        ]);
        $resStatus->assertStatus(302);
        $resStatus->assertSessionHasErrors(['status']);
        $this->assertSame('open', $ticket->fresh()->status);
    }

    /** TC10: Manipulasi field terlarang */
    public function test_tc10_prohibited_fields_manipulation(): void
    {
        // Kirim status pada create -> prohibited
        $resCreate = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Valid',
            'description' => 'Deskripsi valid',
            'status' => 'closed',
            'is_urgent' => '0',
            'note' => 'Catatan',
        ]);
        $resCreate->assertStatus(302);
        $resCreate->assertSessionHasErrors(['status']);

        // Kirim user_id pada update -> prohibited
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'open',
        ]);
        $otherUser = User::factory()->create();

        $resUpdate = $this->put(route('tickets.update', $ticket), [
            'user_id' => $otherUser->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Update',
            'description' => 'Deskripsi update',
            'status' => 'open',
            'is_urgent' => '0',
            'note' => 'Catatan update',
        ]);
        $resUpdate->assertStatus(302);
        $resUpdate->assertSessionHasErrors(['user_id']);
        $this->assertSame($this->user->id, $ticket->fresh()->user_id);
    }

    /** TC11: Urgensi boolean vs invalid string */
    public function test_tc11_urgency_values(): void
    {
        // String 'abc' ditolak rule boolean
        $resInvalid = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Valid',
            'description' => 'Deskripsi valid',
            'is_urgent' => 'abc',
            'note' => 'Catatan',
        ]);
        $resInvalid->assertStatus(302);
        $resInvalid->assertSessionHasErrors(['is_urgent']);

        // Boolean 0 / 1 tersimpan benar
        $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Subjek Urgent True',
            'description' => 'Deskripsi valid',
            'is_urgent' => '1',
            'note' => 'Catatan',
        ]);
        $tTrue = Ticket::where('subject', 'Subjek Urgent True')->first();
        $this->assertTrue($tTrue->is_urgent);
    }

    /** TC12: Delete sukses pada status open/pending */
    public function test_tc12_delete_success_on_open_or_pending(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'open',
        ]);
        $comment = Comment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('tickets.destroy', $ticket));

        $response->assertStatus(303);
        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]); // cascade delete
    }

    /** TC13: Delete ditolak pada status closed */
    public function test_tc13_delete_rejected_on_closed_status(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'closed',
        ]);
        $comment = Comment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('tickets.destroy', $ticket));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['ticket']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    /** TC15: PRG (Post-Redirect-Get) */
    public function test_tc15_post_redirect_get_flow(): void
    {
        $response = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => 'Tiket PRG Test',
            'description' => 'Deskripsi PRG',
            'is_urgent' => '0',
            'note' => 'Catatan PRG',
        ]);

        $response->assertStatus(303);
        $ticket = Ticket::where('subject', 'Tiket PRG Test')->first();
        $targetUrl = route('tickets.show', $ticket);
        $response->assertRedirect($targetUrl);

        // Mengikuti GET redirect
        $getResponse = $this->get($targetUrl);
        $getResponse->assertStatus(200);
        $getResponse->assertSee('Tiket berhasil dibuat.');

        // Refresh GET berikutnya tidak menambah komentar atau tiket
        $this->get($targetUrl)->assertStatus(200);
        $this->assertSame(1, $ticket->comments()->count());
    }

    /** TC16: Escaping HTML output (XSS prevention) */
    public function test_tc16_escaping_output(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => '<b>Uji XSS Subject</b>',
            'description' => '<script>alert("xss")</script>',
        ]);

        $response = $this->get(route('tickets.show', $ticket));
        $response->assertStatus(200);
        // Memastikan HTML diescape, bukan dieksekusi sebagai HTML tag mentah
        $response->assertSee('&lt;b&gt;Uji XSS Subject&lt;/b&gt;', false);
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
        $response->assertDontSee('<b>Uji XSS Subject</b>', false);
    }

    /** TC18: Tipe data salah (array pada subject) */
    public function test_tc18_array_type_for_string_field(): void
    {
        $response = $this->post(route('tickets.store'), [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subject' => ['nested' => 'array_value'],
            'description' => 'Deskripsi',
            'is_urgent' => '0',
            'note' => 'Catatan',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['subject']);
    }
}
