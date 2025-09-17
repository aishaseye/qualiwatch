# 🎯 QualyWatch API - Système de Gestion de Feedbacks Avancé

QualyWatch est une plateforme complète de gestion de feedbacks clients avec des fonctionnalités avancées d'intelligence artificielle, de gamification et d'analyse automatique.

## ✨ Fonctionnalités Principales

### 🔐 **Authentification & Autorisation**
- Inscription multi-étapes avec vérification OTP par email
- Authentification via Laravel Sanctum
- Système de rôles : `manager`, `super_admin`
- Gestion complète des profils utilisateurs

### 📝 **Gestion de Feedbacks**
- Collecte via QR codes personnalisés
- Support multi-média (photos, vidéos, audio)
- Validation client avec liens temporaires
- Système de statuts complet (nouveau, en cours, traité, résolu...)
- Commentaires administrateur et résolutions détaillées

### 🏆 **Système de Récompenses & Badges (KaliPoints)**
- **KaliPoints** : Système de points fidélité
- Création et gestion de récompenses personnalisées
- Types de récompenses : physique, numérique, réduction, bon d'achat, événement
- Système de réclamation avec codes uniques
- Badges automatiques basés sur les milestones
- Attribution manuelle de badges VIP

### 🔔 **Notifications Automatiques**
- Notifications multi-canaux : email, SMS, push, in-app, webhook
- Templates personnalisables par entreprise et type
- Variables dynamiques dans les templates
- Système de retry automatique
- Notifications programmées

### ⚡ **Système SLA & Escalations**
- Règles SLA configurables par type de feedback
- Escalations automatiques basées sur le temps
- Suivi des violations SLA
- Métriques de performance détaillées
- Notifications d'escalation automatiques

### 🤖 **Intelligence Artificielle & Chatbot**
- Chatbot intelligent avec détection d'intention
- Support de multiples intents : salutations, plaintes, aide, informations
- Transfert automatique vers agent humain si nécessaire
- Gestion des conversations avec historique
- Réponses contextuelles personnalisées

### 🚨 **Système d'Alertes Feedback Avancé**
- **Détection automatique de feedbacks critiques**
- Analyse de sentiment en temps réel
- Détection de mots-clés critiques : "catastrophe", "désastre", "désarroi", etc.
- Classification automatique par sévérité : `low`, `medium`, `high`, `critical`, `catastrophic`
- Types d'alertes : sentiment négatif, mots-clés critiques, notes faibles, problèmes multiples, clients VIP
- Dashboard d'alertes avec statistiques
- Escalation automatique des alertes critiques
- Notifications immédiates aux managers

### 📊 **Tableaux de Bord & Statistiques**
- Statistiques complètes par entreprise
- Métriques de satisfaction client
- Analyses historiques et comparaisons
- Classements des employés
- Profils détaillés par employé
- Indicateurs de performance SLA

### 🔧 **Administration Super Admin**
- Accès multi-entreprises pour les super admins
- Statistiques globales de la plateforme
- Gestion centralisée des fonctionnalités

## 🏗️ Architecture Technique

### **Technologies Utilisées**
- **Backend** : Laravel 10 + PHP 8.2
- **Base de données** : MySQL
- **Authentification** : Laravel Sanctum
- **Stockage** : Laravel Storage (local/cloud)
- **Queue** : Laravel Queue pour les notifications
- **Cache** : Redis (optionnel)

