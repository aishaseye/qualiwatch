<?php

namespace App\Services;

use App\Models\PositiveSentiment;
use App\Models\NegativeSentiment;
use App\Models\SuggestionSentiment;
use App\Models\FeedbackType;

class SentimentService
{
    /**
     * Récupérer les sentiments selon le type de feedback
     * 
     * @param string $feedbackTypeId UUID du type de feedback
     * @return \Illuminate\Support\Collection
     */
    public static function getSentimentsByFeedbackType($feedbackTypeId)
    {
        $feedbackType = FeedbackType::find($feedbackTypeId);
        
        if (!$feedbackType) {
            return collect([]);
        }

        return match($feedbackType->name) {
            'positif' => PositiveSentiment::getForSelect(),
            'negatif' => NegativeSentiment::getForSelect(),
            'incident' => NegativeSentiment::getForSelect(), // Les incidents utilisent les sentiments négatifs
            'suggestion' => SuggestionSentiment::getForSelect(),
            default => collect([])
        };
    }

    /**
     * Récupérer le sentiment par défaut selon le type de feedback
     * 
     * @param string $feedbackTypeId UUID du type de feedback
     * @return mixed
     */
    public static function getDefaultSentimentByFeedbackType($feedbackTypeId)
    {
        $feedbackType = FeedbackType::find($feedbackTypeId);
        
        if (!$feedbackType) {
            return null;
        }

        return match($feedbackType->name) {
            'positif' => PositiveSentiment::getDefault(),
            'negatif' => NegativeSentiment::getDefault(),
            'incident' => NegativeSentiment::getDefault(),
            'suggestion' => SuggestionSentiment::getDefault(),
            default => null
        };
    }

    /**
     * Récupérer un sentiment aléatoire selon le type de feedback
     * 
     * @param string $feedbackTypeId UUID du type de feedback
     * @return mixed
     */
    public static function getRandomSentimentByFeedbackType($feedbackTypeId)
    {
        $sentiments = static::getSentimentsByFeedbackType($feedbackTypeId);
        
        return $sentiments->isNotEmpty() ? $sentiments->random() : null;
    }

    /**
     * Valider qu'un sentiment ID correspond bien au type de feedback
     * 
     * @param int $sentimentId
     * @param string $feedbackTypeId
     * @return array|null [sentiment_type, is_valid]
     */
    public static function validateSentimentForFeedbackType($sentimentId, $feedbackTypeId)
    {
        $feedbackType = FeedbackType::find($feedbackTypeId);
        
        if (!$feedbackType) {
            return null;
        }

        $sentimentType = match($feedbackType->name) {
            'positif' => 'positive',
            'negatif', 'incident' => 'negative',
            'suggestion' => 'suggestion',
            default => null
        };
        
        if (!$sentimentType) {
            return null;
        }

        $model = match($sentimentType) {
            'positive' => PositiveSentiment::class,
            'negative' => NegativeSentiment::class,
            'suggestion' => SuggestionSentiment::class,
            default => null
        };

        $isValid = $model ? $model::where('id', $sentimentId)->exists() : false;
        
        return [
            'sentiment_type' => $sentimentType,
            'is_valid' => $isValid
        ];
    }

    /**
     * Récupérer les informations d'un sentiment selon son type
     * 
     * @param int $sentimentId
     * @param string $sentimentType
     * @return mixed
     */
    public static function getSentimentInfo($sentimentId, $sentimentType)
    {
        $model = match($sentimentType) {
            'positive' => PositiveSentiment::class,
            'negative' => NegativeSentiment::class,
            'suggestion' => SuggestionSentiment::class,
            default => null
        };

        if (!$model) {
            return null;
        }

        return $model::find($sentimentId);
    }
    
    /**
     * Déterminer le type de sentiment selon le type de feedback
     * 
     * @param string $feedbackTypeId
     * @return string|null
     */
    public static function getSentimentTypeByFeedbackType($feedbackTypeId)
    {
        $feedbackType = FeedbackType::find($feedbackTypeId);
        
        if (!$feedbackType) {
            return null;
        }

        return match($feedbackType->name) {
            'positif' => 'positive',
            'negatif', 'incident' => 'negative', 
            'suggestion' => 'suggestion',
            default => null
        };
    }
}