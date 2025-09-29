# Intégration WhatsApp - QualyWatch

## Vue d'ensemble

Cette intégration permet d'envoyer des messages WhatsApp aux clients lorsque leurs feedbacks négatifs sont traités, en complément des emails existants. Les clients reçoivent un message interactif avec des liens pour valider si leur problème a été résolu.

## Architecture

### Fichiers créés/modifiés

1. **Service WhatsApp** : `app/Services/WhatsAppService.php`
2. **Configuration** : `config/whatsapp.php`
3. **Variables d'environnement** : `.env.example` (mis à jour)
4. **Service de notification** : `app/Services/NotificationService.php` (modifié)
5. **Contrôleur de test** : `app/Http/Controllers/WhatsAppTestController.php`
6. **Routes de test** : `routes/api.php` (nouvelles routes `/api/whatsapp-test/*`)

### Workflow d'intégration

1. **Déclenchement** : Quand un feedback passe au statut "treated" dans `FeedbackObserver.php:43-44`
2. **Notification** : `NotificationService::sendTreatedNotification()` vérifie s'il y a un email ET/OU un téléphone
3. **Envoi dual** :
   - Email classique (si email disponible)
   - Message WhatsApp (si téléphone disponible et service activé)

## Configuration

### 1. Variables d'environnement

Ajoutez ces variables à votre fichier `.env` :

```env
# WhatsApp Configuration (via Facebook Graph API)
WHATSAPP_ENABLED=false
WHATSAPP_ACCESS_TOKEN=your_whatsapp_access_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_WEBHOOK_URL=
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
```

### 2. Configuration Facebook Business

#### Étape 1 : Créer une application Facebook Business
1. Allez sur [Facebook for Developers](https://developers.facebook.com/)
2. Créez une nouvelle application Business
3. Ajoutez le produit "WhatsApp Business API"

#### Étape 2 : Configurer WhatsApp Business API
1. Dans votre app Facebook, allez dans "WhatsApp" > "Getting Started"
2. Ajoutez un numéro de téléphone WhatsApp Business
3. Notez votre `PHONE_NUMBER_ID`
4. Génèrez un token d'accès permanent

#### Étape 3 : Obtenir le token d'accès permanent
1. Génèrez un token temporaire dans l'interface Facebook
2. Convertissez-le en token permanent via l'API Graph
3. Stockez-le dans `WHATSAPP_ACCESS_TOKEN`

### 3. Activation du service

```env
WHATSAPP_ENABLED=true
```

## Utilisation

### Fonctionnement automatique

Une fois configuré, le système envoie automatiquement des messages WhatsApp :

1. **Condition** : Feedback négatif (type "negatif" ou "incident") passe au statut "treated"
2. **Vérification** : Client a un numéro de téléphone renseigné
3. **Message** : Envoi automatique d'un message avec liens de validation

### Format du message

```
🎯 *Nom de l'entreprise*

Bonjour [Nom du client],

✅ Bonne nouvelle ! Votre feedback *[REF]* concernant "[Titre]" a été traité par notre équipe.

📋 *Résolution apportée :*
[Description de la résolution]

💬 *Votre avis nous intéresse !*
Pouvez-vous nous confirmer si le problème a été résolu ?

👆 Cliquez sur l'un des liens ci-dessous :

✅ *PROBLÈME RÉSOLU* :
[URL de validation]

❌ *PROBLÈME NON RÉSOLU* :
[URL de validation]

⏰ *Important :* Ce lien expire dans 48h

Merci de votre confiance ! 🙏
_L'équipe [Nom de l'entreprise]_
```

## Tests et débogage

### Routes de test disponibles

Toutes les routes nécessitent une authentification (`auth:sanctum`) :

```bash
# Vérifier le statut du service
GET /api/whatsapp-test/status

# Tester la connexion à l'API Facebook
GET /api/whatsapp-test/connection

# Tester avec un feedback existant
POST /api/whatsapp-test/feedback/{feedback_id}

# Tester avec des données d'exemple
POST /api/whatsapp-test/sample
{
    "phone_number": "+33123456789",
    "company_id": "optional_company_id"
}

# Informations de débogage
GET /api/whatsapp-test/debug
```

### Exemples d'appels de test

```bash
# Test de statut
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/whatsapp-test/status

# Test avec données d'exemple
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"phone_number": "+33123456789"}' \
     http://localhost:8000/api/whatsapp-test/sample
```

### Vérification des logs

Les messages WhatsApp sont loggés dans les fichiers de log Laravel :

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log | grep -i whatsapp
```

## Format des numéros de téléphone

Le service accepte et formate automatiquement les numéros :

- **Format français** : `0123456789` → `+33123456789`
- **Format international** : `+33123456789` (inchangé)
- **Autres formats** : Nettoyage automatique des espaces et caractères spéciaux

## Gestion des erreurs

### Erreurs courantes

1. **Service désactivé** : `WHATSAPP_ENABLED=false`
2. **Token invalide** : Vérifiez `WHATSAPP_ACCESS_TOKEN`
3. **Numéro incorrect** : Vérifiez `WHATSAPP_PHONE_NUMBER_ID`
4. **Numéro client invalide** : Format de téléphone non reconnu

### Codes de retour

- **200** : Message envoyé avec succès
- **400** : Numéro de téléphone invalide
- **401** : Token d'accès invalide
- **404** : Numéro WhatsApp Business non trouvé
- **500** : Erreur interne du service

## Sécurité

### Bonnes pratiques

1. **Token sécurisé** : Ne jamais exposer le token d'accès
2. **Webhook sécurisé** : Utilisez `WHATSAPP_WEBHOOK_VERIFY_TOKEN` pour valider les webhooks
3. **Limitation** : Les messages ne sont envoyés que pour les feedbacks "treated"
4. **Format validé** : Validation stricte du format des numéros de téléphone

### Variables sensibles

```env
# ⚠️ Variables sensibles - ne jamais commiter
WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxxxx...  # Token permanent Facebook
WHATSAPP_WEBHOOK_VERIFY_TOKEN=votre_token_secret
```

## Limitations

1. **Quota API** : Facebook impose des limites sur le nombre de messages
2. **Numéros vérifiés** : Seuls les numéros ajoutés à votre WhatsApp Business peuvent recevoir des messages (en mode test)
3. **Templates** : En production, Facebook exige l'approbation de templates de messages
4. **Coût** : L'envoi de messages WhatsApp peut être facturé par Facebook

## Support et maintenance

### Monitoring

- Surveillez les logs pour les échecs d'envoi
- Vérifiez régulièrement la validité du token d'accès
- Monitorer les quotas d'API Facebook

### Mise à jour

- Le token d'accès peut expirer, renouvelez-le régulièrement
- Surveillez les changements d'API Facebook Graph
- Testez régulièrement l'envoi de messages

## Roadmap

Améliorations futures possibles :

1. **Templates approuvés** : Utiliser des templates Facebook approuvés
2. **Webhooks entrants** : Gérer les réponses des clients
3. **Messages multimedia** : Support d'images et documents
4. **Chatbot intégré** : Réponses automatiques aux questions
5. **Analytics** : Statistiques d'ouverture et de réponse

---

**Note** : Cette intégration est conçue pour complémenter le système d'email existant, pas le remplacer. Les clients peuvent recevoir à la fois un email ET un message WhatsApp selon leurs informations de contact disponibles.