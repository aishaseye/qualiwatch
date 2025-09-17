<?php

namespace App\Services;

use App\Models\FeedbackAlert;
use App\Models\Feedback;

class AlertDetectionService
{
    private array $criticalKeywords = [
        'catastrophe', 'catastrophique', 'catastrophic',
        'désastre', 'desastre', 'disaster',
        'désarroi', 'desarroi', 'dismay', 'distress',
        'horrible', 'terrible', 'épouvantable', 'épouvantable',
        'inacceptable', 'unacceptable',
        'scandaleux', 'scandalous',
        'inadmissible', 'inadmissible',
        'révoltant', 'revolting', 'disgusting',
        'honteux', 'shameful',
        'urgent', 'emergency', 'urgence',
        'grave', 'serious', 'sérieux',
        'problème majeur', 'major problem',
        'bug critique', 'critical bug',
        'panne totale', 'total failure',
        'dysfonctionnement', 'malfunction',
        'inutilisable', 'unusable',
        'défaillance', 'failure',
        'perte de données', 'data loss',
        'sécurité', 'security', 'securité',
        'hack', 'piratage', 'breach',
        'arnaque', 'scam', 'fraude', 'fraud'
    ];

    private array $negativeKeywords = [
        'mauvais', 'bad', 'awful',
        'nul', 'nulle', 'terrible',
        'décevant', 'disappointing',
        'frustrant', 'frustrating',
        'ennuyeux', 'boring',
        'lent', 'slow', 'buggy',
        'cassé', 'broken',
        'inutile', 'useless',
        'compliqué', 'complicated',
        'difficile', 'difficult',
        'confus', 'confusing',
        'pas content', 'not happy', 'unhappy',
        'mécontent', 'unsatisfied',
        'problème', 'problem', 'issue',
        'erreur', 'error', 'bug'
    ];

    private array $vipKeywords = [
        'client premium', 'premium client',
        'entreprise', 'enterprise',
        'partenaire', 'partner',
        'investisseur', 'investor',
        'directeur', 'director', 'ceo', 'cto',
        'manager', 'responsable',
        'chef', 'head of'
    ];

    public function analyzeFeedback(Feedback $feedback): ?FeedbackAlert
    {
        $content = strtolower($feedback->description . ' ' . $feedback->title);
        
        $detectedKeywords = $this->detectKeywords($content);
        $sentimentScore = $this->calculateSentiment($content, $detectedKeywords);
        $severity = $this->calculateSeverity($detectedKeywords, $sentimentScore, $feedback->rating);
        $alertType = $this->determineAlertType($detectedKeywords, $sentimentScore, $feedback);

        // Only create alert if severity is medium or higher
        if ($this->shouldCreateAlert($severity, $sentimentScore, $feedback->rating, $detectedKeywords)) {
            $alert = FeedbackAlert::create([
                'company_id' => $feedback->company_id,
                'feedback_id' => $feedback->id,
                'severity' => $severity,
                'alert_type' => $alertType,
                'detected_keywords' => $detectedKeywords,
                'sentiment_score' => $sentimentScore,
                'alert_reason' => $this->generateAlertReason($detectedKeywords, $sentimentScore, $feedback),
                'status' => 'new',
            ]);

            // Auto-escalate critical alerts
            if (in_array($severity, ['critical', 'catastrophic'])) {
                $alert->escalate();
            }

            return $alert;
        }

        return null;
    }

    private function detectKeywords(string $content): array
    {
        $detected = [];
        
        // Check for critical keywords
        foreach ($this->criticalKeywords as $keyword) {
            if (strpos($content, strtolower($keyword)) !== false) {
                $detected[] = $keyword;
            }
        }

        // Check for negative keywords (only if no critical found)
        if (empty($detected)) {
            foreach ($this->negativeKeywords as $keyword) {
                if (strpos($content, strtolower($keyword)) !== false) {
                    $detected[] = $keyword;
                }
            }
        }

        // Check for VIP keywords
        foreach ($this->vipKeywords as $keyword) {
            if (strpos($content, strtolower($keyword)) !== false) {
                $detected[] = $keyword;
            }
        }

        return array_unique($detected);
    }

    private function calculateSentiment(string $content, array $keywords): float
    {
        $score = 0;
        
        // Base sentiment from keywords
        foreach ($keywords as $keyword) {
            if (in_array($keyword, $this->criticalKeywords)) {
                $score -= 0.8;
            } elseif (in_array($keyword, $this->negativeKeywords)) {
                $score -= 0.4;
            }
        }

        // Additional sentiment indicators
        $positiveWords = ['bon', 'good', 'excellent', 'parfait', 'perfect', 'génial', 'great', 'super', 'fantastique'];
        $negativeWords = ['non', 'no', 'jamais', 'never', 'pas', 'not', 'rien', 'nothing'];

        foreach ($positiveWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                $score += 0.2;
            }
        }

