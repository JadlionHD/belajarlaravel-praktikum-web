<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * Create a new ticket along with an initial comment within a database transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Ticket
    {
        return DB::transaction(function () use ($data): Ticket {
            $ticket = Ticket::create([
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => 'open',
                'is_urgent' => (bool) $data['is_urgent'],
            ]);

            $ticket->comments()->create([
                'user_id' => $ticket->user_id,
                'body' => $data['note'],
            ]);

            return $ticket;
        });
    }

    /**
     * Update an existing ticket and record a change note comment within a database transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data): Ticket {
            $current = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            $current->update([
                'category_id' => $data['category_id'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => $data['status'],
                'is_urgent' => (bool) $data['is_urgent'],
            ]);

            $current->comments()->create([
                'user_id' => $current->user_id,
                'body' => $data['note'],
            ]);

            return $current;
        });
    }

    /**
     * Delete a ticket after confirming it is not closed within a database transaction.
     */
    public function delete(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket): void {
            $current = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($current->status === 'closed') {
                throw ValidationException::withMessages([
                    'ticket' => 'Tiket closed tidak boleh dihapus.',
                ]);
            }

            $current->delete(); // FK cascade menghapus comments.
        });
    }
}
