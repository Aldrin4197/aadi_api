<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'reason' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\AccountDeletion::USER_REASONS))],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $user = $this->user();
            $password = (string) $this->input('password', '');

            if ($password !== '' && Hash::check($password, $user->password)) {
                return;
            }

            // Accounts created through Google or Facebook may never have set a password they know.
            $usesSocialLogin = DB::table('social_accounts')->where('user_id', $user->id)->exists();
            $email = mb_strtolower(trim((string) $this->input('email', '')));
            if ($usesSocialLogin && $email !== '' && hash_equals(mb_strtolower($user->email), $email)) {
                return;
            }

            $validator->errors()->add($usesSocialLogin && $password === '' ? 'email' : 'password', $usesSocialLogin && $password === ''
                ? 'Enter your account email address to confirm.'
                : 'The password is incorrect.');
        }];
    }
}
