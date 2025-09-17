<x-mail::message>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/qualywatch-logo.png') }}" alt="Qualy{{ $company->name }}" style="max-width: 200px; height: auto;">
</div>

# {{ $apologyLevel['title'] }}

Bonjour {{ $clientName }},

{{ $apologyLevel['message'] }}

<x-mail::panel>
<div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: {{ $ratingColor }}; text-align: center; font-family: monospace;">
{{ $feedback->rating }}/5
</div>
</x-mail::panel>

**Votre évaluation :** {{ $ratingStars }}

**Important :**
- {{ $apologyLevel['urgency'] }}
- Notre équipe analysera immédiatement ce problème
- Des mesures correctives seront mises en place
- Un suivi personnalisé vous sera proposé

<x-mail::button :url="'tel:' . ($company->phone ?? '')" color="primary">
Nous contacter directement
</x-mail::button>

---

**Qualy{{ $company->name }}** - Nous nous excusons sincèrement  
Chaque retour nous aide à nous améliorer

<div style="text-align: center; margin-top: 30px; color: #6B7280; font-size: 12px;">
    <p>Cet email a été envoyé par Qualy{{ $company->name }}</p>
    <p>{{ $company->name }} - {{ $company->phone ?? '' }}</p>
</div>

Cordialement,<br>
L'équipe Qualy{{ $company->name }}
</x-mail::message>