<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sentiment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description'
    ];
    
    // Relations
    public function feedbackTypes()
    {
        return $this->belongsToMany(FeedbackType::class, 'feedback_type_sentiments')
                    ->withPivot('is_default', 'sort_order')
                    ->withTimestamps();
    }
    
    /**
     * Récupérer tous les sentiments par type de feedback
     */
    public static function getByFeedbackType($feedbackTypeId)
    {
        return static::whereHas('feedbackTypes', function ($query) use ($feedbackTypeId) {
            $query->where('feedback_type_id', $feedbackTypeId);
        })->get();
    }
    
    /**
     * Récupérer un sentiment aléatoire selon le type de feedback
     */
    public static function getRandomByFeedbackType($feedbackTypeId)
    {
        $sentiments = static::getByFeedbackType($feedbackTypeId);
        return $sentiments->isNotEmpty() ? $sentiments->random() : null;
    }
    
    /**
     * Récupérer le sentiment par défaut pour un type de feedback
     */
    public static function getDefaultByFeedbackType($feedbackTypeId)
    {
        return static::whereHas('feedbackTypes', function ($query) use ($feedbackTypeId) {
            $query->where('feedback_type_id', $feedbackTypeId)
                  ->where('is_default', true);
        })->first();
    }
    
    /**
     * Seeder automatique des sentiments
     */
    public static function seedSentiments()
    {
        $allSentiments = [
            // Sentiments positifs
            ['name' => 'Très satisfait', 'description' => 'Client très content du service reçu'],
            ['name' => 'Content', 'description' => 'Client satisfait de son expérience'],
            ['name' => 'Heureux', 'description' => 'Client joyeux et satisfait'],
            ['name' => 'Reconnaissant', 'description' => 'Client qui apprécie le service'],
            ['name' => 'Impressionné', 'description' => 'Client impressionné par la qualité'],
            
            // Sentiments négatifs
            ['name' => 'Mécontent', 'description' => 'Client pas satisfait du service'],
            ['name' => 'Frustré', 'description' => 'Client frustré par une situation'],
            ['name' => 'Déçu', 'description' => 'Client déçu par le service reçu'],
            ['name' => 'Irrité', 'description' => 'Client agacé ou contrarié'],
            ['name' => 'Insatisfait', 'description' => 'Client non satisfait globalement'],
            
            // Sentiments pour suggestions
            ['name' => 'Constructif', 'description' => 'Retour constructif pour améliorer'],
            ['name' => 'Propositionnel', 'description' => 'Propose des améliorations'],
            ['name' => 'Amélioration', 'description' => 'Suggère des améliorations'],
            ['name' => 'Innovation', 'description' => 'Propose des innovations'],
            ['name' => 'Optimisation', 'description' => 'Suggère des optimisations'],
            
            // Neutre
            ['name' => 'Neutre', 'description' => 'Sentiment neutre par défaut']
        ];
        
        foreach ($allSentiments as $sentiment) {
            static::updateOrCreate(
                ['name' => $sentiment['name']], 
                $sentiment
            );
        }
    }
}
