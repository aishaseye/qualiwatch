<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci pour votre suggestion</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .email-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        .header-container {
            text-align: center;
            padding: 0;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            width: 100%;
            border-radius: 20px 20px 0 0;
        }

        .header-container h1 {
            margin: 0;
            padding: 30px;
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            text-align: center;
            width: 100%;
            display: block;
        }

        .content {
            padding: 30px;
            text-align: center;
        }

        .suggestion-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            margin-bottom: 20px;
        }

        .info-card {
            background: #F8F9FA;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2563EB;
        }

        .thank-you-section {
            background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header-container">
            <h1>💡 Merci pour votre suggestion !</h1>
        </div>

        <div class="content">
            <div class="suggestion-badge">
                💡 Suggestion reçue
            </div>

            <p><strong>Bonjour {{ $client_name ?? 'Client' }} !</strong></p>

            <p>Nous vous remercions sincèrement pour votre suggestion concernant nos services.</p>

            <div class="info-card">
                <div style="text-align: left;">
                    <p><strong>Référence:</strong> {{ $feedback_reference ?? 'N/A' }}</p>
                    <p><strong>Votre note:</strong> {{ $rating ?? 'N/A' }}/5 ⭐</p>
                    <p><strong>Date:</strong> {{ $created_at ?? now()->format('d/m/Y à H:i') }}</p>

                    @if($message ?? false)
                    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 15px; margin-top: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #2563EB;">Votre suggestion:</h4>
                        <p style="margin: 0; font-style: italic;">"{{ $message }}"</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="thank-you-section">
                <h3 style="color: #1E40AF; margin: 0 0 15px 0;">🙏 Votre avis compte pour nous</h3>
                <p style="margin: 0; color: #1E3A8A;">
                    Vos suggestions nous aident à améliorer continuellement nos services.<br>
                    Notre équipe va étudier attentivement votre proposition.
                </p>
            </div>

            <div style="margin-top: 30px; text-align: left;">
                <strong>Que se passe-t-il ensuite ?</strong><br>
                • Notre équipe analyse votre suggestion<br>
                • Nous évaluons la faisabilité de sa mise en œuvre<br>
                • Vous serez informé(e) si nous décidons de l'implémenter<br>
                • Votre contribution sera reconnue le cas échéant
            </div>

            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 14px; color: #64748B;">
                    💝 <strong>Bonus:</strong> Vous gagnez des KaliPoints pour cette suggestion constructive !
                </p>
            </div>

            <p style="color: #666; margin-top: 30px;">
                Cordialement,<br>
                L'équipe {{ $company_name ?? 'QualyWatch' }}
            </p>
        </div>
    </div>
</body>
</html>