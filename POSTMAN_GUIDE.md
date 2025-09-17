# 📋 Guide Complet Postman - QualyWatch API

## 🔄 WORKFLOWS PRATIQUES POSTMAN

### 🎯 Workflow 1: Inscription Complète d'une Entreprise
```
📂 Dossier: 🔐 Authentication

1. 📝 "Inscription Étape 1" 
   └── Envoyer email/nom → Reçoit OTP

2. ✉️ "Vérification OTP"
   └── Code depuis logs → Token temporaire sauvé

3. 📋 "Inscription Étape 2 - Informations Entreprise"  
   └── Mot de passe + données entreprise

4. 📸 "Inscription Étape 3 - Upload Photos"
   └── Photo gérant + logo entreprise

5. 🚪 "Connexion"
   └── Email + mot de passe → Token auth sauvé
```

### 📝 Workflow 2: Cycle de Vie d'un Feedback Négatif
```
📂 Dossier: 📝 Gestion Feedbacks

1. ✏️ "Créer Feedback (Public)" 
   └── rating: 1, type: négatif → Status: "new" (ID:1)

2. 👁️ "Marquer comme Vu"
   └── status_id: 2 → Status: "seen" (vu par admin)

3. 🔄 "Mettre à Jour Statut" 
   └── status_id: 3 → Status: "in_progress" (pris en charge)

4. 🔄 "Mettre à Jour Statut"
   └── status_id: 4 + résolution → Status: "treated" (traité)

5. 📨 "Générer Token Validation"
   └── Email automatique au client → Token généré

📂 Dossier: 📧 Validation Client (Public)

6. 📋 "Formulaire Validation"
   └── GET avec token → Voir détails

7. ✅ "Valider Feedback"  
   └── Status final automatique selon satisfaction
```

### 🚨 Workflow 3: Gestion d'une Alerte Critique
```
📂 Dossier: 📝 Gestion Feedbacks

1. ✏️ "Créer Feedback (Public)"
   └── Description: "catastrophe" → Alerte auto-créée

📂 Dossier: 🚨 Système d'Alertes Feedback

2. 📊 "Dashboard Alertes"
   └── Voir nouvelles alertes

3. 🔍 "Lister Alertes" 
   └── Filtrer par sévérité: "critical"

4. ✋ "Prendre en Charge Alerte"
   └── Status: "acknowledged"

5. ✅ "Résoudre Alerte"
   └── Notes de résolution → Status: "resolved"
```

### 📊 Référence Status ID (IMPORTANT!)
```
1  = new                    (nouveau feedback)
2  = seen                   (vu par admin) ✨ NOUVEAU
3  = in_progress           (en cours de traitement)
4  = treated               (traité, solution appliquée)
5  = resolved              (résolu - feedback négatif)
6  = partially_resolved    (partiellement résolu)
7  = not_resolved          (non résolu)
8  = implemented           (implémenté - suggestion)
9  = partially_implemented (partiellement implémenté)
10 = rejected              (rejeté - suggestion)
11 = archived              (archivé - feedback positif)
```

## 🎯 Applications Disponibles

**QualyWatch propose 2 applications distinctes :**

### 1. 👑 Application Super Admin (Développeurs)
- Accès global à toutes les entreprises
- Statistiques plateforme complètes
- Supervision globale du système
- **Accès :** Comptes super admin uniquement

### 2. 🏢 Application Client (Gérants d'entreprise)
- Accès uniquement à leur entreprise
- Dashboard personnalisé
- Gestion employés/services/feedbacks
- **Accès :** Inscription normale

---

## 🚀 Configuration Initiale

### 1. Import de la Collection
1. Ouvrez Postman
2. Cliquez sur **Import** 
3. Sélectionnez le fichier `QualyWatch_API_v2.postman_collection.json`
4. Importez également `QualyWatch_Environment.postman_environment.json`

### 2. Configuration de l'Environnement
1. Sélectionnez l'environnement **QualyWatch - Environment**
2. Modifiez `base_url` si nécessaire (par défaut: `http://localhost:8000/api`)
3. Les autres variables seront remplies automatiquement

### 3. Configuration Email OTP (Important!)
En mode développement, les emails OTP sont sauvés dans les logs :
```bash
# Pour voir l'OTP après inscription
tail -f backend/storage/logs/laravel.log | grep -A 10 -B 10 "verification"
```
**Alternative:** Ouvrez `backend/storage/logs/laravel.log` et cherchez le code 4 chiffres dans le HTML.

