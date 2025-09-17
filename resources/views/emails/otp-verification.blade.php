@component('emails.layout', [
    'title' => 'Vérification Email - QualyWatch',
    'company_name' => 'Watch',
    'header_subtitle' => '',
    'reference' => 'OTP-' . now()->format('Ymd')
])

<div class="text-center ">
    <h2 style="color: #333; margin-bottom: 10px;">Bonjour {{ $userName }} 👋</h2>
    <p style="color: #666; font-size: 16px;">Bienvenue sur <strong>Qualywatch</strong> ! Pour finaliser votre inscription, veuillez utiliser le code de vérification ci-dessous :</p>
</div>

<div class="text-center mb-20">
    <div class="highlight-number" style="font-size: 48px; letter-spacing: 8px; margin: 20px 0;">
        {{ $otp }}
    </div>
</div>

<div class="info-card">
    <h3 style="color: #EA580C; margin-top: 0;">⚠️ Instructions importantes :</h3>
    <ul style="color: #333; line-height: 1.8;">
        <li>Ce code est valide pendant <strong>10 minutes</strong> (jusqu'à {{ $expiresAt }})</li>
        <li>Ne partagez <strong>jamais</strong> ce code avec quelqu'un d'autre</li>
        <li>Si vous n'avez pas demandé cette vérification, ignorez cet email</li>
    </ul>
</div>


<div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-top: 2px solid #EA580C;">
    <h3 style="color: #EA580C; font-size: 18px; margin-bottom: 10px; font-weight: bold;">
        QUALYWATCH
    </h3>
    <p style="color: #333; font-size: 16px; font-style: italic; margin: 0; font-weight: 500;">
        "Contrôlez aujourd'hui, améliorez demain"
    </p>
</div>

@endcomponent
