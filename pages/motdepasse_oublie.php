<?php
/**
 * Réinitialisation du mot de passe
 * Fichier: page/motdepasse_oublie.php
 */

require_once '../config/config.php';

// Démarrer la session
startSecureSession();

// Vérifier que la requête est en POST
/*if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Méthode non autorisée');
}*/

// Récupérer l'action (demande de réinitialisation ou changement de mot de passe)
$action = isset($_POST['action']) ? cleanInput($_POST['action']) : 'request';

// Connexion à la base de données
$pdo = getDbConnection();
if (!$pdo) {
    sendJsonResponse(false, 'Erreur de connexion au serveur');
}

// ============================================================
// ÉTAPE 1 : DEMANDE DE RÉINITIALISATION (envoi du lien par email)
// ============================================================
if ($action === 'request') {
    
    $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
    
    // Validation
    if (empty($email)) {
        sendJsonResponse(false, 'Veuillez saisir votre adresse email');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, 'Format d\'email invalide');
    }
    
    try {
        // Rechercher le professeur
        $stmt = $pdo->prepare("
            SELECT id, nom_complet, email_academique 
            FROM professeurs 
            WHERE email_academique = :email 
            AND is_active = 1
            LIMIT 1
        ");
        
        $stmt->execute(['email' => $email]);
        $professeur = $stmt->fetch();
        
        // Pour des raisons de sécurité, on ne dit pas si l'email existe ou non
        if (!$professeur) {
            // Attendre un peu pour éviter les attaques par timing
            usleep(500000);
            sendJsonResponse(true, 'Si cet email existe, un lien de réinitialisation a été envoyé.');
        }
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $tokenExpiry = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_HOURS . ' hours'));
        
        // Enregistrer le token dans la base de données
        $updateStmt = $pdo->prepare("
            UPDATE professeurs 
            SET validation_token = :token,
                token_expiration = :expiry
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            'token' => $token,
            'expiry' => $tokenExpiry,
            'id' => $professeur['id']
        ]);
        
        // Construire le lien de réinitialisation
        $resetLink = SITE_URL . "/pages/reset_password.php?token=" . $token;
        
        // Préparer l'email
        $to = $professeur['email_academique'];
        $subject = "Réinitialisation de votre mot de passe - " . SITE_NAME;
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a3c40; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .button { display: inline-block; padding: 12px 30px; background: #4a8c6d; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Réinitialisation de mot de passe</h2>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>" . htmlspecialchars($professeur['nom_complet']) . "</strong>,</p>
                    
                    <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte " . SITE_NAME . ".</p>
                    
                    <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                    
                    <div style='text-align: center;'>
                        <a href='" . $resetLink . "' class='button'>Réinitialiser mon mot de passe</a>
                    </div>
                    
                    <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                    <p style='word-break: break-all; background: #fff; padding: 10px; border: 1px solid #ddd;'>" . $resetLink . "</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Important :</strong>
                        <ul>
                            <li>Ce lien est valide pendant <strong>" . TOKEN_EXPIRY_HOURS . " heures</strong></li>
                            <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</li>
                            <li>Votre mot de passe actuel reste valide jusqu'à ce que vous le changiez</li>
                        </ul>
                    </div>
                    
                    <p>Pour toute question, contactez notre support technique.</p>
                    
                    <p>Cordialement,<br><strong>L'équipe " . SITE_NAME . "</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". Tous droits réservés.</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Headers pour l'email HTML
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . SITE_NAME . ' <noreply@ila.edu>',
            'Reply-To: support@ila.edu',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Envoyer l'email
        $emailSent = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($emailSent) {
            // Log de l'action
            try {
                $logStmt = $pdo->prepare("
                    INSERT INTO email_validation_log (professeur_id, action, ip_address, user_agent)
                    VALUES (:professeur_id, 'sent', :ip, :user_agent)
                ");
                $logStmt->execute([
                    'professeur_id' => $professeur['id'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            } catch (PDOException $e) {
                error_log("Log d'email non enregistré: " . $e->getMessage());
            }
            
            sendJsonResponse(true, 'Un email de réinitialisation a été envoyé à votre adresse.');
        } else {
            error_log("Échec de l'envoi de l'email à: " . $email);
            sendJsonResponse(false, 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.');
        }
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la demande de réinitialisation: " . $e->getMessage());
        sendJsonResponse(false, 'Une erreur est survenue. Veuillez réessayer.');
    }
}

// ============================================================
// ÉTAPE 2 : VÉRIFICATION DU TOKEN
// ============================================================
else if ($action === 'verify_token') {
    
    $token = isset($_POST['token']) ? cleanInput($_POST['token']) : '';
    
    if (empty($token)) {
        sendJsonResponse(false, 'Token invalide');
    }
    
    try {
        // Vérifier le token
        $stmt = $pdo->prepare("
            SELECT id, nom_complet, token_expiration
            FROM professeurs
            WHERE validation_token = :token
            AND is_active = 1
            LIMIT 1
        ");
        
        $stmt->execute(['token' => $token]);
        $professeur = $stmt->fetch();
        
        if (!$professeur) {
            sendJsonResponse(false, 'Token invalide ou expiré');
        }
        
        // Vérifier l'expiration
        if (strtotime($professeur['token_expiration']) < time()) {
            sendJsonResponse(false, 'Ce lien a expiré. Veuillez faire une nouvelle demande.');
        }
        
        sendJsonResponse(true, 'Token valide', [
            'nom_complet' => $professeur['nom_complet']
        ]);
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification du token: " . $e->getMessage());
        sendJsonResponse(false, 'Erreur lors de la vérification');
    }
}

// ============================================================
// ÉTAPE 3 : RÉINITIALISATION DU MOT DE PASSE
// ============================================================
else if ($action === 'reset_password') {
    
    $token = isset($_POST['token']) ? cleanInput($_POST['token']) : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validations
    if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
        sendJsonResponse(false, 'Tous les champs sont requis');
    }
    
    if ($newPassword !== $confirmPassword) {
        sendJsonResponse(false, 'Les mots de passe ne correspondent pas');
    }
    
    // Validation de la force du mot de passe
    if (strlen($newPassword) < 8) {
        sendJsonResponse(false, 'Le mot de passe doit contenir au moins 8 caractères');
    }
    
    if (!preg_match('/[A-Z]/', $newPassword)) {
        sendJsonResponse(false, 'Le mot de passe doit contenir au moins une majuscule');
    }
    
    if (!preg_match('/[a-z]/', $newPassword)) {
        sendJsonResponse(false, 'Le mot de passe doit contenir au moins une minuscule');
    }
    
    if (!preg_match('/[0-9]/', $newPassword)) {
        sendJsonResponse(false, 'Le mot de passe doit contenir au moins un chiffre');
    }
    
    try {
        // Vérifier le token
        $stmt = $pdo->prepare("
            SELECT id, nom_complet, email_academique, token_expiration
            FROM professeurs
            WHERE validation_token = :token
            AND is_active = 1
            LIMIT 1
        ");
        
        $stmt->execute(['token' => $token]);
        $professeur = $stmt->fetch();
        
        if (!$professeur) {
            sendJsonResponse(false, 'Token invalide');
        }
        
        // Vérifier l'expiration
        if (strtotime($professeur['token_expiration']) < time()) {
            sendJsonResponse(false, 'Ce lien a expiré. Veuillez faire une nouvelle demande.');
        }
        
        // Hasher le nouveau mot de passe
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe et supprimer le token
        $updateStmt = $pdo->prepare("
            UPDATE professeurs
            SET password_hash = :password,
                validation_token = NULL,
                token_expiration = NULL,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            'password' => $passwordHash,
            'id' => $professeur['id']
        ]);
        
        // Envoyer un email de confirmation
        $subject = "Votre mot de passe a été modifié - " . SITE_NAME;
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a3c40; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✅ Mot de passe modifié</h2>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>" . htmlspecialchars($professeur['nom_complet']) . "</strong>,</p>
                    
                    <div class='success'>
                        <strong>✓ Confirmation :</strong> Votre mot de passe a été modifié avec succès.
                    </div>
                    
                    <p>Vous pouvez désormais vous connecter avec votre nouveau mot de passe.</p>
                    
                    <p><strong>⚠️ Si vous n'êtes pas à l'origine de cette modification :</strong><br>
                    Contactez immédiatement notre support technique pour sécuriser votre compte.</p>
                    
                    <p>Cordialement,<br><strong>L'équipe " . SITE_NAME . "</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . SITE_NAME . ' <noreply@ila.edu>'
        ];
        
        mail($professeur['email_academique'], $subject, $message, implode("\r\n", $headers));
        
        sendJsonResponse(true, 'Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.');
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la réinitialisation: " . $e->getMessage());
        sendJsonResponse(false, 'Une erreur est survenue. Veuillez réessayer.');
    }
}

// Action invalide
else {
    sendJsonResponse(false, 'Action non reconnue');
}