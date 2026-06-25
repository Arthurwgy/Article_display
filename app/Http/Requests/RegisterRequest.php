<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:users,name',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '请填写昵称',
            'name.max' => '昵称最多50个字符',
            'name.unique' => '该昵称已被使用',
            'email.required' => '请填写邮箱',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '该邮箱已被注册',
            'password.required' => '请填写密码',
            'password.min' => '密码至少6位',
            'password.confirmed' => '两次密码输入不一致',
        ];
    }
}
