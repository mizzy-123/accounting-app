<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->attributes->get('active_entity')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'name')->where(fn ($query) => $query->where('entity_id', $entityId)),
            ],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama akun wajib diisi.',
            'name.unique' => 'Nama akun sudah dipakai di entity ini.',
            'type.required' => 'Tipe akun wajib dipilih.',
            'type.in' => 'Tipe akun tidak valid.',
        ];
    }
}
