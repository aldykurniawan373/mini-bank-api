<?php

namespace App\Http\Requests\Api\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
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
        $accountId = $this->route('account');

        return [
            'to_account_id' => [
                'required',
                'exists:accounts,id',
                Rule::notIn([$accountId]),
            ],
            'amount'      => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'to_account_id.required' => 'Rekening tujuan wajib diisi',
            'to_account_id.exists'   => 'Rekening tujuan tidak ditemukan',
            'to_account_id.not_in'   => 'Tidak dapat melakukan transfer ke rekening sendiri',
            'amount.required'        => 'Jumlah wajib diisi',
            'amount.integer'         => 'Jumlah harus berupa angka',
            'amount.min'             => 'Jumlah minimal 1',
            'description.max'        => 'Keterangan maksimal 255 karakter',
        ];
    }
}
