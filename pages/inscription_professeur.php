<?php
/*
 * ========================================
 * FICHIER 1: inscription_professeur.php
 * // inscription_professeur.php - Code backend pour l'inscription des professeurs partenaires avec validation par email
 * ========================================
 */
?>
<?php
session_start();
// Affichage de toutes les erreurs
error_reporting(E_ALL); 
ini_set('display_errors', 1);

$errors = [];
$success = false;

// Configuration de la base de données
$host = 'localhost';
$dbname = 'ila_publications_db';
$username = 'root';
$password = '';

// ✅ CONFIGURATION EMAIL (À ADAPTER)
// ⚠️ IMPORTANT : À MODIFIER selon votre environnement de production
define('MAIL_FROM', 'noreply@ila.edu'); // Mail de l'expéditeur - À MODIFIER en production.
define('MAIL_FROM_NAME', 'Institut de Linguistique Appliquée');
define('SITE_URL', 'http://localhost/site_ila'); // Url en développement - À MODIFIER en production lors de la mise en ligne.
// define('SITE_URL', 'https://www.ila.edu');   // En production



// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonctions utilitaires
function validateEmail($email_academique) {
    return filter_var($email_academique, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/', $password);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);  
    return $data;
}

// ✅ NOUVELLE FONCTION : Générer un token de validation unique
function generateValidationToken() {
    return bin2hex(random_bytes(32)); // Token de 64 caractères
}

// ✅ NOUVELLE FONCTION : Envoyer l'email de validation
function sendValidationEmail($email, $nom_complet, $token, $professeur_id) {
    $validation_link = SITE_URL . "../pages/valider_email.php?token=" . $token;
    
    $subject = "Validez votre inscription - Institut de Linguistique Appliquée";
    
    $message = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a3c40, #2d5d63); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #e9ecef; }
            .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #38a3a5, #2d5d63); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 Bienvenue à l'ILA</h1>
                <p>Institut de Linguistique Appliquée d'Abidjan</p>
            </div>
            
            <div class='content'>
                <h2>Bonjour " . htmlspecialchars($nom_complet) . ",</h2>
                
                <p>Merci de votre demande d'inscription en tant que Professeur Partenaire.</p>
                
                <p><strong>Pour finaliser votre inscription, veuillez valider votre adresse email en cliquant sur le bouton ci-dessous :</strong></p>
                
                <div style='text-align: center;'>
                    <a href='" . $validation_link . "' class='button'>
                        ✅ Valider mon adresse email
                    </a>
                </div>
                
                <div class='warning'>
                    <strong>⏰ Important :</strong> Ce lien est valable pendant <strong>48 heures</strong>.
                </div>
                
                <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                <p style='word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . $validation_link . "</p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #e9ecef;'>
                
                <h3>📋 Prochaines étapes :</h3>
                <ol>
                    <li>Validez votre email (en cliquant sur le lien ci-dessus)</li>
                    <li>Votre profil sera examiné par notre comité académique</li>
                    <li>Vous recevrez un second email de confirmation sous 48h</li>
                    <li>Vous pourrez ensuite accéder à votre espace professeur</li>
                </ol>
                
                <p><strong>Votre numéro d'enregistrement :</strong> PRO" . str_pad($professeur_id, 6, '0', STR_PAD_LEFT) . "</p>
            </div>
            
            <div class='footer'>
                <p>Vous recevez cet email car vous vous êtes inscrit sur notre plateforme.</p>
                <p>Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.</p>
                <p style='margin-top: 20px;'>
                    <strong>Institut de Linguistique Appliquée d'Abidjan</strong><br>
                    Université Félix Houphouët-Boigny<br>
                    📧 contact@ila.edu | 📞 +225 01 40 54 40 00
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers pour email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">" . "\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

function uploadPhoto($file, $professorId) {  //Fonction qui Upload la photo du professeur dans le dossier approprié ../assets/uploads/professeurs/
    $target_dir = "../assets/uploads/professeurs/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $file_name = "prof_" . $professorId . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $file_name;
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file["type"], $allowed_types)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé.'];
    }
    
    if ($file["size"] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB).'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Erreur lors du téléchargement du fichier.'];
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    
    // Validation des champs obligatoires
    $required_fields = [
        'nom_complet', 'email_academique', 'titre_academique', 
        'universites', 'specialites', 'password'
    ];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = "Ce champ est obligatoire.";
        }
    }
    
    // Validation de l'email
    if (!empty($_POST['email_academique']) && !validateEmail($_POST['email_academique'])) {
        $errors['email_academique'] = "Adresse email invalide.";
    }
    
    // Vérifier si l'email existe déjà
    if (!empty($_POST['email_academique'])) {
        $stmt = $pdo->prepare("SELECT id FROM professeurs WHERE email_academique = ?");
        $stmt->execute([$_POST['email_academique']]);
        if ($stmt->fetch()) {
            $errors['email_academique'] = "Cette adresse email est déjà utilisée.";
        }
    }
    
    // Validation du mot de passe
    if (!empty($_POST['password']) && !validatePassword($_POST['password'])) {
        $errors['password'] = "Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).";
    }
    
    // Vérifier la correspondance des mots de passe
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors['confirm_password'] = "Les mots de passe ne correspondent pas.";
    }
    
    // Validation des termes
    if (empty($_POST['terms_prof'])) {
        $errors['terms_prof'] = "Vous devez accepter les conditions de partenariat.";
    }
    
    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Préparer les données
            $nom_complet = sanitizeInput($_POST['nom_complet']);
            $email_academique = sanitizeInput($_POST['email_academique']);
            $titre_academique = sanitizeInput($_POST['titre_academique']);
            
            if ($titre_academique === 'autre' && !empty($_POST['autre_titre'])) {
                $titre_academique = sanitizeInput($_POST['autre_titre']);
            }
            
            $universites = sanitizeInput($_POST['universites']);
            $specialites = sanitizeInput($_POST['specialites']);
            $bio = !empty($_POST['bio']) ? sanitizeInput($_POST['bio']) : null;
            $telephone = !empty($_POST['telephone']) ? sanitizeInput($_POST['telephone']) : null;
            $site_web = !empty($_POST['site_web']) ? sanitizeInput($_POST['site_web']) : null;
            $linkedin_url = !empty($_POST['linkedin_url']) ? sanitizeInput($_POST['linkedin_url']) : null;
            $google_scholar = !empty($_POST['google_scholar']) ? sanitizeInput($_POST['google_scholar']) : null;
            $orcid = !empty($_POST['orcid']) ? sanitizeInput($_POST['orcid']) : null;
            $email_secondaire = !empty($_POST['email_secondaire']) ? sanitizeInput($_POST['email_secondaire']) : null;
            $disponibilite = !empty($_POST['disponibilite']) ? sanitizeInput($_POST['disponibilite']) : null;
            $adresse = !empty($_POST['adresse']) ? sanitizeInput($_POST['adresse']) : null;
            
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // ✅ GÉNÉRER LE TOKEN DE VALIDATION
            $validation_token = generateValidationToken();
            $token_expiration = date('Y-m-d H:i:s', strtotime('+48 hours'));
            
            // ✅ INSERTION AVEC is_active = 0 (compte non validé)
            $stmt = $pdo->prepare("
                INSERT INTO professeurs (
                    nom_complet, email_academique, password_hash, titre_academique, 
                    universites, bio, specialites, telephone, site_web, 
                    linkedin_url, google_scholar_url, orcid_id, email_secondaire, 
                    disponibilite, adresse, validation_token, token_expiration, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            $stmt->execute([
                $nom_complet, $email_academique, $password_hash, $titre_academique,
                $universites, $bio, $specialites, $telephone, $site_web,
                $linkedin_url, $google_scholar, $orcid, $email_secondaire,
                $disponibilite, $adresse, $validation_token, $token_expiration
            ]);
            
            $professeur_id = $pdo->lastInsertId();
            
            // Traitement de la photo
            $photo_url = null;
            if (!empty($_FILES['photo']['name'])) {
                $upload_result = uploadPhoto($_FILES['photo'], $professeur_id);
                if ($upload_result['success']) {
                    $photo_url = "../assets/uploads/professeurs/" . $upload_result['file_name'];
                    $stmt = $pdo->prepare("UPDATE professeurs SET photo_url = ? WHERE id = ?");
                    $stmt->execute([$photo_url, $professeur_id]);
                }
            }
            
            // Insertion des diplômes
            if (!empty($_POST['diplome_annee'])) {
                $diplomes_count = count($_POST['diplome_annee']);
                for ($i = 0; $i < $diplomes_count; $i++) {
                    if (!empty($_POST['diplome_annee'][$i]) && !empty($_POST['diplome_intitule'][$i])) {
                        $stmt = $pdo->prepare("
                            INSERT INTO diplomes (
                                professeur_id, annee, diplome, institution, pays, mention, ordre
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $professeur_id,
                            sanitizeInput($_POST['diplome_annee'][$i]),
                            sanitizeInput($_POST['diplome_intitule'][$i]),
                            sanitizeInput($_POST['diplome_institution'][$i]),
                            !empty($_POST['diplome_pays'][$i]) ? sanitizeInput($_POST['diplome_pays'][$i]) : null,
                            !empty($_POST['diplome_mention'][$i]) ? sanitizeInput($_POST['diplome_mention'][$i]) : null,
                            $i
                        ]);
                    }
                }
            }
            
            // Insertion des expériences
            if (!empty($_POST['experience_poste'])) {
                $experiences_count = count($_POST['experience_poste']);
                for ($i = 0; $i < $experiences_count; $i++) {
                    if (!empty($_POST['experience_poste'][$i]) && !empty($_POST['experience_institution'][$i])) {
                        $est_actuel = isset($_POST['experience_actuel'][$i]) ? 1 : 0;
                        $date_debut = !empty($_POST['experience_debut'][$i]) ? $_POST['experience_debut'][$i] . '-01' : null;
                        $date_fin = null;
                        
                        if (!$est_actuel && !empty($_POST['experience_fin'][$i])) {
                            $date_fin = $_POST['experience_fin'][$i] . '-01';
                        }
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO experiences (
                                professeur_id, poste, institution, description, 
                                date_debut, date_fin, est_actuel, type_experience, ordre
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'academique', ?)
                        ");
                        
                        $stmt->execute([
                            $professeur_id,
                            sanitizeInput($_POST['experience_poste'][$i]),
                            sanitizeInput($_POST['experience_institution'][$i]),
                            !empty($_POST['experience_description'][$i]) ? sanitizeInput($_POST['experience_description'][$i]) : null,
                            $date_debut, $date_fin, $est_actuel, $i
                        ]);
                    }
                }
            }
            
            // ✅ ENVOYER L'EMAIL DE VALIDATION
            $email_sent = sendValidationEmail($email_academique, $nom_complet, $validation_token, $professeur_id);
            
            if (!$email_sent) {
                // Logger l'erreur mais ne pas bloquer l'inscription
                error_log("Erreur d'envoi d'email pour le professeur ID: $professeur_id");
            }
            
            $pdo->commit();
            $success = true;
            
            $_SESSION['new_professor_id'] = $professeur_id;
            $_SESSION['validation_email_sent'] = $email_sent;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['database'] = "Une erreur est survenue lors de l'inscription: " . $e->getMessage();
            error_log("Erreur inscription professeur: " . $e->getMessage());
        }
    }
}
?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Professeur - ILA</title>
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/responsive.css">
    <link rel="stylesheet" href="../styles/auth.css">
    <link rel="stylesheet" href="../styles/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/images/logos/favicon.ico">
    <style>
            /* Styles pour la force du mot de passe */
            .password-strength {
                margin-top: 10px;
                background: #f8f9fa;
                border-radius: 6px;
                padding: 12px;
                border: 1px solid #e9ecef;
            }

            .strength-bar {
                height: 5px;
                background: #ddd;
                border-radius: 3px;
                margin-bottom: 10px;
                width: 0%;
                transition: width 0.3s ease, background-color 0.3s ease;
            }

            .strength-text {
                font-size: 0.85rem;
                color: #6c757d;
                margin-bottom: 8px;
            }

            .password-requirements {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .password-requirements li {
                font-size: 0.8rem;
                color: #6c757d;
                margin-bottom: 4px;
                padding-left: 20px;
                position: relative;
            }

            .password-requirements li:before {
                content: "✗";
                position: absolute;
                left: 0;
                color: #e74c3c;
            }

            .password-requirements li.valid:before {
                content: "✓";
                color: #2ecc71;
            }

            .password-match {
                margin-top: 10px;
                font-size: 0.85rem;
                display: flex;
                align-items: center;
                gap: 5px;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .password-match i {
                color: #2ecc71;
            }

            .password-match.valid i {
                color: #2ecc71;
            }

            .password-match:not(.valid) i {
                color: #e74c3c;
            }

            /* Styles pour les champs d'erreur */
            .form-error {
                color: #e74c3c;
                font-size: 0.85rem;
                margin-top: 5px;
                display: none;
            }

            input.error {
                border-color: #e74c3c !important;
            }

            input.error:focus {
                box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
            }

            .auth-container{
                border : 3px solid rgb(209, 222, 209);
                
            }

            .auth-sidebar {
                border : 3px solid rgb(179, 205, 190);
            }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.html" class="logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="Logo ILA - Institut de Linguistique Appliquée">
            </a>
            <ul class="nav-menu">
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="about.html">À propos</a></li>
                <li><a href="missions.html">Missions</a></li>
                <li><a href="recherche.html">Recherche</a></li>
                <li><a href="professeurs.php">Professeurs</a></li>
                <li><a href="ouvrages.html">Ouvrages</a></li>
                <li><a href="contact.html">Contact</a></li>
            </ul>
            <button class="menu-toggle" type="button" aria-label="Basculer le menu de navigation" title="Menu">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </nav>

    <main class="auth-page">
        <div class="auth-container">
            
            <?php if ($success): ?>
                
                <!-- Message de succès Après enregistrement -->
                <div class="auth-header">
                    <a href="../index.html" class="auth-logo">
                        <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
                    </a>
                    <h1>Inscription réussie !</h1>
                    <p class="auth-subtitle">Votre demande a été enregistrée avec succès</p>
                    <div class="professor-badge">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Demande de compte professeur soumise</span>
                    </div>
                </div>

                <div class="auth-steps">
                    <div class="step completed">
                        <span class="step-number"><i class="fas fa-check"></i></span>
                        <span class="step-text">Informations</span>
                    </div>
                    <div class="step completed">
                        <span class="step-number"><i class="fas fa-check"></i></span>
                        <span class="step-text">Enregistrement</span>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <span class="step-text">Vérification</span>
                    </div>
                </div>

                <div class="validation-content">
                    <div class="validation-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h3>Félicitations <?php echo htmlspecialchars($nom_complet); ?> !</h3>
                    
                    <div class="professor-validation">
                        <div class="professor-info">
                            <div class="professor-avatar" style="background: #2ecc71;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="professor-details">
                                <h4>Votre demande a été enregistrée</h4>
                                <p><?php echo htmlspecialchars($email_academique); ?></p>
                                <p><small>N° d'enregistrement: PRO<?php echo str_pad($professeur_id, 6, '0', STR_PAD_LEFT); ?></small></p>
                            </div>
                        </div>
                        
                        <div class="verification-status">
                            <p><strong>📋 Prochaines étapes :</strong></p>
                            <p>Votre profil sera examiné par notre comité académique. Vous recevrez un email de confirmation sous 48 heures.</p>
                        </div>
                        
                        <div class="instruction">
                            <p><strong>✅ Ce qui a été enregistré :</strong></p>
                            <ul style="text-align: left;">
                                <li>Vos informations personnelles et académiques</li>
                                <li>Vos diplômes universitaires</li>
                                <li>Vos expériences professionnelles</li>
                                <li>Vos spécialités de recherche</li>
                                <?php if ($photo_url): ?>
                                <li>Votre photo de profil</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 40px;">
                        <a href="professeurs.php" class="btn-primary">
                            <i class="fas fa-users"></i> Voir tous les professeurs
                        </a>
                        
                        <div class="auth-divider">
                            <span>ou</span>
                        </div>
                        
                        <a href="../index.html" class="btn-secondary">
                            <i class="fas fa-home"></i> Retour à l'accueil
                        </a>
                    </div>
                </div>
                
                <div class="auth-footer">
                    <p>Vous recevrez un email à <strong><?php echo htmlspecialchars($email_academique); ?></strong> une fois votre compte validé.</p>
                    <p>Questions ? <a href="contact.html">Contactez notre équipe académique</a></p>
                </div>

            <?php else: ?>


                <!--Début du formulaire d'inscription-->
                <div class="auth-header">
                    <a href="../index.html" class="auth-logo">
                        <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
                    </a>
                    <h1>Devenir Professeur Partenaire</h1>
                    <p class="auth-subtitle">Partagez vos connaissances et diffusez vos ouvrages</p>
                    <div class="professor-badge">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Accès à l'espace professeur</span>
                    </div>
                </div>

                <!--Affichage des erreurs de la base de données -->
                <?php if (!empty($errors['database'])): ?> 
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $errors['database']; ?>
                    </div>
                <?php endif; ?>

                <div class="auth-steps">
                    <div class="step active">
                        <span class="step-number">1</span>
                        <span class="step-text">Informations</span>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <span class="step-text">Vérification</span>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <span class="step-text">Validation</span>
                    </div>
                </div>
                
                <!-- Formulaire d'inscription Professeur -->
                <form id="professorRegistrationForm" class="auth-form" method="post" enctype="multipart/form-data"> <!--NE JAMMAIS INSERER L'ATTRIBUT NOVALIDATE ICI-->
                    <div class="form-section">
                        <h3><i class="fas fa-id-card"></i> Identité académique</h3>
                        
                        <div class="form-group profile-photo-upload">
                            <label>
                                <i class="fas fa-camera"></i> Photo de profil
                            </label>
                            <div class="photo-upload-container">
                                <div class="photo-preview" id="photoPreview">
                                    <i class="fas fa-user-tie"></i>
                                    <span>Aucune photo sélectionnée</span>
                                </div>
                                <div class="photo-upload-controls">
                                    <label for="photo" class="btn-upload">
                                        <i class="fas fa-upload"></i> Choisir une photo
                                    </label>
                                    <input type="file" id="photo" name="photo" accept="image/*" hidden>
                                    <small>Formats acceptés : JPG, PNG (max 5MB)</small>
                                </div>
                            </div>
                            <?php if (!empty($errors['photo'])): ?>
                                <div class="form-error"><?php echo $errors['photo']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="titre_academique">
                                    <i class="fas fa-graduation-cap"></i> Titre académique
                                    <span class="required">*</span>
                                </label>
                                <select id="titre_academique" name="titre_academique" required>
                                    <option value="">Sélectionnez votre titre</option>
                                    <option value="professeur" <?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'professeur') ? 'selected' : ''; ?>>Professeur des Universités</option>
                                    <option value="maitre_conf" <?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'maitre_conf') ? 'selected' : ''; ?>>Maître de Conférences</option>
                                    <option value="docteur" <?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'docteur') ? 'selected' : ''; ?>>Docteur</option>
                                    <option value="chercheur" <?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'chercheur') ? 'selected' : ''; ?>>Chercheur</option>
                                    <option value="enseignant" <?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'enseignant') ? 'selected' : ''; ?>>Enseignant-Chercheur</option>
                                    <option value="autre" <?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'autre') ? 'selected' : ''; ?>>Autre titre académique</option>
                                </select>
                                <?php if (!empty($errors['titre_academique'])): ?>
                                    <div class="form-error"><?php echo $errors['titre_academique']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group" id="autre_titre_field" style="<?php echo (isset($_POST['titre_academique']) && $_POST['titre_academique'] == 'autre') ? 'display:block;' : 'display:none;'; ?>">
                                <label for="autre_titre">
                                    <i class="fas fa-pen"></i> Précisez votre titre
                                </label>
                                <input type="text" id="autre_titre" name="autre_titre"
                                       placeholder="Votre titre académique"
                                       value="<?php echo isset($_POST['autre_titre']) ? htmlspecialchars($_POST['autre_titre']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nom_complet">
                                <i class="fas fa-user-tie"></i> Nom complet
                                <span class="required">*</span>
                            </label>
                            <input type="text" id="nom_complet" name="nom_complet" required
                                   placeholder="Prénom et nom"
                                   pattern="[A-Za-zÀ-ÿ\s-]{5,}"
                                   value="<?php echo isset($_POST['nom_complet']) ? htmlspecialchars($_POST['nom_complet']) : ''; ?>">
                            <?php if (!empty($errors['nom_complet'])): ?>
                                <div class="form-error"><?php echo $errors['nom_complet']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">  <!-- Bloc de l'espace de l'Email dans le formulaire -->
                                
                                <div class="form-group">
                                    
                                    <label for="email_academique">
                                        <i class="fas fa-envelope"></i> Email 
                                        <span class="required">*</span>
                                    </label>
                                    
                                    <!-- Pattern pour valider le format de l'email--Plus permissif -->
                                    <input type="email" id="email_academique" name="email_academique" required
                                        placeholder="Votre adresse email"  
                                        pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" 
                                        value="<?php echo isset($_POST['email_academique']) ? htmlspecialchars($_POST['email_academique']) : ''; ?>" >
                                        
                                     
                                    
                                    <?php if (!empty($errors['email_academique'])): ?>
                                        <div class="form-error"><?php echo $errors['email_academique']; ?></div>
                                    <?php endif; ?>
                                    
                                    <small class="form-hint">Utilisez votre email</small>
                                </div>
                                ...
                        </div> <!-- Fin du bloc de l'espace de l'Email dans le formulaire -->

                        <div class="form-group">
                            <label for="universites">
                                <i class="fas fa-university"></i> Université(s) d'affiliation
                                <span class="required">*</span>
                            </label>
                            <input type="text" id="universites" name="universites" required
                                   placeholder="Séparez par des virgules : Université Félix Houphouët-Boigny, Université de Montréal"
                                   value="<?php echo isset($_POST['universites']) ? htmlspecialchars($_POST['universites']) : ''; ?>">
                            <?php if (!empty($errors['universites'])): ?>
                                <div class="form-error"><?php echo $errors['universites']; ?></div>
                            <?php endif; ?>
                            <small class="form-hint">Séparez les différentes universités par des virgules</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-briefcase"></i> Expérience académique</h3>
                        
                        <!-- Section des diplômes -->
                        <div class="dynamic-list" id="diplomes_list">
                            <div class="list-header">
                                <h4><i class="fas fa-graduation-cap"></i> Diplômes universitaires</h4>
                                <button type="button" class="btn-small add-item" data-target="diplome">
                                    <i class="fas fa-plus"></i> Ajouter un diplôme
                                </button>
                            </div>
                            
                            <div class="list-items" id="diplomesContainer">
                                <!-- Les diplômes seront ajoutés dynamiquement ici -->
                            </div>
                        </div>

                        <!-- Section des expériences -->
                        <div class="dynamic-list" id="experiences_list">
                            <div class="list-header">
                                <h4><i class="fas fa-history"></i> Expérience professionnelle</h4>
                                <button type="button" class="btn-small add-item" data-target="experience">
                                    <i class="fas fa-plus"></i> Ajouter une expérience
                                </button>
                            </div>
                            
                            <div class="list-items" id="experiencesContainer">
                                <!-- Les expériences seront ajoutés dynamiquement ici -->
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="specialites">
                                <i class="fas fa-search"></i> Spécialités de Recherche
                                <span class="required">*</span>
                            </label>
                            <textarea id="specialites" name="specialites" required
                                      placeholder="Décrivez vos domaines de recherche, expertise, spécialités..."
                                      rows="4"><?php echo isset($_POST['specialites']) ? htmlspecialchars($_POST['specialites']) : ''; ?></textarea>
                            <?php if (!empty($errors['specialites'])): ?>
                                <div class="form-error"><?php echo $errors['specialites']; ?></div>
                            <?php endif; ?>
                            <small class="form-hint">Séparez les domaines par des virgules</small>
                        </div>

                        <div class="form-group">
                            <label for="bio">
                                <i class="fas fa-file-alt"></i> Biographie académique
                            </label>
                            <textarea id="bio" name="bio"
                                      placeholder="Présentez votre parcours, vos réalisations, vos publications..."
                                      rows="6"><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                            <small class="form-hint">Cette biographie apparaîtra sur votre page publique</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-network-wired"></i> Liens et contacts</h3>
                        
                        <div class="form-group">
                            <label for="site_web">
                                <i class="fas fa-globe"></i> Site web personnel
                            </label>
                            <input type="url" id="site_web" name="site_web"
                                   placeholder="https://votresite.com"
                                   value="<?php echo isset($_POST['site_web']) ? htmlspecialchars($_POST['site_web']) : ''; ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="linkedin_url">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </label>
                                <input type="url" id="linkedin_url" name="linkedin_url"
                                       placeholder="https://linkedin.com/in/votreprofil"
                                       value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="google_scholar">
                                    <i class="fas fa-graduation-cap"></i> Google Scholar
                                </label>
                                <input type="url" id="google_scholar" name="google_scholar"
                                       placeholder="https://scholar.google.com/citations?user=..."
                                       value="<?php echo isset($_POST['google_scholar']) ? htmlspecialchars($_POST['google_scholar']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="orcid">
                                <i class="fas fa-fingerprint"></i> ORCID iD
                            </label>
                            <input type="text" id="orcid" name="orcid"
                                   placeholder="0000-0000-0000-0000"
                                   pattern="\d{4}-\d{4}-\d{4}-\d{4}"
                                   value="<?php echo isset($_POST['orcid']) ? htmlspecialchars($_POST['orcid']) : ''; ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="telephone">
                                    <i class="fas fa-phone"></i> Téléphone
                                </label>
                                <input type="tel" id="telephone" name="telephone"
                                       placeholder="+225 XX XX XX XX"
                                       value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="adresse">
                                    <i class="fas fa-map-marker-alt"></i> Votre Adresse 
                                </label>
                                <textarea id="adresse" name="adresse" rows="3" placeholder="Adresse complète de votre institution"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="disponibilite">
                                    <i class="fas fa-calendar-alt"></i> Disponibilité pour contact
                                </label>
                                <select id="disponibilite" name="disponibilite">
                                    <option value="" <?php echo (empty($_POST['disponibilite'])) ? 'selected' : ''; ?>>Sélectionnez</option>
                                    <option value="tous_jours" <?php echo (isset($_POST['disponibilite']) && $_POST['disponibilite']=='tous_jours') ? 'selected' : ''; ?>>Tous les jours</option>
                                    <option value="jours_ouvres" <?php echo (isset($_POST['disponibilite']) && $_POST['disponibilite']=='jours_ouvres') ? 'selected' : ''; ?>>Jours ouvrables</option>
                                    <option value="sur_rdv" <?php echo (isset($_POST['disponibilite']) && $_POST['disponibilite']=='sur_rdv') ? 'selected' : ''; ?>>Sur rendez-vous</option>
                                    <option value="limite" <?php echo (isset($_POST['disponibilite']) && $_POST['disponibilite']=='limite') ? 'selected' : ''; ?>>Disponibilité limitée</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
            <h3><i class="fas fa-lock"></i> Sécurité du compte</h3>
    
    <div class="form-row">
        <div class="form-group">
            <label for="password">
                <i class="fas fa-key"></i> Mot de passe
                <span class="required">*</span>
            </label>
            <div class="password-input">
                
                <!-- Pattern pour valider le format du mot de passe -->
                <input type="password" id="password" name="password" required
                       placeholder="Minimum 12 caractères"
                       minlength="12"
                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$">
                 

                <button type="button" class="password-toggle" aria-label="Afficher le mot de passe">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?>
                <div class="form-error"><?php echo $errors['password']; ?></div>
            <?php endif; ?>
            <div class="password-strength">
                <div class="strength-bar"></div>
                <div class="strength-text">Force du mot de passe</div>
                <ul class="password-requirements">
                    <li data-rule="length">Minimum 12 caractères</li>
                    <li data-rule="uppercase">Une majuscule</li>
                    <li data-rule="lowercase">Une minuscule</li>
                    <li data-rule="number">Un chiffre</li>
                    <li data-rule="special">Un caractère spécial</li>
                </ul>
            </div>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">
                <i class="fas fa-key"></i> Confirmer le mot de passe
                <span class="required">*</span>
            </label>
            <div class="password-input">
                <input type="password" id="confirm_password" name="confirm_password" required
                       placeholder="Retapez votre mot de passe">
                <button type="button" class="password-toggle" aria-label="Afficher le mot de passe">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            <?php if (!empty($errors['confirm_password'])): ?>
                <div class="form-error"><?php echo $errors['confirm_password']; ?></div>
            <?php endif; ?>
            <div class="password-match">
                <i class="fas fa-check-circle"></i> <span>Les mots de passe correspondent</span>
            </div>
        </div>
    </div>
