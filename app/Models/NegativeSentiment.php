<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NegativeSentiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Méthodes statiques utiles
    public static function getDefault()
    {
        return static::default()->first();
    }

    public static function getAllOrdered()
    {
        return static::ordered()->get();
    }

    public static function getForSelect()
    {
        return static::ordered()->get()->map(function ($sentiment) {
            return [
                'id' => $sentiment->id,
                'name' => $sentiment->name,
                'label' => $sentiment->label,
                'is_default' => $sentiment->is_default,
            ];
        });
    }
}