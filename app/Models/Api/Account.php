<?php

namespace App\Models\Api;

use App\Models\Model;

class Account extends Model
{
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
