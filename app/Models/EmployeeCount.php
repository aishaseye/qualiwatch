<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCount extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'display_label',
        'min_count',
        'max_count',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'min_count' => 'integer',
        'max_count' => 'integer',
    ];

    // Relations
    public function companies()
    {
        return $this->hasMany(Company::class, 'employee_count_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('min_count');
    }

    // Accessors
    public function getCompaniesCountAttribute()
    {
        return $this->companies()->count();
    }

    public function getRangeDisplayAttribute()
    {
        if ($this->max_count) {
            return $this->min_count . '-' . $this->max_count . ' employés';
        }
        return $this->min_count . '+ employés';
    }

    // Méthodes statiques utiles
    public static function getActiveOptions()
    {
        return static::active()->ordered()->get()->mapWithKeys(function ($count) {
            return [$count->id => $count->name];
        });
    }

    public static function getByCode(string $code)
    {
        return static::where('code', $code)->first();
    }

    public static function findByEmployeeNumber(int $employeeNumber)
    {
        return static::where(function ($query) use ($employeeNumber) {
            $query->where('min_count', '<=', $employeeNumber)
                  ->where(function ($q) use ($employeeNumber) {
                      $q->where('max_count', '>=', $employeeNumber)
                        ->orWhereNull('max_count');
                  });
        })->first();
    }

    // Données par défaut des tranches d'employés
    public static function getDefaultCounts(): array
    {
        return [
            [
                'code' => '1-5',
                'name' => '1 à 5 employés',
                'display_label' => 'Très petite entreprise (1-5 employés)',
                'min_count' => 1,
                'max_count' => 5,
                'color' => '#10B981',
                'icon' => 'user',
                'sort_order' => 1,
            ],
            [
                'code' => '5-15',
                'name' => '5 à 15 employés',
                'display_label' => 'Petite entreprise (5-15 employés)',
                'min_count' => 5,
                'max_count' => 15,
                'color' => '#3B82F6',
                'icon' => 'user-group',
                'sort_order' => 2,
            ],
            [
                'code' => '15-25',
                'name' => '15 à 25 employés',
                'display_label' => 'Entreprise moyenne (15-25 employés)',
                'min_count' => 15,
                'max_count' => 25,
                'color' => '#F59E0B',
                'icon' => 'users',
                'sort_order' => 3,
            ],
            [
                'code' => '25-50',
                'name' => '25 à 50 employés',
                'display_label' => 'Entreprise développée (25-50 employés)',
                'min_count' => 25,
                'max_count' => 50,
                'color' => '#EF4444',
                'icon' => 'user-plus',
                'sort_order' => 4,
            ],
            [
                'code' => '50-100',
                'name' => '50 à 100 employés',
                'display_label' => 'Grande entreprise (50-100 employés)',
                'min_count' => 50,
                'max_count' => 100,
                'color' => '#8B5CF6',
                'icon' => 'building-office',
                'sort_order' => 5,
            ],
            [
                'code' => '100+',
                'name' => '100+ employés',
                'display_label' => 'Très grande entreprise (100+ employés)',
                'min_count' => 100,
                'max_count' => null,
                'color' => '#6366F1',
                'icon' => 'building-office-2',
                'sort_order' => 6,
            ],
        ];
    }
}