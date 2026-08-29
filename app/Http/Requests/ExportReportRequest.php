<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportReportRequest extends FormRequest
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
        $type = $this->string('type')->toString();

        return [
            'type' => [
                'required',
                Rule::in(['cash_flow', 'profit_loss', 'balance_sheet', 'project_profitability', 'budget']),
            ],
            'format' => ['required', Rule::in(['pdf', 'excel'])],
            'start' => [
                Rule::requiredIf(in_array($type, ['cash_flow', 'profit_loss', 'project_profitability'], true)),
                'nullable',
                'date',
            ],
            'end' => [
                Rule::requiredIf(in_array($type, ['cash_flow', 'profit_loss', 'project_profitability'], true)),
                'nullable',
                'date',
                'after_or_equal:start',
            ],
            'as_of' => [
                Rule::requiredIf($type === 'balance_sheet'),
                'nullable',
                'date',
            ],
            'period' => [
                Rule::requiredIf($type === 'budget'),
                'nullable',
                'regex:/^\d{4}-\d{2}$/',
            ],
        ];
    }
}
