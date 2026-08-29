<?php

namespace App\Http\Requests;

use App\Models\Entity;
use App\Models\RecurringTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Entity|null $entity */
        $entity = $this->attributes->get('active_entity');

        return $entity !== null && $this->user()?->can('create', [RecurringTransaction::class, $entity]) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'next_run_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['nullable', 'date', 'after:next_run_at'],
        ];

        if (in_array($type, ['income', 'expense'], true)) {
            $rules['account_id'] = ['required', 'uuid', 'exists:accounts,id'];
            $rules['category_id'] = ['required', 'uuid', 'exists:categories,id'];
        }

        if ($type === 'transfer') {
            $rules['from_account_id'] = ['required', 'uuid', 'exists:accounts,id', 'different:to_account_id'];
            $rules['to_account_id'] = ['required', 'uuid', 'exists:accounts,id'];
        }

        return $rules;
    }
}
