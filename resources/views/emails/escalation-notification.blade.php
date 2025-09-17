<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $urgencyLabel }} - QualyWatch</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px;
        }
        .header {
            background: linear-gradient(135deg, {{ $urgencyColor }}, {{ $urgencyColor }}dd);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header .level {
            font-size: 18px;
            margin-top: 5px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .alert-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid {{ $urgencyColor }};
        }
        .feedback-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .feedback-details h3 {
            margin-top: 0;
            color: #495057;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
        }
        .detail-value {
            color: #495057;
        }
        .rating {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
        }
        .rating-1, .rating-2 { background-color: #dc3545; }
        .rating-3 { background-color: #ffc107; color: #000; }
        .rating-4, .rating-5 { background-color: #28a745; }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, {{ $urgencyColor }}, {{ $urgencyColor }}dd);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .action-button:hover {
            opacity: 0.9;
        }
        .escalation-info {
            background-color: #e3f2fd;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .urgency-badge {
            display: inline-block;
            background-color: {{ $urgencyColor }};
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .content-text {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
            font-style: italic;
            border-radius: 0 6px 6px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 {{ $urgencyLabel }}</h1>
            <div class="level">Niveau {{ $escalation->escalation_level }}</div>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user->full_name }}</strong>,</p>

            <div class="alert-box">
                <strong>⚠️ Action requise :</strong> Un feedback négatif nécessite votre attention immédiate en tant que
                @if($escalation->escalation_level == 1)
                    <strong>Manager</strong>
                @elseif($escalation->escalation_level == 2)
                    <strong>Directeur</strong>
                @else
                    <strong>PDG</strong>
                @endif
                de {{ $company->name }}.
            </div>

            <div class="feedback-details">
                <h3>📋 Détails du Feedback</h3>

                <div class="detail-row">
                    <span class="detail-label">Référence :</span>
                    <span class="detail-value"><strong>{{ $feedback->reference }}</strong></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Type :</span>
                    <span class="detail-value">{{ $feedback->feedbackType->name ?? 'Feedback Négatif' }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Note :</span>
                    <span class="detail-value">
                        <span class="rating rating-{{ $feedback->rating }}">{{ $feedback->rating }}/5</span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Client :</span>
                    <span class="detail-value">{{ $client->name ?? $feedback->client_id }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date :</span>
                    <span class="detail-value">{{ $feedback->created_at->format('d/m/Y à H:i') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Statut :</span>
                    <span class="detail-value">{{ $feedback->feedbackStatus->name ?? 'Nouveau' }}</span>
                </div>

                @if($feedback->content)
                <div class="content-text">
                    <strong>💬 Contenu du feedback :</strong><br>
                    "{{ $feedback->content }}"
                </div>
                @endif
            </div>

            <div class="escalation-info">
                <h4>🚨 Informations sur l'escalade</h4>
                <div class="detail-row">
                    <span class="detail-label">Raison :</span>
                    <span class="detail-value">
                        @switch($escalation->trigger_reason)
                            @case('sla_breach')
                                Dépassement du délai SLA
                                @break
                            @case('critical_rating')
                                Note critique avec sentiment négatif
                                @break
                            @case('multiple_incidents')
                                Incidents multiples du même client
                                @break
                            @case('urgent_sentiment')
                                Sentiment urgent détecté
                                @break
                            @default
                                {{ $escalation->trigger_reason }}
                        @endswitch
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Escaladé le :</span>
                    <span class="detail-value">{{ $escalation->escalated_at->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Urgence :</span>
                    <span class="detail-value"><span class="urgency-badge">{{ $urgencyLabel }}</span></span>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $actionUrl }}" class="action-button">
                    🔍 Traiter ce Feedback
                </a>
            </div>

            <div style="background-color: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107;">
                <strong>⏰ Délais SLA :</strong><br>
                @if($escalation->escalation_level == 1)
                    En tant que Manager, vous devez traiter ce feedback dans les plus brefs délais pour éviter une escalade vers la Direction.
                @elseif($escalation->escalation_level == 2)
                    En tant que Directeur, ce feedback a déjà dépassé les délais Manager et nécessite votre intervention immédiate.
                @else
                    En tant que PDG, ce feedback a dépassé tous les délais SLA et nécessite votre attention personnelle immédiate.
                @endif
            </div>

            <p style="margin-top: 30px;">
                <strong>Actions recommandées :</strong>
            </p>
            <ul>
                <li>Prendre contact avec le client dans les plus brefs délais</li>
                <li>Analyser la cause du problème</li>
                <li>Proposer une solution adaptée</li>
                <li>Mettre à jour le statut du feedback dans QualyWatch</li>
                @if($escalation->escalation_level >= 2)
                <li>Informer l'équipe des mesures correctives mises en place</li>
                @endif
            </ul>

            <p>Cordialement,<br>
            <strong>L'équipe QualyWatch</strong></p>
        </div>

        <div class="footer">
            <p>
                📧 Cette notification a été envoyée automatiquement par QualyWatch<br>
                🏢 {{ $company->name }} | 📞 {{ $company->phone ?? 'N/A' }}
            </p>
            <p style="font-size: 12px; margin-top: 10px;">
                Pour vous désabonner ou modifier vos préférences de notification, connectez-vous à votre compte QualyWatch.
            </p>
        </div>
    </div>
</body>
</html>