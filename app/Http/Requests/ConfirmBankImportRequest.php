<?php

namespace App\Http\Requests;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfirmBankImportRequest extends FormRequest
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
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'file_name' => ['required', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.date' => ['required', 'date'],
            'rows.*.description' => ['required', 'string', 'max:1000'],
            'rows.*.amount' => ['required', 'numeric', 'min:0.01'],
            'rows.*.type' => ['required', Rule::in(['income', 'expense'])],
            'rows.*.category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->where('entity_id', $entity?->id),
                ),
            ],
            'rows.*.include' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('rows', []) as $index => $row) {
                if (($row['include'] ?? true) !== true) {
                    continue;
                }

                if (empty($row['category_id'])) {
                    $validator->errors()->add(
                        "rows.{$index}.category_id",
                        'Setiap baris yang diimpor wajib punya kategori.',
                    );
                }
            }
        });
    }
}
