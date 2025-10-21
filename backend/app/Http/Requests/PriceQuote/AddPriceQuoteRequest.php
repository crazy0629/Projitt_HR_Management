<?php

namespace App\Http\Requests\PriceQuote;

use Illuminate\Foundation\Http\FormRequest;

class AddPriceQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'      => 'nullable|string|max:100',
            'last_name'       => 'nullable|string|max:100',
            'contact_code'    => 'nullable|string|max:10',
            'contact_no'      => 'nullable|string|max:20',
            'company_name'    => 'nullable|string|max:150',
            'no_of_employee'  => 'nullable',
            'email'           => 'nullable|email|max:150',
            'contact_email'   => 'nullable|email|max:150',
            'service'   => 'nullable|array',
            'service.*' => 'in:payroll,HR,contractor,vender',
        ];
    }
}
