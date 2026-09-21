<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class TicketFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Khusus demo lokal, belum memakai policy.
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['subject', 'description', 'note'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $normalized[$field] = trim($value);
            }
        }
        $this->merge($normalized);
    }

    protected function commonRules(): array
    {
        return [
            'subject' => ['bail', 'required', 'string', 'max:150'],
            'description' => ['bail', 'required', 'string', 'max:5000'],
            'category_id' => ['bail', 'required', 'integer', 'exists:categories,id'],
            'is_urgent' => ['required', 'boolean'],
            'note' => ['bail', 'required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
            'integer' => ':attribute harus berupa identifier bilangan bulat.',
            'exists' => ':attribute tidak ditemukan.',
            'boolean' => ':attribute harus bernilai 0 atau 1.',
            'in' => ':attribute tidak termasuk pilihan yang diizinkan.',
            'prohibited' => ':attribute tidak boleh dikirim pada operasi ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'Subjek',
            'description' => 'Deskripsi',
            'category_id' => 'Kategori',
            'user_id' => 'Pemilik',
            'is_urgent' => 'Urgensi',
            'status' => 'Status',
            'note' => 'Catatan',
        ];
    }
}
