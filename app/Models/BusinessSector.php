<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSector extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relations
    public function companies()
    {
        return $this->hasMany(Company::class, 'business_sector_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getCompaniesCountAttribute()
    {
        return $this->companies()->count();
    }

    // Méthodes statiques utiles
    public static function getActiveOptions()
    {
        return static::active()->ordered()->get()->mapWithKeys(function ($sector) {
            return [$sector->id => $sector->name];
        });
    }

    public static function getByCode(string $code)
    {
        return static::where('code', $code)->first();
    }

    // Données par défaut des secteurs
    public static function getDefaultSectors(): array
    {
        return [
            [
                'code' => 'restauration',
                'name' => 'Restauration',
                'description' => 'Restaurants, brasseries, cafés, traiteurs',
                'color' => '#EF4444',
                'icon' => 'utensils',
                'sort_order' => 1,
            ],
            [
                'code' => 'hotellerie',
                'name' => 'Hôtellerie',
                'description' => 'Hôtels, chambres d\'hôtes, résidences de tourisme',
                'color' => '#3B82F6',
                'icon' => 'bed',
                'sort_order' => 2,
            ],
            [
                'code' => 'commerce_retail',
                'name' => 'Commerce / Retail',
                'description' => 'Magasins, boutiques, centres commerciaux',
                'color' => '#10B981',
                'icon' => 'shopping-bag',
                'sort_order' => 3,
            ],
            [
                'code' => 'services_sante',
                'name' => 'Services de santé',
                'description' => 'Cabinets médicaux, pharmacies, cliniques',
                'color' => '#F59E0B',
                'icon' => 'heart',
                'sort_order' => 4,
            ],
            [
                'code' => 'services_financiers',
                'name' => 'Services financiers',
                'description' => 'Banques, assurances, conseils financiers',
                'color' => '#8B5CF6',
                'icon' => 'credit-card',
                'sort_order' => 5,
            ],
            [
                'code' => 'education',
                'name' => 'Éducation',
                'description' => 'Écoles, universités, centres de formation',
                'color' => '#06B6D4',
                'icon' => 'academic-cap',
                'sort_order' => 6,
            ],
            [
                'code' => 'transport_logistique',
                'name' => 'Transport / Logistique',
                'description' => 'Transport de personnes, livraisons, logistique',
                'color' => '#84CC16',
                'icon' => 'truck',
                'sort_order' => 7,
            ],
            [
                'code' => 'immobilier',
                'name' => 'Immobilier',
                'description' => 'Agences immobilières, syndics, promoteurs',
                'color' => '#F97316',
                'icon' => 'home',
                'sort_order' => 8,
            ],
            [
                'code' => 'technologie',
                'name' => 'Technologie',
                'description' => 'IT, télécoms, services numériques',
                'color' => '#6366F1',
                'icon' => 'computer-desktop',
                'sort_order' => 9,
            ],
            [
                'code' => 'industrie_manufacturing',
                'name' => 'Industrie / Manufacturing',
                'description' => 'Usines, production industrielle, BTP',
                'color' => '#64748B',
                'icon' => 'cog',
                'sort_order' => 10,
            ],
            [
                'code' => 'services_publics',
                'name' => 'Services publics',
                'description' => 'Administration, mairies, organismes publics',
                'color' => '#DC2626',
                'icon' => 'building-library',
                'sort_order' => 11,
            ],
            [
                'code' => 'tourisme_loisirs',
                'name' => 'Tourisme / Loisirs',
                'description' => 'Agences de voyage, parcs, activités touristiques',
                'color' => '#059669',
                'icon' => 'camera',
                'sort_order' => 12,
            ],
            [
                'code' => 'sport_fitness',
                'name' => 'Sport / Fitness',
                'description' => 'Salles de sport, clubs sportifs, coaching',
                'color' => '#DC2626',
                'icon' => 'lightning-bolt',
                'sort_order' => 13,
            ],
            [
                'code' => 'beaute_bien_etre',
                'name' => 'Beauté / Bien-être',
                'description' => 'Salons de coiffure, spas, instituts de beauté',
                'color' => '#EC4899',
                'icon' => 'sparkles',
                'sort_order' => 14,
            ],
            [
                'code' => 'services_domicile',
                'name' => 'Services à domicile',
                'description' => 'Ménage, jardinage, bricolage, aide à la personne',
                'color' => '#16A34A',
                'icon' => 'wrench-screwdriver',
                'sort_order' => 15,
            ],
            [
                'code' => 'conseils_consulting',
                'name' => 'Conseils / Consulting',
                'description' => 'Cabinets de conseil, expertise comptable, juridique',
                'color' => '#0891B2',
                'icon' => 'briefcase',
                'sort_order' => 16,
            ],
            [
                'code' => 'artisanat',
                'name' => 'Artisanat',
                'description' => 'Artisans, métiers d\'art, production artisanale',
                'color' => '#B45309',
                'icon' => 'hammer',
                'sort_order' => 17,
            ],
            [
                'code' => 'agriculture',
                'name' => 'Agriculture',
                'description' => 'Exploitations agricoles, élevage, viticulture',
                'color' => '#65A30D',
                'icon' => 'leaf',
                'sort_order' => 18,
            ],
            [
                'code' => 'energie',
                'name' => 'Énergie',
                'description' => 'Production énergétique, énergies renouvelables',
                'color' => '#FCD34D',
                'icon' => 'bolt',
                'sort_order' => 19,
            ],
            [
                'code' => 'telecommunications',
                'name' => 'Télécommunications',
                'description' => 'Opérateurs télécoms, services de communication',
                'color' => '#7C3AED',
                'icon' => 'signal',
                'sort_order' => 20,
            ],
            [
                'code' => 'media_communication',
                'name' => 'Média / Communication',
                'description' => 'Médias, publicité, marketing, communication',
                'color' => '#E11D48',
                'icon' => 'megaphone',
                'sort_order' => 21,
            ],
            [
                'code' => 'autres',
                'name' => 'Autres',
                'description' => 'Autres secteurs non listés',
                'color' => '#6B7280',
                'icon' => 'ellipsis-horizontal-circle',
                'sort_order' => 999,
            ],
        ];
    }
}