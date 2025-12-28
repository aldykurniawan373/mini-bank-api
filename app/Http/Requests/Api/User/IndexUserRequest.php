<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\IndexRequest;

class IndexUserRequest extends IndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['sometimes', 'string', 'max:255'],
            'role'   => ['sometimes', 'string'],
        ]);
    }
}
