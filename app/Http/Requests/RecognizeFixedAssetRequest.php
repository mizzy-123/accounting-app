<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecognizeFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->attributes->get('active_entity')?->id;

        return [
            'payment_account_id' => [
                'required',
                'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->whereIn('type', ['asset', 'liability', 'equity'])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_account_id.required' => 'Akun lawan jurnal wajib dipilih.',
        ];
    }
}
