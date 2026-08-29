<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate check dilakukan di controller
    }

    public function rules(): array
    {
        // entity_id dari active entity (request attribute, bukan input user)
        $entityId = $this->attributes->get('active_entity')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where('entity_id', $entityId),
            ],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::in(['active', 'completed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama project wajib diisi.',
            'client_id.exists' => 'Client tidak ditemukan atau tidak milik entity ini.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
