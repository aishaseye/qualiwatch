<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration WhatsApp Business API via Facebook Graph API
    |
    */

    // Activer/désactiver le service WhatsApp
    'enabled' => env('WHATSAPP_ENABLED', false),

    // URL de l'API Facebook Graph API
    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),

    // Token d'accès permanent pour l'API WhatsApp Business
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    // ID du numéro de téléphone WhatsApp Business
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    // Webhook pour recevoir les réponses (optionnel)
    'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),

    // Templates de messages
    'templates' => [
        'feedback_treated' => [
            'greeting' => '🎯 *{company_name}*',
            'intro' => 'Bonjour {client_name},',
            'resolution_message' => '✅ Bonne nouvelle ! Votre feedback *{feedback_reference}* concernant "{feedback_title}" a été traité par notre équipe.',
            'resolution_details' => '📋 *Résolution apportée :*\n{admin_resolution}',
            'feedback_request' => '💬 *Votre avis nous intéresse !*\nPouvez-vous nous confirmer si le problème a été résolu ?',
            'action_buttons' => '👆 Cliquez sur l\'un des liens ci-dessous :',
            'resolved_button' => '✅ *PROBLÈME RÉSOLU* :\n{resolved_url}',
            'not_resolved_button' => '❌ *PROBLÈME NON RÉSOLU* :\n{not_resolved_url}',
            'expiry_notice' => '⏰ *Important :* Ce lien expire dans 48h',
            'signature' => 'Merci de votre confiance ! 🙏\n_L\'équipe {company_name}_'
        ],
    ],

    // Configuration des timeouts
    'timeout' => [
        'connect' => 10, // secondes
        'request' => 30, // secondes
    ],

    // Retry configuration
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000, // millisecondes
    ],

    // Logging
    'log' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'channel' => 'whatsapp', // nom du channel de log
    ],
];