### 4. Création d'un Compte Super Admin (Développeurs)
Pour accéder aux endpoints Super Admin, vous devez créer un compte super admin :
```bash
# Créer un super admin
cd backend
php artisan create:super-admin --email="admin@qualywatch.com" --password="password123" --first-name="Admin" --last-name="QualyWatch"

# Ou de manière interactive
php artisan create:super-admin
```

**Connexion Super Admin :**
```json
{
    "identifier": "admin@qualywatch.com",
    "password": "password123"
}
```

⚠️ **Important** : Les super admins n'ont pas d'entreprise associée, ils ont accès global à toutes les entreprises.

---

## 📱 Tests par Module

### 🔐 Module 1: AUTHENTIFICATION

#### Premier Test - Inscription Complète avec OTP
```bash
1. 📝 "Inscription - Étape 1" 
   → Créé le compte utilisateur
   → OTP 4 chiffres envoyé (logs ou email)
   → Retourne user_id et next_step: "email_verification"

2. ✅ "Vérifier OTP"
   → Saisissez le code 4 chiffres reçu
   → Email vérifié automatiquement
   → Retourne token de registration + next_step: "company_info"
   → Copiez le token dans {{auth_token}}

3. 🏢 "Inscription - Étape 2"
   → Utilise le token de registration
   → Retourne company_id + next_step: "media_upload"

4. 📸 "Inscription - Étape 3"  
   → Upload des médias (optionnel)
   → Retourne token final d'authentification
   → Copiez le token final dans {{auth_token}}

5. 🔑 "Connexion"
   → Test avec email/password créés
   → Alternative au processus d'inscription
```

#### Tests de Vérification OTP Email
```bash
1. 📧 "✅ Vérifier OTP" 
   → Code 4 chiffres envoyé par email (ex: 1234)
   → Vérifiez storage/logs/laravel.log pour voir l'OTP
   → Expire après 10 minutes
   → Nécessaire pour continuer l'inscription

2. 🔁 "Renvoyer OTP"
   → Génère un nouveau code si pas reçu
   → Invalide l'ancien code
   → Nouveau délai de 10 minutes

3. 🚪 "Déconnexion"
   → Révoque le token actuel
```

**💡 Conseil :** Utilisez la vérification double pour débloquer toutes les fonctionnalités premium !

### 👑 Module Super Admin (Développeurs uniquement)

#### Workflow Super Admin
```bash
1. 🔑 "Connexion" (Super Admin)
   → Utilisez les identifiants super admin créés
   → Notez que la réponse n'inclut pas "company" (normal)
   → Token valide pour tous les endpoints super admin

2. 📊 "Dashboard Global"
   → Statistiques de toutes les entreprises
   → Entreprises récemment créées
   → Feedbacks récents toutes entreprises confondues
   → Timeline globale des derniers mois

3. 🏢 "Toutes les entreprises"
   → Liste paginée de toutes les entreprises
   → Inclut manager, services, employés de chaque entreprise
   → Pagination sur 20 entreprises par page

4. 🔍 "Détails d'une entreprise"
   → Vue détaillée d'une entreprise spécifique
   → Timeline sur 6 derniers mois
   → Score de satisfaction calculé
   → Tous les feedbacks avec relations

5. 📈 "Statistiques globales"
   → Stats plateforme complètes
   → Croissance mensuelle
   → Top 5 entreprises par nombre de feedbacks
   → Timeline sur 12 derniers mois
```

#### Tests de Sécurité Super Admin
```bash
1. Test avec token manager normal:
   → Doit retourner 403 "Accès refusé. Privilèges super admin requis."

2. Test sans token:
   → Doit retourner 401 "Non authentifié"

3. Test avec token super admin valide:
   → Accès complet à tous les endpoints
```

**🔐 Sécurité :** Les routes super admin sont protégées par le middleware `super_admin` qui vérifie le rôle de l'utilisateur.

---

### 👥 Module 2: GESTION DES UTILISATEURS

#### Workflow de Test
```bash
1. 📋 "Liste des utilisateurs"
   → Vérifiez la pagination
   → Testez les filtres (role, service_id)

2. ➕ "Créer un utilisateur"
   → Créez un employé de test
   → Notez son ID pour les tests suivants

3. 👤 "Mon profil"
   → Vérifiez vos données personnelles
   → Consultez vos statistiques

4. ✏️ "Modifier utilisateur"
   → Modifiez les informations de l'utilisateur créé
```

**💡 Astuce :** Gardez plusieurs user_id en variables pour tester les interactions

---

### 🏢 Module 3: GESTION DES ENTREPRISES

#### Tests Essentiels
```bash
1. 🏢 "Détails de l'entreprise"
   → Vérifiez les informations
   → Notez les services disponibles

2. 🏗️ "Créer un service"
   → Créez "Service Client", "Support Technique"
   → Notez les service_id

3. 📋 "Liste des services"
   → Vérifiez que vos services apparaissent
```

