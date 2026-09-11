<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitUserVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('sanctum');
    }

    public function rules(): array
    {
        $fileRule = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'];

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'national_code' => [
                'required',
                'digits:10',
                Rule::unique('users', 'national_code')->ignore($this->user('sanctum')?->id),
            ],
            'national_card_serial' => ['required', 'string', 'max:80'],
            'home_phone' => ['required', 'regex:/^0[1-9][0-9]{9}$/'],
            'postal_address' => ['required', 'string', 'min:10', 'max:1000'],
            'residence_document' => $fileRule,
            'iban' => ['required', 'regex:/^IR[0-9]{24}$/i'],
            'job_document' => $fileRule,
            'national_card_front' => $fileRule,
            'national_card_back' => $fileRule,
            'birth_certificate_p1' => $fileRule,
            'birth_certificate_p2' => $fileRule,
            'ownership_confirmed' => ['required', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_code' => $this->normalizeDigits($this->input('national_code')),
            'home_phone' => $this->normalizeDigits($this->input('home_phone')),
            'iban' => strtoupper((string) $this->input('iban')),
        ]);
    }

    private function normalizeDigits(?string $value): ?string
    {
        if ($value === null) return null;
        return strtr($value, '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', '01234567890123456789');
    }
}
