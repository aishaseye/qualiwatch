<x-mail::message>
{{-- Header avec logo --}}
<div style="text-align: center; margin-bottom: 40px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px;">
        <h1 style="color: white; margin: 0; font-size: 24px; font-weight: 300;">
            🙏 {{ $apologyLevel['title'] }}
        </h1>
    </div>
</div>

{{-- Salutation --}}
<div style="text-align: center; margin-bottom: 30px;">
    <p style="font-size: 18px; color: #4A5568; margin: 0;">
        Bonjour <strong>{{ $clientName }}</strong>,
    </p>
</div>

{{-- Message principal --}}
<div style="background: #F7FAFC; padding: 25px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid {{ $apologyLevel['color'] }};">
    <p style="font-size: 16px; line-height: 1.6; color: #2D3748; margin: 0;">
        {{ $apologyLevel['message'] }}
    </p>
</div>

{{-- Note du client - Design simple et élégant --}}
<div style="text-align: center; margin: 30px 0;">
    <div style="background: white; border: 2px solid {{ $apologyLevel['color'] }}; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); max-width: 280px; margin: 0 auto;">
        <h3 style="color: #4A5568; margin: 0 0 15px 0; font-size: 16px; font-weight: 500;">Votre Évaluation</h3>
        
        <div style="font-size: 28px; margin: 10px 0;">{{ $ratingStars }}</div>
        
        <div style="font-size: 32px; font-weight: bold; color: {{ $apologyLevel['color'] }}; margin: 8px 0;">
            {{ $feedback->rating }}/5
        </div>
        
        <div style="background: {{ $apologyLevel['color'] }}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 8px;">
            {{ $apologyLevel['intensity'] }}
        </div>
    </div>
</div>

{{-- Message du client --}}
@if($feedback->message)
<div style="background: #EDF2F7; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <h4 style="color: #4A5568; margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Votre Message</h4>
    <p style="font-style: italic; color: #2D3748; margin: 0; font-size: 15px; line-height: 1.5;">
        "{{ $feedback->message }}"
    </p>
</div>
@endif

{{-- Actions engagées --}}
<div style="background: linear-gradient(135deg, #E6FFFA 0%, #B2F5EA 100%); padding: 25px; border-radius: 8px; margin: 25px 0;">
    <h3 style="color: #234E52; margin: 0 0 15px 0; font-size: 18px; text-align: center;">
        🤝 Notre Engagement
    </h3>
    
    <div style="text-align: center;">
        <div style="display: inline-block; text-align: left; max-width: 400px;">
            <p style="color: #2C7A7B; margin: 8px 0; font-size: 14px;">
                ✅ <strong>Analyse immédiate</strong> du problème signalé
            </p>
            <p style="color: #2C7A7B; margin: 8px 0; font-size: 14px;">
                ⚡ <strong>{{ $apologyLevel['urgency'] }}</strong>
            </p>
            <p style="color: #2C7A7B; margin: 8px 0; font-size: 14px;">
                🎯 <strong>Actions correctives</strong> pour éviter toute récidive
            </p>
        </div>
    </div>
</div>

{{-- Geste commercial --}}
<div style="text-align: center; margin: 30px 0;">
    <div style="background: #FFF5F5; border: 2px dashed #FC8181; padding: 20px; border-radius: 8px;">
        <h4 style="color: #C53030; margin: 0 0 10px 0; font-size: 16px;">
            🎁 Geste Commercial
        </h4>
        <p style="color: #744210; margin: 0; font-size: 14px;">
            Un avantage vous sera proposé lors de votre prochaine visite
        </p>
    </div>
</div>

{{-- Bouton de contact --}}
<x-mail::button :url="'tel:' . ($company->phone ?? '')" color="primary">
📞 Nous Contacter : {{ $company->phone ?? 'Bientôt disponible' }}
</x-mail::button>

{{-- Footer simple --}}
<div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #E2E8F0;">
    <p style="color: #718096; font-size: 12px; margin: 5px 0;">
        <strong>Qualy{{ $company->name }}</strong> - Nous nous excusons sincèrement
    </p>
    <p style="color: #A0AEC0; font-size: 11px; margin: 0;">
        Cet email a été envoyé automatiquement suite à votre feedback
    </p>
</div>

Cordialement,<br>
L'équipe Qualy{{ $company->name }}
</x-mail::message>