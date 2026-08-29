<?php

namespace App\Http\Requests;

use App\Models\Entity;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInterEntityTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createInterEntityTransfer', Transaction::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Entity|null $activeEntity */
        $activeEntity = $this->attributes->get('active_entity');

        return [
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'from_account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'to_entity_id' => [
                'required',
                'uuid',
                Rule::exists('entities', 'id')->where(fn ($q) => $q->where('id', '!=', $activeEntity?->id)),
            ],
            'to_account_id' => ['required', 'uuid', 'exists:accounts,id'],
        ];
    }
}
