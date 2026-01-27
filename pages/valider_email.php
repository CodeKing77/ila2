<?php
/*
 * ========================================
 * FICHIER 2: valider_email.php
 * Page de validation de l'email
 * ========================================
 */
//Fichier pour valider l'email des professeurs partenaires via un token unique envoyé par email
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'ila_publications_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$validation_status = null;
$message = '';
$professeur_nom = '';

// Vérifier si un token est fourni
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Rechercher le professeur avec ce token
        $stmt = $pdo->prepare("
            SELECT id, nom_complet, email_academique, token_expiration, is_active 
            FROM professeurs 
            WHERE validation_token = ?
        ");
        $stmt->execute([$token]);
        $professeur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($professeur) {
            $professeur_nom = $professeur['nom_complet'];
            
            // Vérifier si le compte est déjà validé
            if ($professeur['is_active'] == 1) {
                $validation_status = 'already_validated';
                $message = "Votre compte a déjà été validé. Vous pouvez vous connecter.";
            }
            // Vérifier si le token n'a pas expiré
            elseif (strtotime($professeur['token_expiration']) < time()) {
                $validation_status = 'expired';
                $message = "Ce lien de validation a expiré. Veuillez contacter notre équipe.";
            }
            // Tout est OK, valider le compte
            else {
                // Activer le compte
                $stmt = $pdo->prepare("
                    UPDATE professeurs 
                    SET is_active = 1, 
                        email_verified_at = NOW(),
                        validation_token = NULL,
                        token_expiration = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$professeur['id']]);
                
                $validation_status = 'success';
                $message = "Félicitations ! Votre adresse email a été validée avec succès.";
                
                // Envoyer un email de confirmation (optionnel)
                sendConfirmationEmail($professeur['email_academique'], $professeur['nom_complet']);
            }
        } else {
            $validation_status = 'invalid';
            $message = "Ce lien de validation est invalide. Veuillez vérifier votre email.";
        }
        
    } catch (Exception $e) {
        $validation_status = 'error';
        $message = "Une erreur est survenue lors de la validation. Veuillez réessayer.";
        error_log("Erreur validation email: " . $e->getMessage());
    }
} else {
    $validation_status = 'no_token';
    $message = "Aucun token de validation fourni.";
}

