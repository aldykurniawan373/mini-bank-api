<?php

namespace App\Models\Api;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
