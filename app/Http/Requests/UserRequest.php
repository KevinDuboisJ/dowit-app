<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Exception;

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
        $rules = [
            'firstname' => 'required|string',  // Required for both create and update
            'lastname' => 'required|string',   // Required for both create and update
            'username' => 'required|string|unique:users,username',
            'email' => 'nullable|email',
            'department_id' => 'required|integer|min:1|max:65535',
            'profession_id' => 'required|integer|min:1|max:65535',
            'object_sid' => 'nullable|string',
            'image_path' => 'nullable|string',
            'edb_id' => 'string',
            'is_active' => 'boolean',
        ];

        // Check if we are updating by checking the route parameter
        if (request()->routeIs('users.edbid.update')) {
            $rules['username'] = 'required|string|unique:users,username,' .  $this->get("edb_id") . ',edb_id';
        }

        return $rules;
    }
}
