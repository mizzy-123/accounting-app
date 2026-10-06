<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->attributes->get('active_entity')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_account_id' => [
                'required',
                'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->where('type', 'asset')),
            ],
            'accumulated_account_id' => [
                'required',
                'uuid',
                'different:asset_account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->where('type', 'asset')),
            ],
            'expense_account_id' => [
                'required',
                'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->where('type', 'expense')),
            ],
            'payment_account_id' => [
                'nullable',
                'uuid',
                'different:asset_account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->whereIn('type', ['asset', 'liability'])),
            ],
            'acquisition_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:1'],
            'residual_value' => ['required', 'numeric', 'min:0', 'lt:cost'],
            'useful_life_months' => ['required', 'integer', 'min:1', 'max:600'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama aset wajib diisi.',
            'asset_account_id.required' => 'Akun aset wajib dipilih.',
            'accumulated_account_id.required' => 'Akun akumulasi penyusutan wajib dipilih.',
            'accumulated_account_id.different' => 'Akun akumulasi harus berbeda dari akun aset.',
            'expense_account_id.required' => 'Akun beban penyusutan wajib dipilih.',
            'acquisition_date.required' => 'Tanggal perolehan wajib diisi.',
            'cost.min' => 'Harga perolehan harus lebih dari 0.',
            'residual_value.lt' => 'Nilai residu harus lebih kecil dari harga perolehan.',
            'useful_life_months.min' => 'Masa manfaat minimal 1 bulan.',
        ];
    }
}
