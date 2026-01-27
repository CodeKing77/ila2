# 🔧 Résumé Technique - Système de Validation par Email

## 📊 Architecture du Système

```
┌─────────────────────────────────────────────────────────────┐
│                   WORKFLOW COMPLET                           │
└─────────────────────────────────────────────────────────────┘

1. INSCRIPTION
   ├── inscription_professeur_cl.php
   │   ├── Validation des données
   │   ├── INSERT dans professeurs (is_active=0)
   │   ├── Génération token (64 chars, random_bytes)
   │   ├── Expiration = NOW() + 48h
   │   └── Envoi email avec lien
   │
2. EMAIL
   ├── Lien: SITE_URL/pages/valider_email.php?token=XXX
   └── Contenu HTML responsive
   │
3. VALIDATION
   ├── valider_email.php
   │   ├── Vérification token existe
   │   ├── Vérification non expiré
   │   ├── UPDATE is_active=1
   │   ├── Suppression token
   │   └── email_verified_at = NOW()
   │
4. RENVOI (optionnel)
   └── renvoyer_validation.php
       ├── Rate limiting (1/heure)
       ├── Nouveau token
       └── Nouvel email
```

---

## 🗄️ Structure de la Base de Données

### Table: `professeurs`

```sql
+---------------------+---------------+--------+-------+
| Colonne             | Type          | Null   | Clé   |
+---------------------+---------------+--------+-------+
| id                  | INT           | NO     | PRI   |
| nom_complet         | VARCHAR(255)  | NO     |       |
| email_academique    | VARCHAR(191)  | NO     | UNI   |
| password_hash       | VARCHAR(255)  | NO     |       |
| is_active           | TINYINT(1)    | YES    |       | ← 0 = non validé
| validation_token    | VARCHAR(64)   | YES    | MUL   | ← Token unique
| token_expiration    | DATETIME      | YES    |       | ← Date limite
| email_verified_at   | DATETIME      | YES    |       | ← Date validation
| created_at          | TIMESTAMP     | YES    |       |
| updated_at          | TIMESTAMP     | YES    |       |
+---------------------+---------------+--------+-------+
```

### Index ajoutés

```sql
CREATE INDEX idx_validation_token ON professeurs(validation_token);
```

**Raison** : Accélérer la recherche lors de la validation (requête fréquente)

---

## 🔐 Sécurité Implémentée

### 1. Génération du Token

```php
function generateValidationToken() {
    return bin2hex(random_bytes(32)); // 64 caractères hexadécimaux
}
```

**Entropie** : 256 bits (2^256 combinaisons possibles)  
**Collision** : Quasi impossible  
**Cryptographiquement sûr** : Oui (`random_bytes`)

### 2. Expiration du Token

```php
$token_expiration = date('Y-m-d H:i:s', strtotime('+48 hours'));
```

**Durée** : 48 heures  
**Raison** : Balance entre commodité utilisateur et sécurité

### 3. Token à Usage Unique

```sql
UPDATE professeurs 
SET validation_token = NULL, 
    token_expiration = NULL 
WHERE id = ?;
```

Le token est **supprimé** après utilisation (impossible de réutiliser)

### 4. Protection SQL Injection

```php
$stmt = $pdo->prepare("SELECT * FROM professeurs WHERE validation_token = ?");
$stmt->execute([$token]);
```

**Requêtes préparées** utilisées partout

### 5. Rate Limiting (Anti-spam)

```php
if ($time_since_last < 3600) {
    // Refuser : Maximum 1 email/heure
}
```

---

## 📧 Configuration Email

### Option 1 : PHP mail() natif

**Avantages** :
- Aucune dépendance
- Simple à configurer

**Inconvénients** :
- Souvent bloqué par les filtres anti-spam
- Pas de retry automatique
- Pas de tracking

**Configuration** :
```php
mail($to, $subject, $message, $headers);
```

### Option 2 : SMTP avec PHPMailer (RECOMMANDÉ)

**Avantages** :
- Meilleur délivrabilité
- Support authentification
- Gestion d'erreurs avancée

**Installation** :
```bash
composer require phpmailer/phpmailer
```

**Configuration** :
```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'votre@email.com';
$mail->Password = 'mot-de-passe-app';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

### Option 3 : Services tiers

| Service    | Gratuit        | Prix       | Délivrabilité |
|------------|----------------|------------|---------------|
| SendGrid   | 100/jour       | $19.95/mois| ⭐⭐⭐⭐⭐    |
| Mailgun    | 5000/mois      | $35/mois   | ⭐⭐⭐⭐⭐    |
| AWS SES    | 62000/mois     | $0.10/1000 | ⭐⭐⭐⭐      |
| Gmail SMTP | 500/jour       | Gratuit    | ⭐⭐⭐        |

---

## 🧪 Tests à Effectuer

### 1. Test du flux complet

```bash
1. S'inscrire avec une vraie adresse email
2. Vérifier l'email reçu
3. Cliquer sur le lien
4. Vérifier que is_active = 1 dans la BDD
5. Vérifier que le token a été supprimé
```

### 2. Test des cas d'erreur

```sql
-- Token invalide
UPDATE professeurs SET validation_token = 'fake_token' WHERE id = 1;
-- Tester la validation → Devrait échouer

