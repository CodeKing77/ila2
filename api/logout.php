<?php
/**
 * Déconnexion du professeur
 * Fichier: api/logout.php
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

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    sendJsonResponse(false, 'Méthode non autorisée');
}

try {
    // Log de déconnexion (optionnel - pour audit)
    if (isset($_SESSION['professeur_id'])) {
        $pdo = getDbConnection();
        
        if ($pdo) {
            try {
                $logStmt = $pdo->prepare("
                    INSERT INTO logout_logs (professeur_id, ip_address, logout_time)
                    VALUES (:professeur_id, :ip_address, NOW())
                ");
                $logStmt->execute([
                    'professeur_id' => $_SESSION['professeur_id'],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            } catch (PDOException $e) {
                // Table logout_logs n'existe peut-être pas - ignorer l'erreur
                error_log("Log de déconnexion non enregistré: " . $e->getMessage());
            }
        }
    }
    
    // Sauvegarder l'ID avant de détruire la session (pour les logs)
    $professeur_id = $_SESSION['professeur_id'] ?? null;
    
    // Détruire toutes les variables de session
    $_SESSION = [];
    
    // Supprimer le cookie de session
    if (isset($_COOKIE[SESSION_NAME])) {
        $params = session_get_cookie_params();
        setcookie(
            SESSION_NAME,
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Détruire la session
    session_destroy();
    
    // Réponse de succès
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Déconnexion réussie',
        'redirect' => '/site_ila/index.html'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erreur lors de la déconnexion: " . $e->getMessage());
    
    // Même en cas d'erreur, détruire la session
    $_SESSION = [];
    session_destroy();
    
    sendJsonResponse(true, 'Déconnexion effectuée');
}
