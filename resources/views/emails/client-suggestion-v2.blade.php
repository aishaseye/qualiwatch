@component('emails.layout')
    @slot('title', 'Merci pour votre suggestion')
    @slot('company_name', $company_name)
    @slot('header_subtitle', '')
    @slot('reference', $feedback_reference)

    <p>Bonjour <strong>{{ $client_name }}</strong>,</p>

    <p>Nous vous remercions sincèrement pour votre suggestion concernant nos services.</p>

    <div class="info-card">
        <div style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%); margin-bottom: 15px;">
            💡 Suggestion
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Référence:</strong> {{ $feedback_reference }}
        </div>
        <div style="margin-bottom: 10px;">
            <strong>Votre note:</strong> {{ $rating }}/5 ⭐
        </div>
        <div style="margin-bottom: 15px;">
            <strong>Date:</strong> {{ $created_at }}
        </div>

        @if(!empty($description))
        <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 8px; padding: 15px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0; color: #EA580C;">Votre suggestion:</h4>
            <p style="margin: 0; font-style: italic;">"{{ $description }}"</p>
        </div>
        @endif
    </div>

    <div style="background: linear-gradient(135deg, #FEF3E2 0%, #FED7AA 100%); border-radius: 12px; padding: 25px; margin: 25px 0; text-align: center;">
        <h3 style="color: #C2410C; margin: 0 0 15px 0;">🙏 Votre avis compte pour nous</h3>
        <p style="margin: 0; color: #9A3412;">
            Vos suggestions nous aident à améliorer continuellement nos services.<br>
            Notre équipe va étudier attentivement votre proposition.
        </p>
    </div>

    <div style="margin: 20px 0; text-align: left;">
        <strong style="color: #EA580C;">Que se passe-t-il ensuite ?</strong>
        <ul style="color: #4B5563; line-height: 1.8; margin: 10px 0; padding-left: 20px;">
            <li>Notre équipe analyse votre suggestion</li>
            <li>Nous évaluons la faisabilité de sa mise en œuvre</li>
            <li>Vous serez informé(e) si nous décidons de l'implémenter</li>
            <li>Votre contribution sera reconnue le cas échéant</li>
        </ul>
    </div>

    <div style="background: #FEF3E2; border: 1px solid #FDBA74; border-radius: 6px; padding: 15px; margin: 20px 0; text-align: center; color: #C2410C;">
        <strong>💝 Bonus:</strong> Vous gagnez des KaliPoints pour cette suggestion constructive !
    </div>

@endcomponent