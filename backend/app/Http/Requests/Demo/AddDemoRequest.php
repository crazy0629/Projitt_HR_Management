<?php

namespace App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

class AddDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'       => 'nullable|string|max:100',
            'last_name'        => 'nullable|string|max:100',
            'email'            => 'nullable|email|max:150',
            'contact_code'     => 'nullable|string|max:10',
            'contact_no'       => 'nullable|string|max:20',
            'company'          => 'nullable|string|max:150',
            'company_size'     => 'nullable|string|max:150',
            'industry' => 'nullable|in:Information Technology,Healthcare,Education,Finance & Banking,Manufacturing,Retail & E-Commerce,Real Estate & Construction,Transportation & Logistics,Hospitality & Tourism,Energy & Utilities',
            'how_hear_bout_us' => 'nullable|in:Social,Internet,Friends,Family,NewsPaper',
            'service'   => 'nullable|array',
            'service.*' => 'in:payroll,HR,contractor,vender',
        ];
    }
}
