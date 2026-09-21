<?php

namespace App\Http\Requests;

class StoreTicketRequest extends TicketFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
            'status' => ['prohibited'],
        ]);
    }
}
