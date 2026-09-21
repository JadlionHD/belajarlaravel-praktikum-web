<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TicketTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_update_roll_back_when_comment_fails(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $existing = Ticket::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Subjek awal',
            'status' => 'open',
        ]);
        $data = [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'subject' => 'Subjek baru',
            'description' => 'Deskripsi pengujian',
            'status' => 'pending',
            'is_urgent' => false,
            'note' => 'Catatan uji',
        ];
        $ticketCount = Ticket::count();
        $commentCount = Comment::count();
        $dispatcher = Comment::getEventDispatcher();
        Comment::setEventDispatcher(clone $dispatcher);

        try {
            Comment::creating(function (Comment $comment): void {
                throw new RuntimeException('Simulasi gagal komentar');
            });
            $service = app(TicketService::class);
            foreach (['create', 'update'] as $operation) {
                try {
                    if ($operation === 'create') {
                        $service->create($data);
                    } else {
                        $service->update($existing, $data);
                    }
                    $this->fail('Exception simulasi tidak terjadi.');
                } catch (RuntimeException $e) {
                    $this->assertSame('Simulasi gagal komentar', $e->getMessage());
                }
                $this->assertDatabaseCount('tickets', $ticketCount);
                $this->assertDatabaseCount('comments', $commentCount);
                $this->assertSame('Subjek awal', $existing->fresh()->subject);
                $this->assertSame('open', $existing->fresh()->status);
            }
        } finally {
            Comment::setEventDispatcher($dispatcher);
        }
    }
}