-- Token expiré
UPDATE professeurs 
SET token_expiration = '2020-01-01 00:00:00' 
WHERE id = 1;
-- Tester la validation → Devrait afficher "Lien expiré"

-- Compte déjà validé
UPDATE professeurs SET is_active = 1 WHERE id = 1;
-- Tester la validation → Devrait afficher "Déjà validé"
```

### 3. Test du rate limiting

```bash
1. Demander un nouveau lien
2. Immédiatement redemander un nouveau lien
3. Devrait refuser et afficher "Attendez X minutes"
```

---

## 📊 Monitoring et Logs

### Logs recommandés

```php
// email_validation.log
2026-01-19 14:30:15 | ID: 123 | EMAIL_SENT | Token: abc123... → prof@email.com
2026-01-19 14:35:22 | ID: 123 | VALIDATED | IP: 192.168.1.1
2026-01-19 15:00:00 | ID: 124 | EXPIRED | Token: def456...
2026-01-19 15:05:10 | ID: 125 | RESENT | Nouveau token généré
```

### Requêtes SQL utiles

```sql
-- Comptes en attente de validation
SELECT COUNT(*) FROM professeurs 
WHERE is_active = 0 AND token_expiration > NOW();

-- Tokens expirés
SELECT COUNT(*) FROM professeurs 
WHERE is_active = 0 AND token_expiration < NOW();

-- Taux de validation (derniers 30 jours)
SELECT 
    COUNT(*) as total_inscriptions,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as validés,
    ROUND(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as taux_validation
FROM professeurs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## ⚡ Optimisations Possibles

### 1. Queue de mails (Async)

Au lieu d'envoyer l'email immédiatement :

```php
// Ajouter à une table de queue
INSERT INTO email_queue (recipient, subject, message, created_at) 
VALUES (?, ?, ?, NOW());

// Cronjob séparé pour envoyer les emails
// */5 * * * * php /path/to/send_email_queue.php
```

**Avantages** :
- Ne bloque pas l'inscription
- Retry automatique en cas d'échec
- Meilleure scalabilité

### 2. Cache des tokens validés

```php
// Redis ou Memcached
$redis->setex("validated_token_" . $token, 3600, 1);

// Vérification rapide
if ($redis->exists("validated_token_" . $token)) {
    // Déjà validé, pas besoin de query SQL
}
```

### 3. Compression des tokens

Tokens de 64 chars → Base64URL pour URLs plus courtes

```php
function generateShortToken() {
    return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    // 32 caractères au lieu de 64
}
```

---

## 🐛 Debugging

### Vérifier qu'un email a été envoyé

```php
$result = mail($to, $subject, $message, $headers);
if (!$result) {
    error_log("Mail non envoyé : " . error_get_last()['message']);
}
```

### Afficher les erreurs SMTP

```php
$mail->SMTPDebug = 2; // 0=off, 1=client, 2=client+server
$mail->Debugoutput = 'html';
```

### Tester la fonction mail()

```bash
php -r "var_dump(mail('test@example.com', 'Test', 'Test message'));"
```

---

## 📈 Statistiques Recommandées

### Dashboard Admin (à créer)

```
┌─────────────────────────────────────────┐
│  📊 Validations Email - Derniers 7 jours│
├─────────────────────────────────────────┤
│  Total inscriptions        : 50         │
│  Validés                   : 42 (84%)   │
│  En attente                : 5  (10%)   │
│  Expirés                   : 3  (6%)    │
│                                          │
│  Temps moyen de validation : 3h 24min   │
│  Taux d'ouverture email    : 92%        │
└─────────────────────────────────────────┘
```

---

## 🚀 Déploiement en Production

### Checklist avant mise en ligne

- [ ] Modifier SITE_URL vers le domaine réel
- [ ] Configurer SMTP de production
- [ ] Activer HTTPS (certificat SSL)
- [ ] Tester l'envoi d'email
- [ ] Configurer les logs
- [ ] Mettre en place monitoring
- [ ] Backup de la BDD
- [ ] Documentation pour l'équipe

### Variables d'environnement (.env)

```bash
# Ne jamais commiter ce fichier !
DB_HOST=localhost
DB_NAME=ila_publications_db
DB_USER=root
DB_PASS=votre_mot_de_passe_secret

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=noreply@ila.edu
SMTP_PASS=mot_de_passe_app

SITE_URL=https://www.ila.edu
```

---

## 📞 Support Développeur

**Contact** : dev@ila.edu  
**Documentation complète** : `/docs/email-validation/`  
**Dernière mise à jour** : 19 janvier 2026