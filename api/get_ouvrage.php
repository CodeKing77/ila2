<?php
/**
 * API pour récupérer un ouvrage
 * Fichier: api/get_ouvrage.php  nouveau fichier genere par claudeAI
 */

require_once '../config/config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
startSecureSession();

// Vérifier l'authentification
if (!isLoggedIn() || !validateSession()) {
    sendJsonResponse(false, 'Non autorisé');
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, 'Méthode non autorisée');
}

// Récupérer l'ID
$ouvrage_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$ouvrage_id) {
    sendJsonResponse(false, 'ID invalide');
}

// Connexion DB
$pdo = getDbConnection();
if (!$pdo) {
    sendJsonResponse(false, 'Erreur de connexion à la base de données');
}

try {
    // Récupérer l'ouvrage (vérifier qu'il appartient au professeur connecté)
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.nom as categorie_nom
        FROM ouvrages o
        LEFT JOIN categories_ouvrages c ON o.categorie_id = c.id
        WHERE o.id = :id 
        AND o.professeur_id = :professeur_id
        LIMIT 1
    ");
    
    $stmt->execute([
        'id' => $ouvrage_id,
        'professeur_id' => $_SESSION['professeur_id']
    ]);
    
    $ouvrage = $stmt->fetch();
    
    if (!$ouvrage) {
        sendJsonResponse(false, 'Ouvrage non trouvé');
    }
    
    sendJsonResponse(true, 'Ouvrage récupéré', ['ouvrage' => $ouvrage]);
    
} catch (PDOException $e) {
    error_log("Erreur get_ouvrage: " . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de la récupération de l\'ouvrage');
}
