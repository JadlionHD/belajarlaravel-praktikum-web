<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private TicketService $service) {}

    public function index(): View
    {
        $tickets = Ticket::with(['user', 'category'])
            ->orderByDesc('id')
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        $ticket = new Ticket(['is_urgent' => false]);
        $categories = Category::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('tickets.create', compact('ticket', 'categories', 'users'));
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->service->create($request->validated());

        return redirect()->route('tickets.show', $ticket, 303)
            ->with('success', 'Tiket berhasil dibuat.');
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['user', 'category', 'comments.user']);

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket): View
    {
        $categories = Category::orderBy('name')->get();

        return view('tickets.edit', compact('ticket', 'categories'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket = $this->service->update($ticket, $request->validated());

        return redirect()->route('tickets.show', $ticket, 303)
            ->with('success', 'Tiket berhasil diperbarui.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->service->delete($ticket);

        return redirect()->route('tickets.index', [], 303)
            ->with('success', 'Tiket berhasil dihapus.');
    }
}
