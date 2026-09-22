<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class TicketInput extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['subject', 'description', 'note'] as $field) {
            if (is_string($this->input($field))) {
                $values[$field] = trim($this->input($field));
            }
        }
        $this->merge($values);
    }

    protected function commonRules(): array
    {
        return [
            'subject' => ['bail', 'required', 'string', 'max:150'],
            'description' => ['bail', 'required', 'string', 'max:5000'],
            'category_id' => ['bail', 'required', 'integer', 'exists:categories,id'],
            'is_urgent' => ['required', 'boolean'],
            'note' => ['bail', 'required', 'string', 'max:1000'],
            'user_id' => ['prohibited'],
            'is_admin' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
            'exists' => ':attribute tidak ditemukan.',
            'prohibited' => ':attribute tidak boleh dikirim.',
        ];
    }
}