### **Structure des Données**
```
📁 Database Schema
├── 👥 Gestion Utilisateurs
│   ├── users (managers, super_admins)
│   ├── companies (entreprises)
│   └── user_otps (codes OTP)
├── 📝 Feedbacks
│   ├── feedbacks (feedbacks clients)
│   ├── feedback_types (types de feedback)
│   ├── feedback_statuses (statuts)
│   ├── clients (clients/utilisateurs finaux)
│   └── validation_logs (logs de validation)
├── 🏢 Organisation
│   ├── services (services d'entreprise)
│   ├── employees (employés)
│   └── business_sectors (secteurs d'activité)
├── 🏆 Gamification
│   ├── rewards (récompenses)
│   ├── reward_claims (réclamations)
│   ├── badges (badges)
│   └── client_badges (attribution badges)
├── 🔔 Notifications
│   ├── notifications (notifications)
│   └── notification_templates (templates)
├── ⚡ SLA & Escalations
│   ├── sla_rules (règles SLA)
│   └── sla_escalations (escalations)
├── 🤖 Chatbot
│   ├── chatbot_conversations (conversations)
│   └── chatbot_messages (messages)
└── 🚨 Alertes
    └── feedback_alerts (alertes feedback)
```

## 🚀 Installation & Configuration

### **Prérequis**
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM (pour assets)

### **Installation**

1. **Cloner le projet**
```bash
git clone [repository-url]
cd qualywatch/backend
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configuration environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration base de données** (`.env`)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qualywatch
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Configuration email** (pour OTP)
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@qualywatch.com
MAIL_FROM_NAME="QualyWatch"
```

6. **Exécuter les migrations**
```bash
php artisan migrate --seed
```

7. **Générer les clés Sanctum**
```bash
php artisan sanctum:install
```

8. **Créer le stockage symbolique**
```bash
php artisan storage:link
```

9. **Lancer le serveur**
```bash
php artisan serve
```

## 📋 Utilisation de l'API

### **Authentification**

#### Inscription (Étape 1)
```http
POST /api/auth/register/step-1
Content-Type: application/json

{
    "email": "manager@company.com",
    "first_name": "Jean",
    "last_name": "Dupont"
}
```

#### Vérification OTP (Étape 2)
```http
POST /api/auth/verify-otp
Content-Type: application/json

{
    "email": "manager@company.com",
    "otp": "123456"
}
```

#### Finalisation inscription (Étape 3)
```http
POST /api/auth/register/step-2
Authorization: Bearer {temp_token}
Content-Type: application/json

{
    "password": "password123",
    "phone": "+33123456789"
}
```

#### Connexion
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "manager@company.com",
    "password": "password123"
}
```

### **Gestion des Feedbacks**

#### Créer un feedback (Public - via QR)
```http
POST /api/feedback/company/{companyId}
Content-Type: multipart/form-data

{
    "feedback_type_id": "uuid",
    "title": "Service excellent",
    "description": "J'ai été très satisfait du service",
    "first_name": "Marie",
    "last_name": "Martin",
    "email": "marie@email.com",
    "phone": "+33987654321",
    "kalipoints": 5,
    "service_id": "uuid",
    "employee_id": "uuid",
    "photo": file,
    "video": file,
    "audio": file
}
```

#### Lister les feedbacks (Manager)
```http
GET /api/feedbacks?type=appreciation&status=new&date_from=2024-01-01
Authorization: Bearer {token}
```

### **Système d'Alertes Feedback**

#### Lister les alertes
```http
GET /api/feedback-alerts?severity=critical&status=new
Authorization: Bearer {token}
```

#### Dashboard des alertes
```http
GET /api/feedback-alerts/dashboard/summary
Authorization: Bearer {token}
```

#### Prendre en charge une alerte
```http
PUT /api/feedback-alerts/{alertId}/acknowledge
Authorization: Bearer {token}
```

#### Résoudre une alerte
```http
PUT /api/feedback-alerts/{alertId}/resolve
Authorization: Bearer {token}
Content-Type: application/json

{
    "resolution_notes": "Problème résolu par contact direct avec le client"
}
```

### **Système de Récompenses**

#### Créer une récompense
```http
POST /api/rewards
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Café offert",
    "description": "Un café gratuit",
    "type": "physical",
    "kalipoints_cost": 10,
    "stock_quantity": 100,
    "is_active": true,
    "details": {
        "location": "Comptoir principal",
        "validity_days": 30
    }
}
```

#### Réclamation de récompense (Client)
```http
POST /api/rewards/{rewardId}/claim
Content-Type: application/json

