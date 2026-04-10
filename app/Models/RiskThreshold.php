<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiskThreshold extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'parameter_name',
        'low_min',
        'low_max',
        'medium_min',
        'medium_max',
        'high_min',
        'high_max',
        'weight',
        'description',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'low_min' => 'decimal:3',
            'low_max' => 'decimal:3',
            'medium_min' => 'decimal:3',
            'medium_max' => 'decimal:3',
            'high_min' => 'decimal:3',
            'high_max' => 'decimal:3',
            'weight' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }
}
