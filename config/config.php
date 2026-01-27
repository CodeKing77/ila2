<?php
/**
 * Configuration de la base de données - ILA Publications
 * Fichier de configuration centralisé
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ila_publications_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration de la session
define('SESSION_NAME', 'ILA_SESSION');
define('SESSION_LIFETIME', 3600); // 1 heure en secondes
define('SESSION_REMEMBER_LIFETIME', 2592000); // 30 jours en secondes

// Configuration des tokens
define('TOKEN_EXPIRY_HOURS', 24); // Durée de validité du token de réinitialisation

// Configuration du site
define('SITE_URL', 'http://localhost/site_ila');
define('SITE_NAME', 'Institut de Linguistique Appliquée');

/**
 * Fonction pour obtenir la connexion PDO
 * @return PDO|null
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Démarrer une session sécurisée
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_name(SESSION_NAME);
        session_start();
        
        // Régénération de l'ID de session pour prévenir les attaques de fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

/**
 * Vérifier si un utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['professeur_id']) && 
           isset($_SESSION['professeur_email']) &&
           isset($_SESSION['session_token']);
}

/**
 * Vérifier la validité de la session
 * @return bool
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Vérifier le timeout de la session
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        $timeout = isset($_SESSION['remember_me']) ? SESSION_REMEMBER_LIFETIME : SESSION_LIFETIME;
        
        if ($inactive > $timeout) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Nettoyer et valider l'input utilisateur
 * @param string $data
 * @return string
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Envoyer une réponse JSON
 * @param bool $success
 * @param string $message
 * @param array $data
 */
function sendJsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
