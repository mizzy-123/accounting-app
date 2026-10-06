<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostDepreciationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'through' => ['required', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'through.required' => 'Periode penyusutan wajib diisi.',
            'through.date_format' => 'Periode harus berformat YYYY-MM.',
        ];
    }
}
