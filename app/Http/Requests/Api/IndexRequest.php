<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'     => ['sometimes', 'integer', 'min:1'],
            'search'   => ['sometimes', 'string', 'max:255'],
            'sort_by'  => ['sometimes', 'string'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Per page harus berupa angka',
            'per_page.min'     => 'Per page minimal 1',
            'per_page.max'     => 'Per page maksimal 100',
            'page.integer'     => 'Page harus berupa angka',
            'page.min'         => 'Page minimal 1',
            'sort_dir.in'      => 'Sort direction harus asc atau desc',
        ];
    }

    /**
     * Get per page value with default
     */
    public function getPerPage(): int
    {
        return $this->input('per_page', 10);
    }

    /**
     * Get search keyword
     */
    public function getSearch(): ?string
    {
        return $this->input('search');
    }

    /**
     * Get sort by column
     */
    public function getSortBy(): string
    {
        return $this->input('sort_by', 'id');
    }

    /**
     * Get sort direction
     */
    public function getSortDir(): string
    {
        return $this->input('sort_dir', 'desc');
    }
}
