<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;

class StoreTicketRequest extends TicketInput
{
    public function authorize(): bool
    {
        return Gate::allows('create', Ticket::class);
    }

    public function rules(): array
    {
        return $this->commonRules() + ['status' => ['prohibited']];
    }
}
