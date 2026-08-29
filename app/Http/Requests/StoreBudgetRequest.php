<?php

namespace App\Http\Requests;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Entity|null $entity */
        $entity = $this->attributes->get('active_entity');

        return [
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->where('entity_id', $entity?->id)->where('type', 'expense'),
                ),
            ],
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'period.regex' => 'Periode harus format YYYY-MM.',
            'amount.min' => 'Jumlah budget harus lebih dari nol.',
        ];
    }
}