        foreach ($negativeWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                $score -= 0.3;
            }
        }

        // Normalize between -1 and 1
        return max(-1, min(1, $score));
    }

    private function calculateSeverity(array $keywords, float $sentimentScore, ?int $rating): string
    {
        // Critical keywords = catastrophic
        $criticalFound = array_intersect($keywords, $this->criticalKeywords);
        if (!empty($criticalFound)) {
            return 'catastrophic';
        }

        // Very low sentiment + high rating (for negative feedbacks) = critical
        if ($sentimentScore <= -0.7 && ($rating !== null && $rating >= 4)) {
            return 'critical';
        }

        // Low sentiment + multiple negative keywords = high
        if ($sentimentScore <= -0.5 && count(array_intersect($keywords, $this->negativeKeywords)) >= 3) {
            return 'high';
        }

        // High rating for negative feedback = high severity
        if ($rating !== null && $rating >= 4) {
            return 'high';
        }

        // Moderate negative sentiment = medium
        if ($sentimentScore <= -0.3) {
            return 'medium';
        }

        return 'low';
    }

    private function determineAlertType(array $keywords, float $sentimentScore, Feedback $feedback): string
    {
        // Check for VIP client
        if (array_intersect($keywords, $this->vipKeywords)) {
            return 'vip_client';
        }

        // Check for critical keywords
        if (array_intersect($keywords, $this->criticalKeywords)) {
            return 'critical_keywords';
        }

        // Check for high rating (severe for negative feedbacks)
        if ($feedback->rating !== null && $feedback->rating >= 4) {
            return 'high_rating';
        }

        // Check for negative sentiment
        if ($sentimentScore <= -0.4) {
            return 'negative_sentiment';
        }

        // Check for multiple issues (multiple negative keywords)
        if (count(array_intersect($keywords, $this->negativeKeywords)) >= 3) {
            return 'multiple_issues';
        }

        return 'negative_sentiment';
    }

    private function shouldCreateAlert(string $severity, float $sentimentScore, ?int $rating, array $keywords): bool
    {
        // Always alert for critical/catastrophic
        if (in_array($severity, ['critical', 'catastrophic'])) {
            return true;
        }

        // Alert for high severity with negative sentiment
        if ($severity === 'high' && $sentimentScore <= -0.3) {
            return true;
        }

        // Alert for VIP clients
        if (array_intersect($keywords, $this->vipKeywords)) {
            return true;
        }

        // Alert for very high ratings (severe for negative feedbacks)
        if ($rating !== null && $rating >= 5) {
            return true;
        }

        // Alert for critical keywords regardless of severity
        if (array_intersect($keywords, $this->criticalKeywords)) {
            return true;
        }

        return false;
    }

    private function generateAlertReason(array $keywords, float $sentimentScore, Feedback $feedback): string
    {
        $reasons = [];

        if (array_intersect($keywords, $this->criticalKeywords)) {
            $reasons[] = 'Mots-clés critiques détectés: ' . implode(', ', array_intersect($keywords, $this->criticalKeywords));
        }

        if ($sentimentScore <= -0.5) {
            $reasons[] = 'Sentiment très négatif détecté (score: ' . round($sentimentScore, 2) . ')';
        }

        if ($feedback->rating !== null && $feedback->rating >= 4) {
            $reasons[] = 'Note très élevée pour feedback négatif: ' . $feedback->rating . '/5 (plus grave)';
        }

        if (array_intersect($keywords, $this->vipKeywords)) {
            $reasons[] = 'Client VIP identifié';
        }

        if (count(array_intersect($keywords, $this->negativeKeywords)) >= 3) {
            $reasons[] = 'Multiples problèmes identifiés';
        }

        return implode('. ', $reasons) ?: 'Feedback négatif détecté nécessitant une attention particulière';
    }

    public function getCompanyAlertStats($companyId): array
    {
        $alerts = FeedbackAlert::byCompany($companyId);

        return [
            'total' => $alerts->count(),
            'new' => $alerts->where('status', 'new')->count(),
            'in_progress' => $alerts->where('status', 'in_progress')->count(),
            'resolved' => $alerts->where('status', 'resolved')->count(),
            'critical' => $alerts->critical()->count(),
            'escalated' => $alerts->escalated()->count(),
            'by_severity' => [
                'catastrophic' => $alerts->bySeverity('catastrophic')->count(),
                'critical' => $alerts->bySeverity('critical')->count(),
                'high' => $alerts->bySeverity('high')->count(),
                'medium' => $alerts->bySeverity('medium')->count(),
                'low' => $alerts->bySeverity('low')->count(),
            ],
        ];
    }
}