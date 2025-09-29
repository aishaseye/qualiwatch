@component('emails.layout')
    @slot('title', 'Escalation ' . $urgencyLabel . ' - QualyWatch')
    @slot('company_name', $company->name ?? 'Watch')
    @slot('header_subtitle', 'Escalation Niveau ' . $escalation->escalation_level)
    @slot('reference', $feedback->reference)

    <p>Bonjour <strong>{{ $user->full_name }}</strong>,</p>

    <p>Un feedback négatif nécessite votre attention immédiate en tant que
        @if($escalation->escalation_level == 1)
            <strong>Manager</strong>
        @elseif($escalation->escalation_level == 2)
            <strong>Directeur</strong>
        @else
            <strong>PDG</strong>
        @endif
        de {{ $company->name }}.
    </p>

    <div class="info-card">
        <div style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, {{ $urgencyColor }} 0%, {{ $urgencyColor }}dd 100%); margin-bottom: 15px;">
            {{ $urgencyLabel }}
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Référence:</strong> {{ $feedback->reference }}
        </div>
        <div style="margin-bottom: 10px;">
            <strong>Type:</strong> {{ $feedback->feedbackType->name ?? 'Feedback Négatif' }}
        </div>
        <div style="margin-bottom: 10px;">
            <strong>Note:</strong>
            <span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: white; background-color: @if($feedback->rating <= 2) #dc3545 @elseif($feedback->rating == 3) #ffc107; color: #000 @else #28a745 @endif;">
                {{ $feedback->rating }}/5
            </span>
        </div>
        <div style="margin-bottom: 10px;">
            <strong>Client:</strong> {{ $client->name ?? $feedback->client_id }}
        </div>
        <div style="margin-bottom: 15px;">
            <strong>Date:</strong> {{ $feedback->created_at->format('d/m/Y à H:i') }}
        </div>

        @if($feedback->content)
        <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0; color: #EA580C;">Contenu du feedback:</h4>
            <p style="margin: 0; font-style: italic;">"{{ $feedback->content }}"</p>
        </div>
        @endif
    </div>

    <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 6px; padding: 15px; margin: 20px 0; color: #C2410C;">
        <strong>Raison de l'escalade:</strong>
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
        <br>
        <small>Escaladé le {{ $escalation->escalated_at->format('d/m/Y à H:i') }}</small>
    </div>

    <div class="text-center mt-20 mb-20">
        <p><strong>Action requise immédiatement :</strong></p>

        <div style="margin: 30px 0;">
            <a href="{{ $actionUrl }}" style="display: inline-block; background: linear-gradient(135deg, {{ $urgencyColor }} 0%, {{ $urgencyColor }}dd 100%); color: white; padding: 18px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 18px; min-width: 200px; text-align: center; box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);">
                🔍 Traiter ce Feedback
            </a>
        </div>
    </div>

    <div style="background: #FFF3CD; border: 1px solid #FFE69C; border-radius: 8px; padding: 20px; margin: 20px 0; color: #8A5A00;">
        <h4 style="margin: 0 0 10px 0; color: #8A5A00;">⏰ Délais SLA</h4>
        @if($escalation->escalation_level == 1)
            En tant que Manager, vous devez traiter ce feedback dans les plus brefs délais pour éviter une escalade vers la Direction.
        @elseif($escalation->escalation_level == 2)
            En tant que Directeur, ce feedback a déjà dépassé les délais Manager et nécessite votre intervention immédiate.
        @else
            En tant que PDG, ce feedback a dépassé tous les délais SLA et nécessite votre attention personnelle immédiate.
        @endif
    </div>

    <div style="margin-top: 30px;">
        <strong>Actions recommandées :</strong><br>
        <div style="text-align: left; margin: 15px 0; padding-left: 20px;">
            • Prendre contact avec le client dans les plus brefs délais<br>
            • Analyser la cause du problème<br>
            • Proposer une solution adaptée<br>
            • Mettre à jour le statut du feedback dans QualyWatch<br>
            @if($escalation->escalation_level >= 2)
            • Informer l'équipe des mesures correctives mises en place<br>
            @endif
        </div>
    </div>

    <div style="margin-top: 30px;">
        <strong>Informations de contact :</strong><br>
        @if($client->email ?? false)
        📧 {{ $client->email }}<br>
        @endif
        @if($client->phone ?? false)
        📞 {{ $client->phone }}<br>
        @endif
    </div>

    <p style="font-size: 14px; color: #666; margin-top: 30px;">
        Cette notification a été envoyée automatiquement par le système QualyWatch suite au déclenchement des règles SLA.
    </p>

@endcomponent