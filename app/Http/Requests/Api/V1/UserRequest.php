<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'username' => 'required|string',
            'email' => 'nullable|email',
            'department_id' => 'required|integer|min:1|max:65535',
            'profession_id' => 'required|integer|min:1|max:65535',
            'object_sid' => 'nullable|string',
            'image_path' => 'nullable|string',
            'edb_id' => 'required|integer',
            'is_active' => 'boolean',
        ];

        return $rules;
    }
}
