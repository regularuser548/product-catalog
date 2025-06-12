<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'price',
        'description',
        'available',
        'stock',
    ];

    /**
     * Get the parameter values for the product.
     *
     */
    public function parameterValues(): BelongsToMany
    {
        return $this->belongsToMany(ParameterValue::class)->withTimestamps();
    }

    /**
     * Get the parameters for the product.
     *
     */
    public function parameters(): HasManyThrough
    {
        return $this->hasManyThrough(Parameter::class, ParameterValue::class);
    }
}
