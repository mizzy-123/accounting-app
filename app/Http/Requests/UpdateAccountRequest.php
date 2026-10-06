<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Account $account */
        $account = $this->route('account');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'name')
                    ->where(fn ($query) => $query->where('entity_id', $account->entity_id))
                    ->ignore($account->id),
            ],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'is_active' => ['sometimes', 'boolean'],
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
