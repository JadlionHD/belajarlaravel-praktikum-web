<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends TicketInput
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return $this->commonRules() + [
            'status' => ['required', Rule::in(['open', 'pending', 'closed'])],
        ];
    }
}