// Fonction pour envoyer l'email de confirmation (optionnel)
function sendConfirmationEmail($email, $nom) {
    $subject = "Votre compte a été validé - ILA";
    
    $message = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a3c40, #2d5d63); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #e9ecef; }
            .success-icon { font-size: 60px; text-align: center; margin: 20px 0; }
            .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #38a3a5, #2d5d63); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Email Validé !</h1>
            </div>
            
            <div class='content'>
                <div class='success-icon'>🎉</div>
                
                <h2>Bonjour " . htmlspecialchars($nom) . ",</h2>
                
                <p>Votre adresse email a été validée avec succès !</p>
                
                <p><strong>Prochaines étapes :</strong></p>
                <ol>
                    <li>Votre profil sera examiné par notre comité académique</li>
                    <li>Vous recevrez une notification de validation sous 48 heures</li>
                    <li>Une fois validé, vous pourrez accéder à votre espace professeur</li>
                </ol>
                
                <p>Merci de votre patience !</p>
            </div>
            
            <div class='footer'>
                <p><strong>Institut de Linguistique Appliquée d'Abidjan</strong><br>
                📧 contact@ila.edu | 📞 +225 01 40 54 40 00</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Institut de Linguistique Appliquée <noreply@ila.edu>" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation Email - ILA</title>
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
        }
        
        .validation-container {
            max-width: 600px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .validation-header {
            background: linear-gradient(135deg, #1a3c40, #2d5d63);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .validation-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .validation-content {
            padding: 40px;
            text-align: center;
        }
        
        .status-success { color: #2ecc71; }
        .status-error { color: #e74c3c; }
        .status-warning { color: #f39c12; }
        
        .validation-message {
            font-size: 18px;
            margin: 30px 0;
            line-height: 1.6;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #38a3a5, #2d5d63);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 163, 165, 0.3);
        }
        
        .btn-secondary {
            display: inline-block;
            padding: 15px 30px;
            background: white;
            color: #1a3c40;
            text-decoration: none;
            border: 2px solid #1a3c40;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn-secondary:hover {
            background: #1a3c40;
            color: white;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #38a3a5;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .next-steps {
            text-align: left;
            margin: 20px 0;
        }
        
        .next-steps ol {
            padding-left: 20px;
        }
        
        .next-steps li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="validation-container">
        <div class="validation-header">
            <div class="validation-icon">
                <?php if ($validation_status === 'success'): ?>
                    ✅
                <?php elseif ($validation_status === 'already_validated'): ?>
                    ℹ️
                <?php elseif ($validation_status === 'expired'): ?>
                    ⏰
                <?php else: ?>
                    ❌
                <?php endif; ?>
            </div>
            <h1>Validation de l'Email</h1>
        </div>
        
        <div class="validation-content">
            <?php if ($validation_status === 'success'): ?>
                <h2 class="status-success">🎉 Validation Réussie !</h2>
                <p class="validation-message">
                    Félicitations <strong><?php echo htmlspecialchars($professeur_nom); ?></strong> !<br>
                    Votre adresse email a été validée avec succès.
                </p>
                
                <div class="info-box">
                    <h3>📋 Prochaines étapes :</h3>
                    <div class="next-steps">
                        <ol>
                            <li><strong>Examen du profil</strong> : Notre comité académique va examiner votre candidature</li>
                            <li><strong>Notification</strong> : Vous recevrez un email de confirmation sous 48 heures</li>
                            <li><strong>Accès à l'espace professeur</strong> : Une fois validé, vous pourrez vous connecter</li>
                        </ol>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="professeurs.php" class="btn-primary">
                        <i class="fas fa-users"></i> Voir les professeurs
                    </a>
                    <a href="../index.html" class="btn-secondary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
                
            <?php elseif ($validation_status === 'already_validated'): ?>
                <h2 class="status-warning">ℹ️ Compte Déjà Validé</h2>
                <p class="validation-message">
                    Bonjour <strong><?php echo htmlspecialchars($professeur_nom); ?></strong>,<br>
                    Votre compte a déjà été validé. Vous pouvez vous connecter dès maintenant.
                </p>
                
                <div style="margin-top: 30px;">
                    <a href="connexion.php?type=professeur" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                    <a href="../index.html" class="btn-secondary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
                
            <?php elseif ($validation_status === 'expired'): ?>
                <h2 class="status-error">⏰ Lien Expiré</h2>
                <p class="validation-message">
                    Ce lien de validation a expiré (validité : 48 heures).
                </p>
                
                <div class="info-box">
                    <p><strong>Que faire ?</strong></p>
                    <p>Veuillez contacter notre équipe académique pour obtenir un nouveau lien de validation.</p>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="contact.html" class="btn-primary">
                        <i class="fas fa-envelope"></i> Contacter l'équipe
                    </a>
                    <a href="../index.html" class="btn-secondary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
                
            <?php else: ?>
                <h2 class="status-error">❌ Erreur de Validation</h2>
                <p class="validation-message">
                    <?php echo htmlspecialchars($message); ?>
                </p>
                
                <div class="info-box">
                    <p><strong>Vérifications à effectuer :</strong></p>
                    <ul style="text-align: left;">
                        <li>Assurez-vous d'avoir copié le lien complet depuis votre email</li>
                        <li>Vérifiez que le lien n'a pas été modifié</li>
                        <li>Consultez votre dossier de spam/courrier indésirable</li>
                    </ul>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="contact.html" class="btn-primary">
                        <i class="fas fa-envelope"></i> Contactez-nous
                    </a>
                    <a href="../index.html" class="btn-secondary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e9ecef;">
                <p style="color: #666; font-size: 14px;">
                    <strong>Besoin d'aide ?</strong><br>
                    📧 contact@ila.edu | 📞 +225 01 40 54 40 00
                </p>
            </div>
        </div>
    </div>
</body>
</html>