<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                Rule::in(['personal', 'business']),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'personal' && $this->user()?->ownsPersonalEntity()) {
                        $fail('Anda sudah memiliki entity pribadi. Satu akun hanya boleh punya satu entity pribadi.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama entity wajib diisi.',
            'name.max' => 'Nama entity maksimal 255 karakter.',
            'type.required' => 'Tipe entity wajib dipilih.',
            'type.in' => 'Tipe entity tidak valid.',
        ];
    }
}
