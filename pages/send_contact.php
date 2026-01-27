<?php
/**
 * Script d'envoi d'emails pour le formulaire de contact
 * Institut de Linguistique Appliquée d'Abidjan
 */

// Démarrer la session (si nécessaire pour les messages flash)
session_start();

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(0);

// En développement, activer les erreurs
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Autoriser les requêtes CORS (si nécessaire)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    displayErrorPage('Méthode non autorisée. Utilisez POST.', 405);
    exit;
}

// Récupérer et sécuriser les données du formulaire
$name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim(htmlspecialchars($_POST['phone'])) : '';
$subject = isset($_POST['subject']) ? trim(htmlspecialchars($_POST['subject'])) : '';
$message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'])) : '';
$newsletter = isset($_POST['newsletter']) ? true : false;

// Tableau de correspondance pour les sujets
$subjectLabels = [
    'achat' => 'Achat d\'ouvrages',
    'information' => 'Demande d\'information',
    'collaboration' => 'Proposition de collaboration',
    'formation' => 'Demande de formation',
    'partenariat' => 'Partenariat institutionnel',
    'autre' => 'Autre demande'
];

// Validation des données
$errors = [];

// Validation du nom
if (empty($name)) {
    $errors['name'] = 'Le nom est requis.';
} elseif (strlen($name) < 2) {
    $errors['name'] = 'Le nom doit contenir au moins 2 caractères.';
} elseif (strlen($name) > 100) {
    $errors['name'] = 'Le nom ne peut pas dépasser 100 caractères.';
}

// Validation de l'email
if (empty($email)) {
    $errors['email'] = 'L\'email est requis.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'L\'adresse email n\'est pas valide.';
} elseif (strlen($email) > 150) {
    $errors['email'] = 'L\'email ne peut pas dépasser 150 caractères.';
}

// Validation du téléphone (optionnel)
if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]{8,20}$/', $phone)) {
    $errors['phone'] = 'Le format du téléphone n\'est pas valide.';
}

// Validation du sujet
if (empty($subject)) {
    $errors['subject'] = 'Le sujet est requis.';
} elseif (!array_key_exists($subject, $subjectLabels)) {
    $errors['subject'] = 'Le sujet sélectionné n\'est pas valide.';
}

// Validation du message
if (empty($message)) {
    $errors['message'] = 'Le message est requis.';
} elseif (strlen($message) < 10) {
    $errors['message'] = 'Le message doit contenir au moins 10 caractères.';
} elseif (strlen($message) > 2000) {
    $errors['message'] = 'Le message ne peut pas dépasser 2000 caractères.';
}

// Si des erreurs, afficher la page d'erreur de validation
if (!empty($errors)) {
    displayValidationErrors($errors);
    exit;
}

