<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ProfileFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable','string','max:40'],
            'bio' => ['nullable','string','max:1000'],
            'city' => ['required','string','max:255'],
            'address' => ['required','string','max:255'],
            'birth_date' => ProfileFields::birthDateRules(),
            'sex_assigned_at_birth' => ['required', Rule::in(ProfileFields::SEX_OPTIONS)],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
