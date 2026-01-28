<?php
/**
 * Vérification de l'état de la session
 * Fichier: api/check_session.php
 */

// Définir le chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
startSecureSession();

// Headers pour éviter le cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json; charset=utf-8');

try {
    // Vérifier si la session est valide
    if (!validateSession()) {
        echo json_encode([
            'success' => true,
            'is_logged_in' => false,
            'message' => 'Session expirée ou non connecté'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // La session est valide, récupérer les informations du professeur
    $pdo = getDbConnection();
    
    if (!$pdo) {
        echo json_encode([
            'success' => false,
            'is_logged_in' => false,
            'message' => 'Erreur de connexion au serveur'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Récupérer les informations à jour du professeur
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nom_complet,
            email_academique,
            titre_academique,
            photo_url,
            is_active
        FROM professeurs
        WHERE id = :id
        LIMIT 1
    ");
    
    $stmt->execute(['id' => $_SESSION['professeur_id']]);
    $professeur = $stmt->fetch();
    
    // Vérifier si le professeur existe toujours et est actif
    if (!$professeur || $professeur['is_active'] != 1) {
        // Détruire la session si le compte n'existe plus ou est désactivé
        session_unset();
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'is_logged_in' => false,
            'message' => 'Compte désactivé ou supprimé'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Séparer le nom complet
    $nomParts = explode(' ', $professeur['nom_complet'], 2);
    $prenom = $nomParts[0] ?? '';
    $nom = $nomParts[1] ?? $professeur['nom_complet'];
    
    // Préparer les données utilisateur
    $userData = [
        'id' => $professeur['id'],
        'nom' => $nom,
        'prenom' => $prenom,
        'nom_complet' => $professeur['nom_complet'],
        'email' => $professeur['email_academique'],
        'titre_academique' => $professeur['titre_academique'],
        'photo_url' => $professeur['photo_url']
    ];
    
    // Calculer le temps restant avant expiration
    $lastActivity = $_SESSION['last_activity'] ?? time();
    $timeout = isset($_SESSION['remember_me']) ? SESSION_REMEMBER_LIFETIME : SESSION_LIFETIME;
    $timeRemaining = $timeout - (time() - $lastActivity);
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'is_logged_in' => true,
        'user' => $userData,
        'session_info' => [
            'time_remaining' => $timeRemaining,
            'remember_me' => isset($_SESSION['remember_me']),
            'last_activity' => date('Y-m-d H:i:s', $lastActivity)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification de session: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'is_logged_in' => false,
        'message' => 'Erreur lors de la vérification de la session'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Erreur inattendue: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'is_logged_in' => false,
        'message' => 'Erreur inattendue'
    ], JSON_UNESCAPED_UNICODE);
}