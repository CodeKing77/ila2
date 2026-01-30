<?php
/**
 * API pour récupérer les ouvrages (liste) ou un ouvrage spécifique
 * Fichier: api/get_ouvrages.php
 * FIchier fusionné le: 29-01-2026 avec api/get_ouvrage.php
 */

// Définir le chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// ===========================================
// MODE 1 : RÉCUPÉRER UN SEUL OUVRAGE PAR ID
// ===========================================
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    // Démarrer la session pour vérifier l'authentification
    startSecureSession();
    
    // Vérifier l'authentification (requis pour modifier)
    if (!isLoggedIn() || !validateSession()) {
        sendJsonResponse(false, 'Non autorisé');
        exit;
    }
    
    $ouvrage_id = intval($_GET['id']);
    
    if (!$ouvrage_id) {
        sendJsonResponse(false, 'ID invalide');
        exit;
    }
    
    // Connexion DB
    $pdo = getDbConnection();
    if (!$pdo) {
        sendJsonResponse(false, 'Erreur de connexion à la base de données');
        exit;
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
            sendJsonResponse(false, 'Ouvrage non trouvé ou non autorisé');
            exit;
        }
        
        sendJsonResponse(true, 'Ouvrage récupéré', ['ouvrage' => $ouvrage]);
        exit;
        
    } catch (PDOException $e) {
        error_log("Erreur get_ouvrage: " . $e->getMessage());
        sendJsonResponse(false, 'Erreur lors de la récupération de l\'ouvrage: ' . $e->getMessage());
        exit;
    }
}

// ===========================================
// MODE 2 : RÉCUPÉRER LA LISTE DES OUVRAGES
// ===========================================

try {
    // Configuration de la base de données
    $pdo = getDbConnection();
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Récupérer les paramètres de la requête
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 9;
    $offset = ($page - 1) * $limit;
    
    // Paramètres de filtrage
    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'all';
    $format = isset($_GET['format']) ? $_GET['format'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'date-desc';
    
    // Construction de la requête SQL avec informations professeur
    $sql = "SELECT o.*, 
            c.nom as categorie_nom, 
            c.couleur as categorie_couleur,
            p.nom_complet as auteur_nom,
            p.titre_academique as auteur_titre
            FROM ouvrages o 
            LEFT JOIN categories_ouvrages c ON o.categorie_id = c.id 
            LEFT JOIN professeurs p ON o.professeur_id = p.id
            WHERE o.is_active = 1";
    
    $params = [];
    
    // Filtre par catégorie
    if ($categorie !== 'all' && in_array($categorie, ['scientifique', 'didactique', 'culturel'])) {
        $sql .= " AND LOWER(c.nom) LIKE :categorie";
        $params[':categorie'] = "%" . strtolower($categorie) . "%";
    }
    
    // Filtre par format
    if ($format !== 'all') {
        if ($format === 'numerique') {
            $sql .= " AND o.is_digital = 1";
        } elseif ($format === 'physique') {
            $sql .= " AND o.is_physical = 1";
        }
    }
    
    // Filtre par recherche
    if (!empty($search)) {
        $sql .= " AND (o.titre LIKE :search 
                  OR o.description LIKE :search 
                  OR o.resume LIKE :search
                  OR p.nom_complet LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Tri
    switch ($sort) {
        case 'date-asc':
            $sql .= " ORDER BY o.annee_publication ASC, o.created_at ASC";
            break;
        case 'titre-asc':
            $sql .= " ORDER BY o.titre ASC";
            break;
        case 'titre-desc':
            $sql .= " ORDER BY o.titre DESC";
            break;
        case 'price-asc':
            $sql .= " ORDER BY COALESCE(o.prix_promotion, 0) ASC";
            break;
        case 'price-desc':
            $sql .= " ORDER BY COALESCE(o.prix_promotion, 0) DESC";
            break;
        default: // date-desc
            $sql .= " ORDER BY o.annee_publication DESC, o.created_at DESC";
    }
    
    // Compter le nombre total de résultats (pour la pagination)
    $count_sql = "SELECT COUNT(*) as total FROM ($sql) as subquery";
    $stmt_count = $pdo->prepare($count_sql);
    
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    
    $stmt_count->execute();
    $total_result = $stmt_count->fetch();
    $total = $total_result['total'];
    $total_pages = ceil($total / $limit);
    
    // Ajouter la pagination à la requête principale
    $sql .= " LIMIT :limit OFFSET :offset";
    
    // Préparer et exécuter la requête principale
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres de filtrage
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Liaison des paramètres de pagination
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $ouvrages = $stmt->fetchAll();
    
    // Formater les résultats pour le frontend
    $formatted_ouvrages = [];
    foreach ($ouvrages as $ouvrage) {
        $formatted_ouvrages[] = [
            'id' => (int)$ouvrage['id'],
            'titre' => $ouvrage['titre'],
            'sous_titre' => $ouvrage['sous_titre'] ?? '',
            'resume' => $ouvrage['resume'] ?? '',
            'description' => $ouvrage['description'] ?? '',
            'auteur_nom' => $ouvrage['auteur_nom'] ?? 'Auteur inconnu',
            'auteur_titre' => $ouvrage['auteur_titre'] ?? '',
            'categorie_nom' => $ouvrage['categorie_nom'] ?? 'Non catégorisé',
            'categorie_id' => $ouvrage['categorie_id'],
            'categorie_couleur' => $ouvrage['categorie_couleur'] ?? '#cccccc',
            'couverture_url' => $ouvrage['couverture_url'],
            'fichier_pdf_url' => $ouvrage['fichier_pdf_url'],
            'annee_publication' => $ouvrage['annee_publication'],
            'langue' => $ouvrage['langue'] ?? 'Français',
            'editeur' => $ouvrage['editeur'] ?? '',
            'isbn' => $ouvrage['isbn'] ?? '',
            'nombre_pages' => $ouvrage['nombre_pages'] ?? 0,
            'stock' => (int)$ouvrage['stock'],
            'is_physical' => (int)$ouvrage['is_physical'],
            'is_digital' => (int)$ouvrage['is_digital'],
            'prix_promotion' => $ouvrage['prix_promotion'],
            'slug' => $ouvrage['slug'],
            'views_count' => (int)$ouvrage['views_count'],
            'created_at' => $ouvrage['created_at'],
            'updated_at' => $ouvrage['updated_at']
        ];
    }
    
    // Construire la réponse
    $response = [
        'success' => true,
        'ouvrages' => $formatted_ouvrages,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $total_pages
        ],
        'filters' => [
            'categorie' => $categorie,
            'format' => $format,
            'search' => $search,
            'sort' => $sort
        ]
    ];
    
    // Ajouter des informations de débogage si demandé
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        $response['debug'] = [
            'sql' => $sql,
            'params' => $params,
            'total_results' => $total,
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    // Erreur de base de données
    $response = [
        'success' => false,
        'error' => 'Erreur de base de données',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
    
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    http_response_code(500);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Autre type d'erreur
    $response = [
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ];
    
    http_response_code(500);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
