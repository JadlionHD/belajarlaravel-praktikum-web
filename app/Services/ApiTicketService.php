<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApiTicketService
{
    /**
     * @param  array{category_id: int, subject: string, description: string, is_urgent: bool, note: string}  $data
     */
    public function create(User $actor, array $data): Ticket
    {
        Gate::forUser($actor)->authorize('create', Ticket::class);

        return DB::transaction(function () use ($actor, $data) {
            $ticket = Ticket::create([
                'user_id' => $actor->id,
                'category_id' => $data['category_id'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => 'open',
                'is_urgent' => (bool) $data['is_urgent'],
            ]);

            $ticket->comments()->create([
                'user_id' => $actor->id,
                'body' => $data['note'],
            ]);

            return $ticket;
        });
    }

    /**
     * @param  array{category_id: int, subject: string, description: string, status: string, is_urgent: bool, note: string}  $data
     */
    public function update(User $actor, Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($actor, $ticket, $data) {
            $current = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            Gate::forUser($actor)->authorize('update', $current);

            $current->update([
                'category_id' => $data['category_id'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => $data['status'],
                'is_urgent' => (bool) $data['is_urgent'],
            ]);

            $current->comments()->create([
                'user_id' => $actor->id,
                'body' => $data['note'],
            ]);

            return $current;
        });
    }

    public function delete(User $actor, Ticket $ticket): void
    {
        DB::transaction(function () use ($actor, $ticket) {
            $current = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            Gate::forUser($actor)->authorize('delete', $current);

            if ($current->status === 'closed') {
                throw ValidationException::withMessages([
                    'ticket' => 'Tiket closed tidak boleh dihapus.',
                ]);
            }

            $current->delete();
        });
    }
}
