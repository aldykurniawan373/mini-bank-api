<?php

namespace App\Http\Requests\Api\Transaction;

use App\Http\Requests\Api\IndexRequest;

class IndexTransactionRequest extends IndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'type'       => ['sometimes', 'in:deposit,withdrawal,transfer'],
            'direction'  => ['sometimes', 'in:in,out'],
            'account_id' => ['sometimes', 'integer', 'exists:accounts,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date'   => ['sometimes', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