{
    "client_email": "client@email.com",
    "client_name": "Jean Dupont"
}
```

### **Système de Notifications**

#### Envoyer une notification
```http
POST /api/notifications
Authorization: Bearer {token}
Content-Type: application/json

{
    "recipient_type": "client",
    "recipient_email": "client@email.com",
    "type": "custom",
    "channel": "email",
    "title": "Message important",
    "message": "Votre feedback a été traité"
}
```

### **Chatbot IA**

#### Démarrer une conversation
```http
POST /api/chatbot/company/{companyId}/start
Content-Type: application/json

{
    "client_identifier": "client@email.com",
    "context": "feedback"
}
```

#### Envoyer un message
```http
POST /api/chatbot/conversation/{conversationId}/message
Content-Type: application/json

{
    "message": "Bonjour, j'ai un problème avec ma commande",
    "sender_type": "client"
}
```

### **Système SLA**

#### Créer une règle SLA
```http
POST /api/sla
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "SLA Incidents Critiques",
    "feedback_type": "incident",
    "priority": "high",
    "response_time_hours": 2,
    "resolution_time_hours": 24,
    "escalation_rules": [
        {
            "trigger_after_hours": 4,
            "action": "escalate_to_manager",
            "notification_channels": ["email", "sms"]
        }
    ]
}
```

## 🎯 Fonctionnalités Avancées

### **Détection Automatique d'Alertes**

Le système analyse automatiquement chaque feedback pour détecter :

1. **Mots-clés critiques** : 
   - "catastrophe", "désastre", "désarroi"
   - "inacceptable", "scandaleux", "révoltant"
   - "urgent", "grave", "critique"

2. **Sentiment négatif** :
   - Analyse du sentiment global (-1 à 1)
   - Détection de patterns négatifs
   - Score de criticité automatique

3. **Clients VIP** :
   - Détection automatique via mots-clés
   - "directeur", "CEO", "manager", "partenaire"

4. **Escalation automatique** :
   - Alertes critiques/catastrophiques escaladées automatiquement
   - Notifications immédiates aux managers
   - Suivi du temps de résolution

### **Templates de Notifications**

Les templates supportent des variables dynamiques :

```handlebars
Bonjour {{client_name}},

Votre feedback "{{feedback_title}}" a été traité.

Détails :
- Type : {{feedback_type}}
- Statut : {{status}}
- Note : {{rating}}/5
- KaliPoints gagnés : {{kalipoints}}

Merci pour votre confiance !

{{company_name}}
```

### **Badges Automatiques**

Le système attribue automatiquement des badges basés sur :
- Nombre de feedbacks soumis
- Points KaliPoints accumulés
- Ancienneté client
- Qualité des feedbacks

## 🔧 Configuration Avancée

### **Personnalisation des Mots-clés d'Alerte**

Dans `AlertDetectionService.php`, vous pouvez personnaliser les mots-clés :

```php
private array $criticalKeywords = [
    // Français
    'catastrophe', 'désastre', 'désarroi',
    'inacceptable', 'scandaleux',
    
    // Anglais
    'disaster', 'catastrophic', 'unacceptable',
    'urgent', 'critical', 'emergency',
    
    // Vos mots-clés personnalisés
    'mot1', 'mot2'
];
```

### **Configuration des Seuils d'Alerte**

```php
// Seuils de sentiment
private const SENTIMENT_CRITICAL = -0.7;
private const SENTIMENT_HIGH = -0.5;
private const SENTIMENT_MEDIUM = -0.3;

