<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name ?? $this->full_name,
            'nik'        => $this->nik ?? null,
            'phone'      => $this->phone,
            'address'    => $this->address,
            'accounts'   => $this->whenLoaded('accounts', function () {
                return $this->accounts->map(function ($account) {
                    return [
                        'id'             => $account->id,
                        'account_number' => $account->account_number,
                        'balance'        => $account->balance,
                        'created_at'     => $account->created_at?->format('Y-m-d H:i:s'),
                        'updated_at'     => $account->updated_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
