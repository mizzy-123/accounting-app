<?php

namespace App\Http\Requests;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankImportRuleRequest extends FormRequest
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
            'keyword' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bank_import_rules', 'keyword')
                    ->where(fn ($q) => $q->where('entity_id', $entity?->id)),
            ],
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->where('entity_id', $entity?->id),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.required' => 'Keyword wajib diisi.',
            'keyword.unique' => 'Keyword ini sudah ada untuk entity aktif.',
            'category_id.required' => 'Kategori wajib dipilih.',
        ];
    }
}
