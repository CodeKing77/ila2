<?php
/**
 * API de mise à jour du profil professeur
 * Fichier: api/update_profil.php
 */

require_once '../config/config.php';

// Démarrer la session
startSecureSession();

// Vérifier l'authentification
if (!isLoggedIn() || !validateSession()) {
    sendJsonResponse(false, 'Non autorisé');
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Méthode non autorisée');
}

// Connexion DB
$pdo = getDbConnection();
if (!$pdo) {
    sendJsonResponse(false, 'Erreur de connexion à la base de données');
}

$professeur_id = $_SESSION['professeur_id'];

try {
    // Récupérer les données actuelles
    $checkStmt = $pdo->prepare("SELECT photo_url, email_academique FROM professeurs WHERE id = :id");
    $checkStmt->execute(['id' => $professeur_id]);
    $currentData = $checkStmt->fetch();
    
    if (!$currentData) {
        sendJsonResponse(false, 'Professeur non trouvé');
    }
    
    // Récupération et validation des données
    $nom_complet = isset($_POST['nom_complet']) ? trim($_POST['nom_complet']) : '';
    $email_academique = isset($_POST['email_academique']) ? trim($_POST['email_academique']) : '';
    $titre_academique = isset($_POST['titre_academique']) ? trim($_POST['titre_academique']) : null;
    $specialites = isset($_POST['specialites']) ? trim($_POST['specialites']) : null;
    $universites = isset($_POST['universites']) ? trim($_POST['universites']) : null;
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : null;
    $site_web = isset($_POST['site_web']) ? trim($_POST['site_web']) : null;
    
    // Validation
    if (empty($nom_complet)) {
        sendJsonResponse(false, 'Le nom complet est requis');
    }
    
    if (empty($email_academique) || !filter_var($email_academique, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, 'Email invalide');
    }
    
    // Vérifier si l'email existe déjà (pour un autre professeur)
    if ($email_academique !== $currentData['email_academique']) {
        $emailCheck = $pdo->prepare("SELECT id FROM professeurs WHERE email_academique = :email AND id != :id");
        $emailCheck->execute(['email' => $email_academique, 'id' => $professeur_id]);
        
        if ($emailCheck->fetch()) {
            sendJsonResponse(false, 'Cet email est déjà utilisé par un autre compte');
        }
    }
    
    // Gestion de l'upload de la photo
    $photo_url = $currentData['photo_url'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadPhoto($_FILES['photo'], $professeur_id);
        
        if ($uploadResult['success']) {
            // Supprimer l'ancienne photo
            if (!empty($currentData['photo_url']) && file_exists('../' . $currentData['photo_url'])) {
                @unlink('../' . $currentData['photo_url']);
            }
            $photo_url = $uploadResult['path'];
        } else {
            sendJsonResponse(false, $uploadResult['error']);
        }
    }
    
    // Mise à jour du profil
    $stmt = $pdo->prepare("
        UPDATE professeurs SET
            nom_complet = :nom_complet,
            email_academique = :email_academique,
            titre_academique = :titre_academique,
            specialites = :specialites,
            universites = :universites,
            bio = :bio,
            telephone = :telephone,
            site_web = :site_web,
            photo_url = :photo_url,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $result = $stmt->execute([
        'nom_complet' => $nom_complet,
        'email_academique' => $email_academique,
        'titre_academique' => $titre_academique,
        'specialites' => $specialites,
        'universites' => $universites,
        'bio' => $bio,
        'telephone' => $telephone,
        'site_web' => $site_web,
        'photo_url' => $photo_url,
        'id' => $professeur_id
    ]);
    
    if ($result) {
        // Mettre à jour les informations de session
        $_SESSION['professeur_nom'] = $nom_complet;
        $_SESSION['professeur_email'] = $email_academique;
        $_SESSION['professeur_titre'] = $titre_academique;
        
        sendJsonResponse(true, 'Profil mis à jour avec succès');
    } else {
        sendJsonResponse(false, 'Erreur lors de la mise à jour');
    }
    
} catch (PDOException $e) {
    error_log("Erreur update profil: " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la mise à jour du profil');
}

/**
 * Upload de la photo de profil
 */
function uploadPhoto($file, $professeur_id) {
    $maxSize = 2 * 1024 * 1024; // 2 Mo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    
    // Vérifications
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 2Mo)'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Format non autorisé (JPG, PNG uniquement)'];
    }
    
    // Créer le dossier s'il n'existe pas
    $uploadDir = '../assets/uploads/professeurs/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'prof_' . $professeur_id . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'path' => 'assets/uploads/professeurs/' . $filename,
            'filename' => $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
}
