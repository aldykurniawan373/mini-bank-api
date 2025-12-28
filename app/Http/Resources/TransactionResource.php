<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'transaction_code' => $this->transaction_code,
            'type'              => $this->type,
            'type_label'        => $this->getTypeLabel(),
            'direction'         => $this->direction,
            'direction_label'   => $this->direction == 'in' ? 'Masuk' : 'Keluar',
            'amount'            => $this->amount,
            'account'           => $this->whenLoaded('account', function () {
                return [
                    'id'             => $this->account->id,
                    'account_number' => $this->account->account_number,
                    'customer'       => $this->account->customer ? [
                        'id'   => $this->account->customer->id,
                        'name' => $this->account->customer->name ?? $this->account->customer->full_name,
                    ] : null,
                ];
            }),
            'related_account' => $this->whenLoaded('relatedAccount', function () {
                return $this->relatedAccount ? [
                    'id'             => $this->relatedAccount->id,
                    'account_number' => $this->relatedAccount->account_number,
                    'customer'       => $this->relatedAccount->customer ? [
                        'id'   => $this->relatedAccount->customer->id,
                        'name' => $this->relatedAccount->customer->name ?? $this->relatedAccount->customer->full_name,
                    ] : null,
                ] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get label untuk tipe transaksi
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'deposit'    => 'Setoran',
            'withdrawal' => 'Penarikan',
            'transfer'   => 'Transfer',
            default      => $this->type,
        };
    }
}
