@component('emails.layout')
    @slot('title', 'Rappel - Validation de votre feedback')
    @slot('company_name', $company_name)
    @slot('header_subtitle', 'Rappel de validation')
    @slot('reference', $feedback_reference)

    <p>Bonjour <strong>{{ $client_name }}</strong>,</p>

    <p>Nous souhaitons nous assurer que votre problème a bien été résolu. Pouvez-vous confirmer l'état de votre feedback ?</p>

    <div class="info-card">
        <div style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); margin-bottom: 15px;">
            ⏰ Rappel {{ $feedback_type === 'incident' ? 'Incident' : 'Suggestion' }}
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Référence:</strong> {{ $feedback_reference }}
        </div>
        <div style="margin-bottom: 15px;">
            <strong>Titre:</strong> {{ $feedback_title }}
        </div>

        <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0; color: #EA580C;">Action entreprise:</h4>
            <p style="margin: 0;">{{ $admin_resolution }}</p>
        </div>
    </div>

    <div style="background: #FFF7ED; border: 1px solid #FDBA74; border-radius: 6px; padding: 15px; margin: 20px 0; color: #C2410C;">
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 20px; margin-right: 10px;">⚠️</span>
            <strong>Rappel important</strong>
        </div>
        <p style="margin: 0;">Votre validation expire le {{ $expires_at }}. Merci de nous indiquer si le problème est résolu.</p>
    </div>

    <div class="text-center mt-20 mb-20">
        <p><strong>Merci de nous confirmer si notre intervention a résolu votre problème :</strong></p>

        <div style="margin: 30px 0;">
            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                    <td style="padding: 10px;">
                        <a href="{{ $resolved_url }}" style="display: inline-block; background: linear-gradient(135deg, #10B981 0%, #34D399 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; min-width: 150px; text-align: center;">
                            ✅ Problème résolu
                        </a>
                    </td>
                    <td style="padding: 10px;">
                        <a href="{{ $not_resolved_url }}" style="display: inline-block; background: linear-gradient(135deg, #EF4444 0%, #F87171 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; min-width: 150px; text-align: center;">
                            ❌ Non résolu
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); padding: 20px; border-radius: 12px; text-align: center; margin: 25px 0;">
        <h4 style="margin: 0 0 10px 0; color: #D97706;">📞 Besoin d'aide ?</h4>
        <p style="margin: 0; color: #92400E;">
            Si vous rencontrez des difficultés, n'hésitez pas à nous contacter directement.
        </p>
        <div style="margin-top: 15px;">
            <a href="tel:{{ $company_phone ?? '' }}" style="display: inline-block; background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%); color: white; padding: 10px 20px; text-decoration: none; border-radius: 20px; font-weight: bold; font-size: 14px;">
                📞 Nous appeler
            </a>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <strong>Pourquoi valider ?</strong><br>
        • Nous aider à confirmer que votre problème est résolu<br>
        • Améliorer la qualité de nos interventions<br>
        • Permettre un suivi personnalisé si nécessaire<br>
        • Participer à l'amélioration continue de nos services
    </div>

    <p style="font-size: 14px; color: #666; margin-top: 20px;">
        Ceci est un rappel automatique. Si les boutons ne fonctionnent pas, vous pouvez nous contacter directement.
    </p>

@endcomponent