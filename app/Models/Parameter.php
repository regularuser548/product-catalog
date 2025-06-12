<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Parameter extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the parameter values for the parameter.
     *
     */
    public function parameterValues(): HasMany
    {
        return $this->hasMany(ParameterValue::class);
    }

    /**
     * Get the products for the parameter.
     *
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, ParameterValue::class);
    }
}
