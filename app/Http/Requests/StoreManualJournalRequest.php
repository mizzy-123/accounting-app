<?php

namespace App\Http\Requests;

use App\Models\Entity;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreManualJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Entity|null $entity */
        $entity = $this->attributes->get('active_entity');

        return $entity !== null && $this->user()?->can('createAdjustment', [Transaction::class, $entity]) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.kredit' => ['nullable', 'numeric', 'min:0'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $entries = $this->input('entries', []);

            foreach ($entries as $index => $entry) {
                $debit = (float) ($entry['debit'] ?? 0);
                $kredit = (float) ($entry['kredit'] ?? 0);

                if (($debit > 0 && $kredit > 0) || ($debit <= 0 && $kredit <= 0)) {
                    $validator->errors()->add(
                        "entries.{$index}",
                        'Setiap baris harus memiliki debit atau kredit saja.',
                    );
                }
            }

            $totalDebit = collect($entries)->sum(fn (array $entry) => (float) ($entry['debit'] ?? 0));
            $totalKredit = collect($entries)->sum(fn (array $entry) => (float) ($entry['kredit'] ?? 0));

            if (round($totalDebit, 2) !== round($totalKredit, 2)) {
                $validator->errors()->add('entries', 'Total debit harus sama dengan total kredit.');
            }
        });
    }
}
