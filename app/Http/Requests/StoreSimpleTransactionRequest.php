<?php

namespace App\Http\Requests;

use App\Models\Entity;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSimpleTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Entity|null $entity */
        $entity = $this->attributes->get('active_entity');

        return $entity !== null && $this->user()?->can('create', [Transaction::class, $entity]) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ];

        if (in_array($type, ['income', 'expense'], true)) {
            $rules['account_id'] = ['required', 'uuid', 'exists:accounts,id'];
            $rules['category_id'] = [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where(fn ($q) => $q
                    ->where('entity_id', $this->attributes->get('active_entity')?->id)
                    ->where('type', $type === 'income' ? 'income' : 'expense')),
            ];
        }

        if ($type === 'transfer') {
            $rules['from_account_id'] = ['required', 'uuid', 'exists:accounts,id', 'different:to_account_id'];
            $rules['to_account_id'] = ['required', 'uuid', 'exists:accounts,id'];
        }

        // project_id dan client_id — opsional, hanya untuk entity bisnis
        $entityId = $this->attributes->get('active_entity')?->id;
        $rules['project_id'] = [
            'nullable',
            Rule::exists('projects', 'id')->where('entity_id', $entityId),
        ];
        $rules['client_id'] = [
            'nullable',
            Rule::exists('clients', 'id')->where('entity_id', $entityId),
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Jumlah harus lebih dari nol.',
            'from_account_id.different' => 'Akun sumber dan tujuan harus berbeda.',
        ];
    }
}