---

### 📝 Module 4: GESTION DES FEEDBACKS (CRUCIAL)

#### Tests Complets par Type

##### Feedback Positif
```json
{
    "client_name": "Sophie Martin",
    "client_email": "sophie@example.com",
    "employee_id": "{{user_id}}",
    "feedback_type_id": "positif_type_id",
    "title": "Service excellent",
    "message": "Très satisfaite du service reçu !",
    "rating": 5,
    "sentiment": "very_satisfied",
    "source": "website",
    "tags": ["excellent", "rapide"]
}
```

##### Feedback Négatif
```json
{
    "client_name": "Pierre Durand",
    "client_email": "pierre@example.com", 
    "employee_id": "{{user_id}}",
    "feedback_type_id": "negatif_type_id",
    "title": "Problème de service",
    "message": "Temps d'attente trop long",
    "rating": 2,
    "sentiment": "unsatisfied",
    "priority": 4,
    "source": "phone"
}
```

##### Incident Critique
```json
{
    "client_name": "Marie Dubois",
    "client_email": "marie@example.com",
    "employee_id": "{{user_id}}",
    "feedback_type_id": "incident_type_id", 
    "title": "Panne système critique",
    "message": "Le système est complètement indisponible",
    "priority": 5,
    "sentiment": "very_unsatisfied",
    "source": "email"
}
```

#### Workflow de Traitement
```bash
1. 📝 "Créer un feedback" (testez les 3 types)
2. 👀 "Détails d'un feedback" (vérifiez le calcul des KaliPoints)
3. 🔄 "Modifier le statut" :
   → "open" → "in_progress" → "resolved"
   → Testez les notifications automatiques
4. 📎 "Ajouter des médias" (optionnel)
```

---

### 📊 Module 5: STATISTIQUES

#### Tests Dashboard
```bash
1. 📊 "Dashboard principal"
   → Period: daily, weekly, monthly
   → Vérifiez les KPIs

2. 👤 "Statistiques par employé"
   → Utilisez différents user_id
   → Activez compare=true

3. 🏢 "Statistiques par service"
   → Comparez les performances

4. 💭 "Analyse de sentiment"
   → Granularité daily/weekly
```

---

### 🎮 Module 5: SYSTÈME KALIPOINTS (GAMIFICATION SIMPLE)

#### Qu'est-ce que c'est ?
Le système KaliPoints récompense les clients qui laissent des feedbacks :
- **1 à 5 points** selon leur note de satisfaction (obligatoire)
- Les points s'accumulent dans leur profil client
- Plus simple qu'un système de badges complexe

#### Tests KaliPoints
```bash
1. 📝 "Créer un feedback avec KaliPoints"
   → Utilisez "kalipoints": 4 dans votre feedback
   → Vérifiez que le client reçoit bien 4 points
   → Points visibles dans la réponse "total_kalipoints"

2. 👤 "Voir profil client" 
   → Cherchez dans les détails d'un feedback
   → Section "client" → "total_kalipoints"
   → Vérifiez l'accumulation des points

3. 📊 "Statistiques des points"
   → Dashboard → Regardez les métriques clients
   → Total des KaliPoints distribués
```

#### Exemple Pratique
```json
{
    "feedback_type_id": "9fce4bff-9da7-4379-86a0-e273dec6ba6f",
    "kalipoints": 5,
    "title": "Service excellent",
    "description": "Très satisfait !",
    "first_name": "Marie",
    "last_name": "Dubois",
    "email": "marie@test.com"
}
```
→ Le client Marie recevra 5 KaliPoints automatiquement

---

### 🔧 Module 6: ADMINISTRATION SIMPLE

#### Ce qui existe vraiment dans l'API
```bash
1. 🎯 "Gestion des Services"
   → Créer/modifier/supprimer les services de l'entreprise
   → Chaque service a une couleur et une icône

2. 👥 "Gestion des Employés" 
   → Ajouter/modifier les employés
   → Import Excel disponible
   → Associer employés aux services

3. 📊 "Statistiques Avancées"
   → Comparaison des services
   → Ranking des employés  
   → Historique de l'entreprise
   → Résumé global

4. 🏢 "Profil de l'Entreprise"
   → Modifier informations de base
   → Upload logo
   → Générer QR code pour feedbacks
```

#### Tests Administration Concrets
```bash
1. ➕ Créer un service "Accueil"
2. ➕ Créer un employé "Jean Dupont" 
3. 📝 Créer un feedback pour Jean
4. 📊 Voir les stats de Jean dans le dashboard
5. 🔄 Changer le statut du feedback
```