// Seuils de note
private const RATING_CRITICAL = 2;
private const RATING_LOW = 3;
```

## 📈 Monitoring & Métriques

### **Métriques Disponibles**

1. **Alertes**
   - Nombre total d'alertes
   - Alertes non résolues
   - Temps moyen de résolution
   - Taux d'escalation

2. **Feedbacks**
   - Volume quotidien/mensuel
   - Score de satisfaction moyen
   - Répartition par type
   - Temps de traitement moyen

3. **SLA**
   - Taux de respect des SLA
   - Violations par type
   - Performance par employé

## 🚨 Gestion des Erreurs

### **Codes d'erreur communs**

- `400` - Données invalides
- `401` - Non authentifié
- `403` - Non autorisé
- `404` - Ressource introuvable
- `422` - Validation échouée
- `500` - Erreur serveur

### **Format de réponse d'erreur**
```json
{
    "success": false,
    "message": "Message d'erreur",
    "errors": {
        "field": ["Détail de l'erreur"]
    }
}
```

## 🔒 Sécurité

### **Bonnes Pratiques Implémentées**

1. **Authentification sécurisée** avec OTP
2. **Autorisation basée sur les rôles**
3. **Validation stricte des données**
4. **Protection CSRF**
5. **Sanitization des entrées**
6. **Logs d'audit complets**

### **Variables d'environnement sensibles**

```env
# Chiffrement
APP_KEY=base64:your-key-here

# JWT/Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1

# Email (ne jamais commit)
MAIL_PASSWORD=your-smtp-password

# Base de données
DB_PASSWORD=your-db-password
```

## 🧪 Tests

### **Exécuter les tests**
```bash
php artisan test
php artisan test --filter FeedbackAlertTest
```

### **Tests de l'API avec cURL**

```bash
# Test de santé
curl -X GET http://localhost:8000/api/health

# Test authentification
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Test alertes (avec token)
curl -X GET http://localhost:8000/api/feedback-alerts \
  -H "Authorization: Bearer your-token-here"
```

## 🤝 Contribution

### **Standards de Code**

1. **PSR-12** pour le style de code
2. **Documentation** obligatoire pour les méthodes publiques
3. **Tests unitaires** pour les nouvelles fonctionnalités
4. **Validation** stricte des données d'entrée

### **Structure des Commits**

```
type: description courte

[Description détaillée si nécessaire]

Fixes #issue-number
```

Types : `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

## 📞 Support

### **Logs & Debugging**

```bash
# Voir les logs Laravel
tail -f storage/logs/laravel.log

# Voir les logs d'alertes
tail -f storage/logs/laravel.log | grep "feedback.*alert"

# Debug mode
php artisan tinker
```

### **Commandes Utiles**

```bash
# Nettoyer le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Recalculer les statistiques
php artisan statistics:recalculate

# Traitement des notifications
php artisan queue:work

# Maintenance
php artisan down
php artisan up
```

## 🔄 Mise à Jour

### **Processus de Déploiement**

1. **Backup de la base**
```bash
mysqldump -u user -p qualywatch > backup-$(date +%Y%m%d).sql
```

2. **Mise à jour du code**
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

3. **Migrations**
```bash
php artisan migrate --force
```

4. **Cache & Assets**
```bash
php artisan config:cache
php artisan route:cache
npm run build
```

---

## 📋 Changelog

### **Version 2.0.0 - Fonctionnalités Avancées**
- ✅ Système d'alertes feedback automatique
- ✅ Chatbot IA avec détection d'intention
- ✅ Système SLA et escalations
- ✅ Notifications multi-canaux
- ✅ Gamification KaliPoints
- ✅ Dashboard analytics avancé

### **Version 1.0.0 - Base**
- ✅ Gestion de feedbacks
- ✅ Authentification OTP
- ✅ QR Codes personnalisés
- ✅ Gestion multi-entreprises

---

**Développé avec ❤️ pour une meilleure expérience client**

*Pour plus d'informations, consultez la documentation technique ou contactez l'équipe de développement.*#   q u a l i w a t c h  
 