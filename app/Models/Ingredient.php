<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Custo unitário mais recente.
     * Caso não exista na base, retorna 0 para evitar erros em relatórios.
     */
    public function getLastCostAttribute(): float
    {
        return (float) ($this->attributes['last_cost'] ?? 0);
    }
}
