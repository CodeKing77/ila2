<?php
/**
 * Traitement de la connexion des professeurs
 * Fichier: api/process_login.php
 */

require_once '../config/config.php';

// Démarrer la session
startSecureSession();

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Méthode non autorisée');
}

// Récupérer et nettoyer les données
$email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validation des champs
if (empty($email) || empty($password)) {
    sendJsonResponse(false, 'Veuillez remplir tous les champs');
}

// Validation du format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Format d\'email invalide');
}

// Connexion à la base de données
$pdo = getDbConnection();
if (!$pdo) {
    sendJsonResponse(false, 'Erreur de connexion au serveur');
}

try {
    // Rechercher le professeur par email
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nom_complet,
            email_academique,
            password_hash,
            titre_academique,
            photo_url,
            is_active,
            email_verified_at
        FROM professeurs
        WHERE email_academique = :email
        LIMIT 1
    ");
    
    $stmt->execute(['email' => $email]);
    $professeur = $stmt->fetch();
    
    // Vérifier si le professeur existe
    if (!$professeur) {
        // Pause de sécurité pour éviter les attaques par timing
        usleep(500000); // 0.5 seconde
        sendJsonResponse(false, 'Email ou mot de passe incorrect');
    }
    
    // Vérifier si le compte est actif
    if ($professeur['is_active'] != 1) {
        sendJsonResponse(false, 'Votre compte est désactivé. Contactez l\'administrateur.');
    }
    
    // Vérifier si l'email est validé
    if (empty($professeur['email_verified_at'])) {
        sendJsonResponse(false, 'Veuillez valider votre email avant de vous connecter.');
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $professeur['password_hash'])) {
        // Pause de sécurité
        usleep(500000);
        sendJsonResponse(false, 'Email ou mot de passe incorrect');
    }
    
    // Générer un token de session unique
    $sessionToken = bin2hex(random_bytes(32));
    
    // Séparer le nom complet en prénom et nom
    $nomParts = explode(' ', $professeur['nom_complet'], 2);
    $prenom = $nomParts[0] ?? '';
    $nom = $nomParts[1] ?? $professeur['nom_complet'];
    
    // Créer la session
    $_SESSION['professeur_id'] = $professeur['id'];
    $_SESSION['professeur_email'] = $professeur['email_academique'];
    $_SESSION['professeur_nom'] = $professeur['nom_complet'];
    $_SESSION['professeur_prenom'] = $prenom;
    $_SESSION['professeur_titre'] = $professeur['titre_academique'];
    $_SESSION['session_token'] = $sessionToken;
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Option "Se souvenir de moi"
    if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
        $_SESSION['remember_me'] = true;
        // Prolonger la durée de vie du cookie de session
        setcookie(
            session_name(),
            session_id(),
            time() + SESSION_REMEMBER_LIFETIME,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }
    
    // Mettre à jour la date de dernière connexion
    $updateStmt = $pdo->prepare("
        UPDATE professeurs 
        SET last_login = NOW() 
        WHERE id = :id
    ");
    $updateStmt->execute(['id' => $professeur['id']]);
    
    // Log de connexion (optionnel - pour audit)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO login_logs (professeur_id, ip_address, user_agent, login_time)
            VALUES (:professeur_id, :ip_address, :user_agent, NOW())
        ");
        $logStmt->execute([
            'professeur_id' => $professeur['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Table login_logs n'existe peut-être pas - ignorer l'erreur
        error_log("Log de connexion non enregistré: " . $e->getMessage());
    }
    
    // Préparer les données utilisateur pour la réponse
    $userData = [
        'id' => $professeur['id'],
        'nom' => $nom,
        'prenom' => $prenom,
        'nom_complet' => $professeur['nom_complet'],
        'email' => $professeur['email_academique'],
        'titre_academique' => $professeur['titre_academique'],
        'photo_url' => $professeur['photo_url']
    ];
    
    // Réponse de succès
    sendJsonResponse(true, 'Connexion réussie', [
        'user' => $userData,
        'redirect' => 'espace_professeur/dashboard_personnalise.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la connexion: " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue. Veuillez réessayer.');
}