<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            // --- [تم التعديل هنا] ---
            // جعل البريد الإلكتروني اختياريًا وغير فريد
            'email' => 'nullable|string|email|max:255',
            // --- [نهاية التعديل] ---
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name,guard_name,api',
        ];
    }
}
