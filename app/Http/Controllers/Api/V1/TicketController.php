<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\ApiTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function __construct(private readonly ApiTicketService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $tickets = Ticket::query()->where('user_id', $request->user()->id)
            ->with(['user', 'category'])->orderByDesc('id')
            ->paginate($data['per_page'] ?? 10)
            ->appends($request->only('per_page'));

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->service->create($request->user(), $request->validated());

        return (new TicketResource($ticket->load(['user', 'category'])))
            ->response()->setStatusCode(201)
            ->header('Location', route('api.v1.tickets.show', $ticket));
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return new TicketResource($ticket->load(['user', 'category']));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->service->update(
            $request->user(), $ticket, $request->validated()
        );

        return new TicketResource($ticket->load(['user', 'category']));
    }

    public function destroy(Request $request, Ticket $ticket): Response
    {
        Gate::authorize('delete', $ticket);
        $this->service->delete($request->user(), $ticket);

        return response()->noContent();
    }
}