// Fonction pour afficher les erreurs de validation
function displayValidationErrors($errors) {
    $error_html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur de validation - ILA</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .error-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                padding: 40px;
                max-width: 600px;
                width: 100%;
                animation: fadeIn 0.5s ease-out;
                border-top: 5px solid #ff9800;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .error-header {
                color: #e65100;
                margin-bottom: 25px;
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .error-header i {
                font-size: 32px;
            }
            
            .error-title {
                font-size: 28px;
                font-weight: 700;
                margin: 0;
            }
            
            .error-subtitle {
                color: #666;
                font-size: 16px;
                margin-top: 5px;
                font-weight: normal;
            }
            
            .error-list {
                list-style: none;
                padding: 0;
                margin: 0 0 30px 0;
            }
            
            .error-item {
                padding: 15px;
                background: #fff8e1;
                margin-bottom: 12px;
                border-radius: 8px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                border-left: 4px solid #ffb300;
                transition: transform 0.2s ease;
            }
            
            .error-item:hover {
                transform: translateX(5px);
                background: #ffecb3;
            }
            
            .error-item i {
                color: #ff9800;
                font-size: 18px;
                margin-top: 2px;
                flex-shrink: 0;
            }
            
            .error-content {
                flex: 1;
            }
            
            .error-field {
                font-weight: 700;
                color: #e65100;
                margin-bottom: 4px;
                text-transform: capitalize;
            }
            
            .error-message {
                color: #5d4037;
                line-height: 1.5;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 14px 28px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                cursor: pointer;
                border: none;
                min-width: 180px;
            }
            
            .btn-primary {
                background: #3498db;
                color: white;
            }
            
            .btn-primary:hover {
                background: #2980b9;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            }
            
            .btn-secondary {
                background: #f8f9fa;
                color: #2c3e50;
                border: 2px solid #e9ecef;
            }
            
            .btn-secondary:hover {
                background: #e9ecef;
                transform: translateY(-2px);
            }
            
            .btn i {
                margin-right: 8px;
            }
            
            .form-tips {
                background: #e8f4fc;
                border-radius: 8px;
                padding: 20px;
                margin-top: 25px;
                border-left: 4px solid #3498db;
            }
            
            .tips-title {
                color: #2c3e50;
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .tips-list {
                color: #34495e;
                padding-left: 20px;
                line-height: 1.6;
            }
            
            .tips-list li {
                margin-bottom: 8px;
            }
            
            @media (max-width: 480px) {
                .error-container {
                    padding: 25px;
                }
                
                .error-title {
                    font-size: 24px;
                }
                
                .error-header {
                    flex-direction: column;
                    text-align: center;
                    gap: 10px;
                }
                
                .btn {
                    padding: 12px 20px;
                    min-width: 160px;
                    font-size: 15px;
                }
                
                .action-buttons {
                    flex-direction: column;
                }
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
        <div class="error-container">
            <div class="error-header">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h1 class="error-title">Erreurs dans le formulaire</h1>
                    <p class="error-subtitle">Veuillez corriger les points suivants :</p>
                </div>
            </div>
            
            <ul class="error-list">';
    
    foreach ($errors as $field => $message) {
        $fieldLabel = str_replace('_', ' ', $field);
        $fieldLabel = ucfirst($fieldLabel);
        
        $error_html .= '
                <li class="error-item">
                    <i class="fas fa-times-circle"></i>
                    <div class="error-content">
                        <div class="error-field">' . $fieldLabel . '</div>
                        <div class="error-message">' . htmlspecialchars($message) . '</div>
                    </div>
                </li>';
    }
    
    $error_html .= '
            </ul>
            
            <div class="form-tips">
                <div class="tips-title">
                    <i class="fas fa-lightbulb"></i>
                    Conseils pour remplir le formulaire :
                </div>
                <ul class="tips-list">
                    <li>Vérifiez que tous les champs obligatoires (*) sont remplis</li>
                    <li>Assurez-vous que votre adresse email est valide</li>
                    <li>Le message doit contenir au moins 10 caractères</li>
                    <li>Le numéro de téléphone doit être au format international si nécessaire</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <button onclick="window.history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour au formulaire
                </button>
                
                <a href="contact.html" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Recommencer
                </a>
            </div>
        </div>
        
        <script>
            // Focus sur le premier champ d\'erreur après un court délai
            setTimeout(() => {
                if (window.history.length > 1) {
                    // On laisse l\'utilisateur utiliser le bouton retour
                }
            }, 100);
        </script>
    </body>
    </html>';
    
    echo $error_html;
}

// Tentative d'envoi de l'email
try {
    // Configuration de l'email
    $to = 'contact@ila.edu'; // Email de destination principal
    $cc = 'publications@ila.edu'; // Email en copie
    
    // Sujet de l'email
    $email_subject = '[Contact ILA] ' . $subjectLabels[$subject] . ' - ' . date('d/m/Y');
    
    // Construction du corps du message
    $email_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Nouveau message de contact</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #2c3e50; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Institut de Linguistique Appliquée d'Abidjan</h1>
                <h2>Nouveau message de contact</h2>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <span class='label'>Date :</span> " . date('d/m/Y à H:i:s') . "
                </div>
                
                <div class='field'>
                    <span class='label'>Expéditeur :</span> " . htmlspecialchars($name) . "
                </div>
                
                <div class='field'>
                    <span class='label'>Email :</span> <a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a>
                </div>
                
                <div class='field'>
                    <span class='label'>Téléphone :</span> " . (!empty($phone) ? htmlspecialchars($phone) : 'Non renseigné') . "
                </div>
                
                <div class='field'>
                    <span class='label'>Sujet :</span> " . $subjectLabels[$subject] . "
                </div>
                
                <div class='field'>
                    <span class='label'>Inscription newsletter :</span> " . ($newsletter ? 'Oui' : 'Non') . "
                </div>
                
                <div class='field'>
                    <span class='label'>Message :</span>
                    <div style='margin-top: 10px; padding: 10px; background: white; border: 1px solid #eee; border-radius: 4px;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </div>
            </div>
            
            <div class='footer'>
                <p>Ce message a été envoyé depuis le formulaire de contact du site web de l'ILA.</p>
                <p>© " . date('Y') . " - Institut de Linguistique Appliquée d'Abidjan</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // En-têtes de l'email
    $headers = [
        'From: ' . $email,
        'Reply-To: ' . $email,
        'Cc: ' . $cc,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 1 (Highest)',
        'X-MSMail-Priority: High',
        'Importance: High'
    ];
    
    // Convertir le tableau d'en-têtes en chaîne
    $headers_string = implode("\r\n", $headers);
    
    // Utiliser la fonction mail() de PHP
    $mail_sent = mail($to, $email_subject, $email_body, $headers_string);
    
    // Envoi d'un accusé de réception à l'expéditeur
    $confirmation_subject = 'Accusé de réception - Institut de Linguistique Appliquée';
    $confirmation_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Accusé de réception</title>
    </head>
    <body>
        <p>Bonjour " . htmlspecialchars($name) . ",</p>
        
        <p>Nous accusons réception de votre message envoyé via le formulaire de contact de l'Institut de Linguistique Appliquée d'Abidjan.</p>
        
        <p><strong>Résumé de votre message :</strong></p>
        <ul>
            <li><strong>Sujet :</strong> " . $subjectLabels[$subject] . "</li>
            <li><strong>Date :</strong> " . date('d/m/Y à H:i:s') . "</li>
        </ul>
        
        <p>Nous traiterons votre demande dans les plus brefs délais. Vous recevrez une réponse sous 48 heures ouvrables.</p>
        
        <p>Cordialement,<br>
        <strong>L'équipe de l'ILA</strong><br>
        Institut de Linguistique Appliquée d'Abidjan<br>
        Université Félix Houphouët-Boigny</p>
        
        <hr>
        <p style='font-size: 0.8em; color: #666;'>
            Ceci est un message automatique, merci de ne pas y répondre.<br>
            © " . date('Y') . " - Institut de Linguistique Appliquée d'Abidjan
        </p>
    </body>
    </html>
    ";
    
    $confirmation_headers = [
        'From: contact@ila.edu',
        'Reply-To: contact@ila.edu',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Envoyer l'accusé de réception
    mail($email, $confirmation_subject, $confirmation_body, implode("\r\n", $confirmation_headers));
    
    // Log l'envoi (pour débogage)
    $log_message = date('Y-m-d H:i:s') . " - Message envoyé par: $email ($name) - Sujet: $subject - IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents(__DIR__ . '/contact_log.txt', $log_message, FILE_APPEND);
    
    // Afficher la page de succès HTML
    displaySuccessPage($name, $email, $subject, $subjectLabels);
    
} catch (Exception $e) {
    // En cas d'erreur - Afficher une page d'erreur HTML
    displayErrorPage('Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.', 500, $e->getMessage());
    
    // Log de l'erreur
    $error_log = date('Y-m-d H:i:s') . " - ERREUR: " . $e->getMessage() . " - IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents(__DIR__ . '/contact_errors.txt', $error_log, FILE_APPEND);
}

// Fonction pour afficher la page de succès
function displaySuccessPage($name, $email, $subject, $subjectLabels) {
    $success_html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Message envoyé avec succès - ILA</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .success-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                animation: fadeIn 0.5s ease-out;
                border-top: 5px solid #4CAF50;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .success-icon {
                width: 80px;
                height: 80px;
                background: #4CAF50;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 25px;
                animation: scaleIn 0.5s ease-out 0.2s both;
            }
            
            @keyframes scaleIn {
                from {
                    transform: scale(0);
                }
                to {
                    transform: scale(1);
                }
            }
            
            .success-icon i {
                font-size: 40px;
                color: white;
            }
            
            .success-title {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
                line-height: 1.3;
            }
            
            .success-message {
                color: #34495e;
                font-size: 17px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            
            .success-details {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 30px;
                text-align: left;
                border-left: 4px solid #3498db;
            }
            
            .detail-item {
                display: flex;
                align-items: center;
                margin-bottom: 12px;
            }
            
            .detail-item:last-child {
                margin-bottom: 0;
            }
            
            .detail-icon {
                width: 32px;
                height: 32px;
                background: #e8f4fc;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 12px;
                flex-shrink: 0;
            }
            
            .detail-icon i {
                color: #3498db;
                font-size: 16px;
            }
            
            .detail-text {
                color: #2c3e50;
                font-size: 15px;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 14px 28px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                cursor: pointer;
                border: none;
                min-width: 160px;
            }
            
            .btn-primary {
                background: #3498db;
                color: white;
            }
            
            .btn-primary:hover {
                background: #2980b9;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            }
            
            .btn-secondary {
                background: #f8f9fa;
                color: #2c3e50;
                border: 2px solid #e9ecef;
            }
            
            .btn-secondary:hover {
                background: #e9ecef;
                transform: translateY(-2px);
            }
            
            .btn i {
                margin-right: 8px;
            }
            
            .auto-close {
                margin-top: 25px;
                color: #7f8c8d;
                font-size: 14px;
                font-style: italic;
            }
            
            .confirmation-note {
                background: #e8f5e9;
                border-radius: 8px;
                padding: 15px;
                margin-top: 20px;
                border-left: 4px solid #4CAF50;
                text-align: left;
            }
            
            .confirmation-note i {
                color: #4CAF50;
                margin-right: 8px;
            }
            
            @media (max-width: 480px) {
                .success-container {
                    padding: 25px;
                }
                
                .success-title {
                    font-size: 24px;
                }
                
                .success-message {
                    font-size: 16px;
                }
                
                .btn {
                    padding: 12px 20px;
                    min-width: 140px;
                }
                
                .action-buttons {
                    flex-direction: column;
                }
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="success-title">Message envoyé avec succès !</h1>
            
            <p class="success-message">
                Votre message a été transmis à l\'équipe de l\'ILA. 
                Vous recevrez un accusé de réception par email dans les minutes qui suivent.
            </p>
            
            <div class="success-details">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="detail-text">
                        <strong>Expéditeur :</strong> ' . htmlspecialchars($name) . '
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="detail-text">
                        <strong>Email :</strong> ' . htmlspecialchars($email) . '
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="detail-text">
                        <strong>Sujet :</strong> ' . $subjectLabels[$subject] . '
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="detail-text">
                        <strong>Date d\'envoi :</strong> ' . date('d/m/Y à H:i:s') . '
                    </div>
                </div>
            </div>
            
            <div class="confirmation-note">
                <i class="fas fa-info-circle"></i>
                <strong>Accusé de réception :</strong> Un email de confirmation a été envoyé à <strong>' . htmlspecialchars($email) . '</strong>. 
                Consultez votre boîte de réception (et vos spams).
            </div>
            
            <div class="action-buttons">
                <a href="contact.html" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour au contact
                </a>
                
                <a href="../index.html" class="btn btn-primary">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
            
            <p class="auto-close">
                Vous serez redirigé vers la page d\'accueil dans <span id="countdown">10</span> secondes...
            </p>
        </div>
        
        <script>
            // Compte à rebours pour la redirection automatique
            let seconds = 10;
            const countdownElement = document.getElementById("countdown");
            
            const countdown = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdown);
                    window.location.href = "../index.html";
                }
            }, 1000);
            
            // Redirection manuelle si l\'utilisateur clique sur retour
            document.querySelector(".btn-secondary").addEventListener("click", function(e) {
                e.preventDefault();
                clearInterval(countdown);
                window.location.href = "contact.html";
            });
        </script>
    </body>
    </html>';
    
    echo $success_html;
}

// Fonction pour afficher la page d'erreur technique
function displayErrorPage($message, $code = 500, $technicalDetails = '') {
    http_response_code($code);
    
    $error_html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur d\'envoi - ILA</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .error-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                animation: fadeIn 0.5s ease-out;
                border-top: 5px solid #e74c3c;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .error-icon {
                width: 80px;
                height: 80px;
                background: #e74c3c;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 25px;
            }
            
            .error-icon i {
                font-size: 40px;
                color: white;
            }
            
            .error-title {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
            }
            
            .error-message {
                color: #34495e;
                font-size: 17px;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            
            .error-details {
                background: #fef2f2;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 30px;
                text-align: left;
                border-left: 4px solid #e74c3c;
                font-family: monospace;
                font-size: 14px;
                color: #dc2626;
                max-height: 200px;
                overflow-y: auto;
                display: ' . (!empty($technicalDetails) ? 'block' : 'none') . ';
            }
            
            .contact-alternative {
                background: #e8f4fc;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 30px;
                border-left: 4px solid #3498db;
                text-align: left;
            }
            
            .contact-alternative h4 {
                color: #2c3e50;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .contact-alternative ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .contact-alternative li {
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .contact-alternative i {
                color: #3498db;
                width: 20px;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 14px 28px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                cursor: pointer;
                border: none;
                min-width: 160px;
            }
            
            .btn-primary {
                background: #3498db;
                color: white;
            }
            
            .btn-primary:hover {
                background: #2980b9;
                transform: translateY(-2px);
            }
            
            .btn-secondary {
                background: #f8f9fa;
                color: #2c3e50;
                border: 2px solid #e9ecef;
            }
            
            .btn-secondary:hover {
                background: #e9ecef;
                transform: translateY(-2px);
            }
            
            .btn i {
                margin-right: 8px;
            }
            
            @media (max-width: 480px) {
                .error-container {
                    padding: 25px;
                }
                
                .error-title {
                    font-size: 24px;
                }
                
                .error-message {
                    font-size: 16px;
                }
                
                .btn {
                    padding: 12px 20px;
                    min-width: 140px;
                }
                
                .action-buttons {
                    flex-direction: column;
                }
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h1 class="error-title">Erreur d\'envoi</h1>
            
            <p class="error-message">
                ' . htmlspecialchars($message) . '
            </p>
            
            <div class="error-details">
                ' . (!empty($technicalDetails) ? htmlspecialchars($technicalDetails) : '') . '
            </div>
            
            <div class="contact-alternative">
                <h4>
                    <i class="fas fa-phone-alt"></i>
                    Contactez-nous autrement :
                </h4>
                <ul>
                    <li>
                        <i class="fas fa-phone"></i>
                        <strong>Téléphone :</strong> +225 01 40 54 40 00
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <strong>Email :</strong> contact@ila.edu
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <strong>Adresse :</strong> Université Félix Houphouët-Boigny, Abidjan
                    </li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour au formulaire
                </a>
                
                <a href="../index.html" class="btn btn-primary">
                    <i class="fas fa-home"></i> Retour à l\'accueil
                </a>
            </div>
        </div>
    </body>
    </html>';
    
    echo $error_html;
}
?>