</div>

                    <div class="form-section">
                        <h3><i class="fas fa-file-contract"></i> Conditions de partenariat</h3>
                        
                        <div class="terms-box">
                            <div class="form-group">
                                <label class="checkbox agreement">
                                    <input type="checkbox" id="terms_prof" name="terms_prof" required
                                        <?php echo isset($_POST['terms_prof']) ? 'checked' : ''; ?>>
                                    <span>Je certifie que les informations fournies sont exactes et 
                                          j'accepte les <a href="conditions_professeurs.html" target="_blank">conditions de partenariat</a>
                                          <span class="required">*</span></span>
                                </label>
                                <?php if (!empty($errors['terms_prof'])): ?>
                                    <div class="form-error"><?php echo $errors['terms_prof']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-lg">
                            <i class="fas fa-chalkboard-teacher"></i> Devenir Professeur Partenaire
                        </button>
                        
                        <div class="auth-divider">
                            <span>Déjà partenaire ?</span>
                        </div>
                        
                        <a href="connexion.php?type=professeur" class="btn-secondary">
                            <i class="fas fa-sign-in-alt"></i> Accéder à mon espace
                        </a>
                    </div>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Les demandes d'inscription sont vérifiées manuellement sous 48h.</p>
                <p>Besoin d'aide ? <a href="contact.html">Contactez notre équipe académique</a></p>
            </div>
        </div>

        <div class="auth-sidebar">
            <div class="sidebar-content">
                <h2>Pourquoi devenir Professeur Partenaire ?</h2>
                
                <div class="stats-box">
                    <div class="stat-item">
                        <i class="fas fa-book-open"></i>
                        <div>
                            <h3>500+</h3>
                            <p>Ouvrages publiés</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-globe-africa"></i>
                        <div>
                            <h3>20+</h3>
                            <p>Pays couverts</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3>10K+</h3>
                            <p>Lecteurs mensuels</p>
                        </div>
                    </div>
                </div>

                <ul class="benefits-list">
                    <li>
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <h4>Statistiques avancées</h4>
                            <p>Suivez les performances de vos ouvrages en temps réel</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-coins"></i>
                        <div>
                            <h4>Rémunération attractive</h4>
                            <p>Commission compétitive sur chaque vente</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-tools"></i>
                        <div>
                            <h4>Outils de gestion</h4>
                            <p>Interface intuitive pour gérer vos publications</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h4>Protection des droits</h4>
                            <p>Vos droits d'auteur sont entièrement protégés</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-bullhorn"></i>
                        <div>
                            <h4>Promotion ciblée</h4>
                            <p>Vos ouvrages sont promus auprès du public concerné</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>Support dédié</h4>
                            <p>Équipe technique et éditoriale à votre service</p>
                        </div>
                    </li>
                </ul>
                
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"Grâce à la plateforme ILA, mes recherches ont touché un public international. Les outils de suivi sont exceptionnels."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://via.placeholder.com/50/1a3c40/ffffff?text=PD" alt="Photo de Prof. Diallo">
                        <div>
                            <h4>Prof. Diallo</h4>
                            <span>Université de Montréal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
         <div class="footer-container">
            <div class="footer-section">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" class="footer-logo">
                <p>Institut de Linguistique Appliquée d'Abidjan<br>
                Université Félix Houphouët‑Boigny</p>
            </div>
            <div class="footer-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="../index.html">Accueil</a></li>
                    <li><a href="about.html">À propos</a></li>
                    <li><a href="missions.html">Missions</a></li>
                    <li><a href="recherche.html">Recherche</a></li>
                    <li><a href="professeurs.php">Professeurs</a></li>
                    <li><a href="ouvrages.html">Ouvrages</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p><i class="fas fa-map-marker-alt"></i> Abidjan, Côte d'Ivoire</p>
                <p><i class="fas fa-envelope"></i> contact@ila.edu</p>
                <p><i class="fas fa-phone"></i>+225 01 40 54 40 00</p>
            </div>
            <div class="footer-section">
                <h3>Suivez-nous</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Institut de Linguistique Appliquée d'Abidjan. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Templates pour les éléments dynamiques -->
    <template id="diplomeTemplate">
        <div class="dynamic-item" data-type="diplome">
            <div class="item-header">
                <span class="item-number">Diplôme #<span class="item-index">1</span></span>
                <button type="button" class="btn-remove-item" aria-label="Supprimer ce diplôme">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="item-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Année</label>
                        <input type="number" name="diplome_annee[]" min="1950" max="<?php echo date('Y'); ?>" 
                               placeholder="2020" required>
                    </div>
                    <div class="form-group">
                        <label>Diplôme</label>
                        <input type="text" name="diplome_intitule[]" 
                               placeholder="Doctorat en Sciences du Langage" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Institution</label>
                    <input type="text" name="diplome_institution[]" 
                           placeholder="Université de Montréal" required>
                </div>
                <div class="form-group">
                    <label>Pays</label>
                    <input type="text" name="diplome_pays[]" placeholder="Canada">
                </div>
                <div class="form-group">
                    <label>Mention / Description</label>
                    <textarea name="diplome_mention[]" rows="2" 
                              placeholder="Mention Très Honorable avec félicitations"></textarea>
                </div>
            </div>
        </div>
    </template>

    <template id="experienceTemplate">
        <div class="dynamic-item" data-type="experience">
            <div class="item-header">
                <span class="item-number">Expérience #<span class="item-index">1</span></span>
                <button type="button" class="btn-remove-item" aria-label="Supprimer cette expérience">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="item-fields">
                <div class="form-group">
                    <label>Poste / Fonction</label>
                    <input type="text" name="experience_poste[]" 
                           placeholder="Professeur invité" required>
                </div>
                <div class="form-group">
                    <label>Institution</label>
                    <input type="text" name="experience_institution[]" 
                           placeholder="Université la Sapienza de Rome" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date début</label>
                        <input type="month" name="experience_debut[]">
                    </div>
                    <div class="form-group">
                        <label>Date fin</label>
                        <input type="month" name="experience_fin[]">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="experience_actuel[]"> Actuellement
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="experience_description[]" rows="3" 
                              placeholder="Description des responsabilités et réalisations"></textarea>
                </div>
            </div>
        </div>
    </template>

    <div class="modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <h3>Demande envoyée !</h3>
            <p>Votre candidature a été soumise avec succès.</p>
            <p>Notre équipe académique va examiner votre profil sous <strong>48 heures</strong>.</p>
            <p>Vous recevrez un email de confirmation à <strong id="profEmail"></strong>.</p>
            <div class="modal-actions">
                <a href="../index.html" class="btn-primary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
                <a href="contact.html" class="btn-secondary">
                    <i class="fas fa-envelope"></i> Nous contacter
                </a>
            </div>
        </div>
    </div>

    <script src="../scripts/professor-form.js"></script>
    
    <script> <!-- Script pour la validation du mot de passe et autres fonctionnalités -->
    document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    const passwordMatch = document.querySelector('.password-match');
    
    const passwordRules = {
        length: { regex: /.{12,}/ },
        uppercase: { regex: /[A-Z]/ },
        lowercase: { regex: /[a-z]/ },
        number: { regex: /[0-9]/ },
        special: { regex: /[@$!%*?&]/ }
    };

    // Fonction pour vérifier la force du mot de passe
    function checkPasswordStrength() {
        if (!passwordInput || !strengthBar || !strengthText) return;
        
        const password = passwordInput.value;
        
        if (!password) {
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#ddd';
            strengthText.textContent = 'Force du mot de passe';
            return;
        }

        // Calculer la force
        let score = 0;
        Object.keys(passwordRules).forEach(rule => {
            const li = document.querySelector(`[data-rule="${rule}"]`);
            if (li && passwordRules[rule].regex.test(password)) {
                li.classList.add('valid');
                score++;
            } else if (li) {
                li.classList.remove('valid');
            }
        });

        // Mettre à jour la barre
        const percentage = (score / Object.keys(passwordRules).length) * 100;
        strengthBar.style.width = `${percentage}%`;
        
        let color, text;
        if (percentage < 40) {
            color = '#e74c3c';
            text = 'Faible';
        } else if (percentage < 70) {
            color = '#f39c12';
            text = 'Moyen';
        } else if (percentage < 90) {
            color = '#3498db';
            text = 'Bon';
        } else {
            color = '#2ecc71';
            text = 'Excellent';
        }
        
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = `Force du mot de passe: ${text}`;
    }

    // Fonction pour vérifier la correspondance des mots de passe
    function checkPasswordMatch() {
        if (!passwordInput || !confirmInput || !passwordMatch) return;
        
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (!confirm) {
            passwordMatch.style.opacity = '0';
            return;
        }

        if (password === confirm) {
            passwordMatch.classList.add('valid');
            passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i><span>Les mots de passe correspondent</span>';
        } else {
            passwordMatch.classList.remove('valid');
            passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i><span>Les mots de passe ne correspondent pas</span>';
        }
        
        passwordMatch.style.opacity = '1';
    }

    // Écouteurs d'événements
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength();
            checkPasswordMatch();
        });
    }
    
    if (confirmInput) {
        confirmInput.addEventListener('input', checkPasswordMatch);
    }

    // Toggle pour afficher/masquer le mot de passe
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.closest('.password-input').querySelector('input');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
                this.setAttribute('aria-label', 'Masquer le mot de passe');
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
                this.setAttribute('aria-label', 'Afficher le mot de passe');
            }
        });
    });

    // Validation en temps réel
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearError(this);
        });
    });

    function validateField(field) {
        const value = field.value.trim();
        const fieldId = field.id;
        const errorElement = document.getElementById(`${fieldId}-error`);
        
        if (!errorElement) return true;

        errorElement.textContent = '';
        field.classList.remove('error');

        if (field.hasAttribute('required') && !value) {
            showError(field, 'Ce champ est obligatoire');
            return false;
        }

        return true;
    }

    function showError(field, message) {
        field.classList.add('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function clearError(field) {
        field.classList.remove('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }
});
</script>
</body>
</html>