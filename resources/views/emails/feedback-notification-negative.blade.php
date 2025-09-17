@component('emails.layout')
    @slot('title', '🚨 Feedback Négatif Reçu')
    @slot('company_name', $company->name ?? 'QualyWatch')
    @slot('header_subtitle', 'Action Requise - Feedback Négatif')
    @slot('reference', $feedback->reference ?? $feedback->id ?? '')

    <p>Bonjour <strong>{{ $company->name ?? 'Manager' }}</strong>,</p>

    <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <span style="font-size: 24px; margin-right: 15px;">🚨</span>
            <div>
                <h3 style="margin: 0; color: #DC2626;">Attention ! Feedback Négatif</h3>
                <p style="margin: 0; color: #991B1B; font-size: 14px;">Un client a laissé un feedback négatif qui nécessite votre attention immédiate.</p>
            </div>
        </div>
    </div>

    <div class="info-card">
        <div style="display: inline-block; padding: 6px 16px; border-radius: 25px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%); margin-bottom: 15px;">
            {{ $feedback->type === 'incident' ? '⚠️ Incident' : '👎 Négatif' }}
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <div style="background: white; padding: 15px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid #DC2626;">
                <div style="font-size: 28px; margin-bottom: 5px;">{{ $ratingStars ?? str_repeat('⭐', $feedback->rating ?? 1) }}</div>
                <div style="font-size: 20px; font-weight: bold; color: #DC2626;">
                    {{ $feedback->rating ?? 'N/A' }}/5
                </div>
                <div style="font-size: 12px; color: #DC2626; margin-top: 5px; font-weight: bold;">
                    URGENT
                </div>
            </div>
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Client:</strong> {{ $client->full_name ?? 'Client Anonyme' }}<br>
            <strong>Email:</strong> {{ $client->email ?? 'Non renseigné' }}<br>
            <strong>Type:</strong> {{ ucfirst($feedback->type) }}<br>
            <strong>Date:</strong> {{ $feedback->created_at->format('d/m/Y à H:i') ?? now()->format('d/m/Y à H:i') }}
        </div>

        @if($feedback->service ?? false)
        <div style="margin-bottom: 10px;">
            <strong>Service concerné:</strong> {{ $feedback->service->name ?? 'Non spécifié' }}
        </div>
        @endif

        @if($feedback->employee ?? false)
        <div style="margin-bottom: 10px;">
            <strong>Employé concerné:</strong> {{ $feedback->employee->full_name ?? 'Non spécifié' }}
        </div>
        @endif
    </div>

    <div style="margin-top: 20px;">
        <strong>Message du Client :</strong>
        <div style="background: #F9FAFB; border-left: 4px solid #DC2626; border-radius: 8px; padding: 15px; margin-top: 10px;">
            <p style="margin: 0; font-style: italic; color: #374151;">
                "{{ $feedback->description ?? $feedback->message ?? 'Aucun message fourni' }}"
            </p>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%); border-radius: 8px; padding: 20px; margin: 25px 0;">
        <h3 style="color: #DC2626; margin: 0 0 15px 0;">🚨 Actions Recommandées</h3>
        <div style="color: #DC2626; font-weight: bold; margin-bottom: 15px;">Priorité élevée - Réponse dans les 2h recommandée :</div>

        <ol style="color: #333; line-height: 1.8; margin: 0; padding-left: 20px;">
            <li><strong>Contactez le client</strong> pour comprendre le problème</li>
            <li><strong>Identifiez les causes</strong> du dysfonctionnement</li>
            <li><strong>Mettez en place des corrections</strong> immédiates</li>
            <li><strong>Suivez l'évolution</strong> de la satisfaction client</li>
        </ol>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.frontend_url', config('app.url')) . '/dashboard/feedbacks/' . $feedback->id }}"
           style="display: inline-block; background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px;">
            🚨 Traiter ce Feedback Urgent
        </a>
    </div>

    <div style="margin-top: 30px;">
        <strong>Statistiques Rapides :</strong><br>
        • Satisfaction générale : {{ number_format($company->satisfaction_score ?? 4.2, 1) }}/5 ⭐<br>
        • Total feedbacks : {{ $company->total_feedbacks ?? 'N/A' }} retours reçus<br>
        • Temps de réponse moyen : 2h<br>
        • Taux de résolution : 95%
    </div>

    <div style="background-color: #FEE2E2; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #DC2626;">
        <strong style="color: #DC2626;">⚠️ Rappel Important</strong><br>
        <span style="color: #991B1B;">
            Les feedbacks négatifs impactent directement votre réputation. Une réponse rapide et efficace peut transformer une expérience négative en opportunité de fidélisation.
        </span>
    </div>

@endcomponent