<?php

namespace App\Http\Requests;

use App\Models\BankImportRule;
use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankImportRuleRequest extends FormRequest
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
        /** @var BankImportRule $rule */
        $rule = $this->route('bankImportRule');

        return [
            'keyword' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bank_import_rules', 'keyword')
                    ->where(fn ($q) => $q->where('entity_id', $entity?->id))
                    ->ignore($rule->id),
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
}
