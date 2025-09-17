<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Qualy' . ($company_name ?? 'Watch') }}</title>
    <style>
        /* Variables de couleurs QualyWatch */
        :root {
            --orange-gradient: linear-gradient(135deg, #EA580C 0%, #FB923C 100%);
            --orange-primary: #EA580C;
            --orange-light: #FB923C;
            --orange-shadow: rgba(234, 88, 12, 0.3);
            --orange-shadow-hover: rgba(234, 88, 12, 0.4);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            text-align: center;
        }
        
        .email-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
        }
        
        .header {
            text-align: center;
            background: var(--orange-gradient);
            color: white;
            padding: 0;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .full-width-header {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .header-container {
            background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%);
            color: white !important;
            text-align: center;
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin: 0;
            padding: 20px 0;
            box-sizing: border-box;
            border-radius: 20px 20px 0 0;
        }
        
        .header-container h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .header-container p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
            color: white !important;
        }
        
        .content {
            padding: 30px;
            text-align: center;
        }
        
        .btn-orange {
            display: inline-block;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 5px;
            text-align: center;
            font-size: 16px;
            min-width: 180px;
            background: var(--orange-gradient);
            box-shadow: 0 4px 15px var(--orange-shadow);
            transition: all 0.3s ease;
        }
        
        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--orange-shadow-hover);
            color: white;
        }
        
        .highlight-number {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 4px;
            text-align: center;
            font-family: monospace;
            background: var(--orange-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: var(--orange-primary);
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--orange-primary);
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 12px;
        }
        
        .text-center { text-align: center; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }
    </style>
</head>
<body>
    <!-- Barre d'en-tête pleine largeur orange gradient au début -->
    <div class="full-width-header">
        <div class="header-container" style="text-align: center; padding: 0; background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%); width: 100%; border-radius: 20px 20px 0 0;">
            <!-- Nom de l'entreprise directement dans le conteneur orange -->
            <h1 style="margin: 0; padding: 30px; font-size: 28px; font-weight: bold; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2); text-align: center; width: 100%; display: block;">Qualy{{ $company_name ?? '' }}</h1>
            @if($header_subtitle && $header_subtitle !== '')
            <p style="margin: 0; padding: 0 30px 20px; font-size: 16px; color: white; font-weight: 500; text-align: center;">{{ $header_subtitle }}</p>
            @endif
        </div>
    </div>
    
    <div class="email-container">
        
        <div class="content">
            {{ $slot }}
        </div>
        
        <div class="footer">
            <!-- Logo de l'entreprise -->
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="{{ asset('images/qualywatch-logo.png') }}" alt="Qualy{{ $company_name ?? '' }}" style="max-width: 120px; height: auto; opacity: 0.8;">
            </div>
            
            <p style="text-align: center; font-style: italic; color: #666; font-size: 14px; margin: 0;">
                "Contrôlez aujourd'hui, améliorez demain"
            </p>
        </div>
        
    </div>
</body>
</html>