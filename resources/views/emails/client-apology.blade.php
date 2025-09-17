@component('emails.layout')
    @slot('title', 'Nos excuses pour votre expérience')
    @slot('company_name', $company->name)
    @slot('header_subtitle', '')
    @slot('reference', $feedback->reference ?? $feedback->id)

    <p>Bonjour <strong>{{ $clientName }}</strong>,</p>

    <p>Nous avons pris connaissance de votre retour concernant votre récente expérience et nous nous excusons sincèrement.</p>

    <div class="info-card">
        <div style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: white; background: linear-gradient(135deg, {{ $apologyLevel['color'] }} 0%, {{ $apologyLevel['color'] }}CC 100%); margin-bottom: 15px;">
            {{ $apologyLevel['intensity'] }}
        </div>

        <h3 style="color: #1f2937; margin-bottom: 10px; font-size: 18px;">{{ $apologyLevel['title'] }}</h3>

        <div style="background-color: #f9fafb; padding: 15px; border-radius: 8px; border-left: 4px solid {{ $apologyLevel['color'] }};">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="font-size: 24px; margin-right: 15px;">
                    {{ $ratingStars }}
                </div>
                <div>
                    <div style="font-size: 18px; font-weight: bold; color: {{ $apologyLevel['color'] }};">{{ $feedback->rating }}/5</div>
                    <div style="font-size: 12px; color: #6b7280;">Votre évaluation</div>
                </div>
            </div>

            @if($feedback->description)
            <div style="font-style: italic; color: #4b5563; margin-top: 15px; padding: 10px; background-color: white; border-radius: 6px;">
                "{{ $feedback->description }}"
            </div>
            @endif
        </div>
    </div>

    <div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); padding: 20px; border-radius: 12px; margin: 25px 0;">
        <h3 style="color: #1E40AF; margin-bottom: 15px; font-size: 16px;">🙏 Notre engagement immédiat</h3>

        <div style="display: grid; gap: 10px;">
            <div style="background: white; padding: 12px; border-radius: 8px; border-left: 3px solid #3B82F6;">
                <strong style="color: #1E40AF;">🔍 Analyse</strong> - Identification des causes du problème
            </div>
            <div style="background: white; padding: 12px; border-radius: 8px; border-left: 3px solid #3B82F6;">
                <strong style="color: #1E40AF;">⚡ Action</strong> - Mise en place d'améliorations immédiates
            </div>
            <div style="background: white; padding: 12px; border-radius: 8px; border-left: 3px solid #3B82F6;">
                <strong style="color: #1E40AF;">📞 Suivi</strong> - {{ $apologyLevel['urgency'] ?? 'Contact personnalisé sous 24h' }}
            </div>
        </div>
    </div>

    <div style="background-color: #FEF3C7; padding: 20px; border-radius: 12px; text-align: center; margin: 25px 0;">
        <h4 style="margin: 0 0 10px 0; color: #D97706;">💝 Geste commercial</h4>
        <p style="margin: 0; color: #92400E;">
            Pour vous témoigner notre attachement à votre satisfaction, nous vous offrons un geste commercial lors de votre prochaine visite.
        </p>
    </div>

    <div style="text-align: center; margin: 25px 0;">
        <a href="tel:{{ $company->phone ?? '' }}" style="display: inline-block; background: linear-gradient(135deg, #10B981 0%, #34D399 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold;">
            📞 Nous contacter directement
        </a>
    </div>

    <div style="margin-top: 30px;">
        <strong>Notre engagement :</strong><br>
        • Analyser et comprendre les causes du problème<br>
        • Mettre en place des actions correctives immédiates<br>
        • Vous recontacter pour valider la résolution<br>
        • Améliorer continuellement nos services
    </div>

    <p style="font-size: 14px; color: #666; margin-top: 20px;">
        Nous nous excusons sincèrement et nous engageons à mieux faire pour votre satisfaction.
    </p>

@endcomponent