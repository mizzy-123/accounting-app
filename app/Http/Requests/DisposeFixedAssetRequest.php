<?php

namespace App\Http\Requests;

use App\Models\FixedAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisposeFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->attributes->get('active_entity')?->id;
        $hasProceeds = (float) $this->input('proceeds', 0) > 0;
        $asset = $this->route('fixedAsset');
        $minDate = $asset instanceof FixedAsset
            ? $asset->acquisition_date->toDateString()
            : null;

        return [
            'date' => array_values(array_filter([
                'required',
                'date',
                $minDate ? 'after_or_equal:'.$minDate : null,
            ])),
            'proceeds' => ['required', 'numeric', 'min:0'],
            'proceeds_account_id' => [
                Rule::requiredIf($hasProceeds),
                'nullable',
                'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->whereIn('type', ['asset', 'liability'])),
            ],
            'gain_account_id' => [
                'required',
                'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->where('type', 'revenue')),
            ],
            'loss_account_id' => [
                'required',
                'uuid',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('entity_id', $entityId)->where('type', 'expense')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal pelepasan wajib diisi.',
            'proceeds.required' => 'Nilai penjualan wajib diisi (isi 0 jika dihapusbukukan).',
            'proceeds_account_id.required' => 'Akun penerimaan hasil penjualan wajib dipilih.',
            'gain_account_id.required' => 'Akun keuntungan pelepasan wajib dipilih.',
            'loss_account_id.required' => 'Akun kerugian pelepasan wajib dipilih.',
        ];
    }
}
