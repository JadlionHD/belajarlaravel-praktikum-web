<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTicketRequest extends TicketFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'user_id' => ['prohibited'],
            'status' => ['required', Rule::in(['open', 'pending', 'closed'])],
        ]);
    }
}
