<?php

namespace App\Http\Requests;

use App\Models\BankPlan;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum')?->is_verified === true;
    }

    public function rules(): array
    {
        $rules = [
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'bank_plan_id' => ['required', 'integer', 'exists:bank_plans,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'type' => ['required', 'in:supply,demand'],
            'title' => ['required', 'string', 'min:10', 'max:255', 'not_regex:/09\d{9}/'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'loan_amount' => ['required', 'integer', 'min:1'],
            'assignment_price' => ['required', 'integer', 'min:0'],
            'profit_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:360'],
        ];

        if ($this->filled('transfer_price') && ! $this->filled('installment_count')) {
            $rules['installment_count'] = ['nullable', 'integer', 'min:1', 'max:360'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('bank_id') && $this->filled('bank_plan_id') && ! BankPlan::whereKey($this->integer('bank_plan_id'))->where('bank_id', $this->integer('bank_id'))->exists()) {
                $validator->errors()->add('bank_plan_id', 'طرح انتخاب‌شده متعلق به بانک انتخاب‌شده نیست.');
            }

            if ($this->filled('location_id') && ! Location::whereKey($this->integer('location_id'))->whereNotNull('parent_id')->exists()) {
                $validator->errors()->add('location_id', 'موقعیت انتخاب‌شده باید یک شهر باشد.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('transfer_price') && ! $this->has('assignment_price')) {
            $this->merge(['assignment_price' => $this->input('transfer_price')]);
        }
        if ($this->has('interest_rate') && ! $this->has('profit_rate')) {
            $this->merge(['profit_rate' => $this->input('interest_rate')]);
        }
        if ($this->has('transfer_price') && ! $this->has('assignment_price')) {
            $this->merge(['assignment_price' => $this->input('transfer_price')]);
        }
        if ($this->has('transfer_price') && ! $this->has('installment_count')) {
            $this->merge(['installment_count' => 1]);
        }
    }
}