---

### 🔧 Module 9: ADMINISTRATION

#### Tests Admin (Rôle Admin Requis)
```bash
1. ⚙️ "Configuration générale"
2. 🎮 "Lancer calculs gamification"
3. 📈 "Statistiques système"
```

---

## 🎯 Scénarios de Test Réels (QualyWatch)

### Scénario 1: Parcours Client Complet
```bash
1. 📝 Créer un feedback positif avec 4 KaliPoints
   POST /api/feedback/company/{companyId} (form-data)

2. ✅ Vérifier que le client reçoit les points
   → Regarder "total_kalipoints" dans la réponse

3. 🔄 Manager change le statut en "in_progress" 
   PUT /api/feedbacks/{id}/status

4. 📊 Voir les stats mises à jour dans le dashboard
   GET /api/dashboard/overview
```

### Scénario 2: Gestion d'Entreprise Complète
```bash
1. ➕ Créer un service "Accueil"
   POST /api/services

2. 👤 Ajouter un employé "Jean Dupont"
   POST /api/employees

3. 📝 Créer un feedback pour Jean
   POST /api/feedback/company/{id} (avec employee_id)

4. 📊 Voir les stats de Jean
   GET /api/dashboard/employee/{employeeId}

5. 🏢 Générer QR code entreprise
   POST /api/company/generate-qr
```

### Scénario 3: Supervision Super Admin
```bash
1. 🔑 Se connecter en tant que super admin
   POST /api/auth/login

2. 📊 Voir le dashboard global
   GET /api/super-admin/dashboard

3. 🏢 Lister toutes les entreprises
   GET /api/super-admin/companies

4. 🔍 Analyser une entreprise spécifique  
   GET /api/super-admin/companies/{id}

5. 📈 Consulter les stats globales
   GET /api/super-admin/statistics
```

---

## 📊 Variables d'Environnement à Surveiller

### Variables Critiques
- `auth_token` : Toujours valide après login
- `company_id` : Cohérent dans tous les tests
- `user_id` : Utilisateur connecté actuel

### Variables de Test QualyWatch
- `feedback_id` : Copier après création feedback
- `service_id` : Depuis liste services  
- `employee_id` : Depuis liste employés
- `company_id` : ID de l'entreprise (auto-rempli)

---

## ⚠️ Points d'Attention

### 🔒 Sécurité
- Token expire après inactivité
- Super admins ont accès global, managers sont limités à leur entreprise
- Middleware `super_admin` protège les routes sensibles

### 📈 Performance  
- Pagination sur listes importantes (20 items par page)
- Filtres disponibles sur la plupart des endpoints
- Form-data requis pour les uploads de fichiers

### 🎮 KaliPoints Simple
- Points obligatoires de 1 à 5 pour chaque feedback
- Accumulation automatique dans le profil client
- Visible dans les réponses API

---

## 🆘 Dépannage QualyWatch

### Erreur 401 - Non Autorisé
```bash
→ Token expiré → Se reconnecter
→ Super admin requis → Utiliser compte super admin
→ Format Bearer Token incorrect
```

### Erreur 422 - Validation échouée
```bash
→ Champ "kalipoints" obligatoire (1-5)
→ feedback_type_id requis
→ Vérifier format form-data pour uploads
```

### Erreur 403 - Accès refusé
```bash
→ Role super_admin requis pour routes /super-admin/*  
→ Manager ne peut voir que sa propre entreprise
```

---

## 🎉 Checklist QualyWatch ✅

### Fonctionnalités Testées
- [ ] **Inscription** : 3 étapes avec OTP email
- [ ] **Authentification** : Login manager et super admin  
- [ ] **Feedbacks** : Création avec form-data et fichiers
- [ ] **KaliPoints** : Attribution 1-5 points obligatoire
- [ ] **Gestion** : Services, employés, profil entreprise
- [ ] **Dashboard** : Stats manager et dashboard super admin
- [ ] **Super Admin** : 4 endpoints surveillance globale
- [ ] **Sécurité** : Middleware et permissions testés

### Ce qui fonctionne maintenant
- **API QualyWatch** : Système de feedback avec form-data
- **2 Applications** : Super Admin (global) + Client (entreprise)  
- **KaliPoints** : 1-5 points selon satisfaction client
- **Sécurité** : Rôles et permissions appropriés

---

**🚀 Vous êtes maintenant prêt à tester l'intégralité de l'API QualyWatch !**

*Commencez par les modules d'authentification et feedbacks, puis explorez la gamification pour voir la magie opérer ! 🎮*