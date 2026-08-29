<?php

namespace App\Http\Requests;

use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'project_id' => [
                'required',
                'uuid',
                Rule::exists('projects', 'id')->where(
                    fn ($q) => $q->where('entity_id', $entity?->id),
                ),
            ],
            'client_id' => [
                'nullable',
                'uuid',
                Rule::exists('clients', 'id')->where(
                    fn ($q) => $q->where('entity_id', $entity?->id),
                ),
            ],
            'issued_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Project wajib dipilih.',
            'items.required' => 'Minimal satu item invoice.',
            'items.min' => 'Minimal satu item invoice.',
            'items.*.name.required' => 'Nama item wajib diisi.',
            'items.*.qty.min' => 'Qty harus lebih dari nol.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal terbit.',
        ];
    }
}
