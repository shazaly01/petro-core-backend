<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'full_name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            // --- [تم التعديل هنا] ---
            // جعل البريد الإلكتروني اختياريًا وغير فريد
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                // لا نحتاج للتحقق من التفرد إذا كان اختياريًا، ولكن من الجيد إبقاؤه إذا تم إدخاله
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            // --- [نهاية التعديل] ---
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name,guard_name,api',
        ];
    }
}
