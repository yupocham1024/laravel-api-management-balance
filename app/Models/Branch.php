<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{

    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'prefecture'];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
