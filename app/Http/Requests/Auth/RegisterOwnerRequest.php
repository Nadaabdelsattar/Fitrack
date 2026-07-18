<?php
declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class RegisterOwnerRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gym_name' => ['required', 'string', 'max:255'],
            'gym_email' => ['required', 'string', 'email', 'max:255', 'unique:gyms,email'],
            'gym_phone' => ['required', 'string', 'max:50'],
            'gym_address' => ['required', 'string', 'max:500'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'gym_name.required' => 'Gym name is required.',
            'gym_email.required' => 'Gym email is required.',
            'owner_email.required' => 'Owner email is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
