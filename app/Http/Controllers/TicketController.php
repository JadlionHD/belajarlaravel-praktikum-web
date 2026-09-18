<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TicketController extends Controller
{
    private function tickets(): array
    {
        return [
            1 => ['id' => 1, 'subject' => 'Tidak dapat login', 'status' => 'open'],
            2 => ['id' => 2, 'subject' => 'Pembayaran belum tercatat', 'status' => 'pending'],
            3 => ['id' => 3, 'subject' => 'Permintaan perubahan profil', 'status' => 'closed'],
        ];
    }

    public function index(): View
    {
        $tickets = Ticket::with(['user', 'category'])
            ->orderByDesc('id')
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    public function show(int $ticket): View
    {
        $item = $this->findTicket($ticket);

        return view('tickets.show', ['ticket' => $item]);
    }

    public function showJson(Request $request, int $ticket): JsonResponse
    {
        Log::info('Ticket JSON requested', [
            'ticket_id' => $ticket,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        return response()->json(['data' => $this->findTicket($ticket)]);
    }

    private function findTicket(int $ticket): array
    {
        $item = $this->tickets()[$ticket] ?? null;
        abort_if($item === null, 404, 'Ticket tidak ditemukan');

        return $item;
    }
}
