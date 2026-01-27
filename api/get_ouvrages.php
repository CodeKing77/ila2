<?php
/**
 * API pour récupérer les ouvrages - Version finale --Premier fichier des get_ouvrages - Generes par deepseek , avant claude
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration de la base de données (directement ici pour simplifier)
$host = 'localhost';
$dbname = 'ila_publications_db';
$username = 'root';
$password = '';

try {
    // Connexion directe à la base de données
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Récupérer les paramètres de la requête
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 9;
    $offset = ($page - 1) * $limit;
    
    // Paramètres de filtrage
    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'all';
    $format = isset($_GET['format']) ? $_GET['format'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'date-desc';
    
    // Construction de la requête SQL
    $sql = "SELECT o.*, c.nom as categorie_nom, c.couleur as categorie_couleur 
            FROM ouvrages o 
            LEFT JOIN categories_ouvrages c ON o.categorie_id = c.id 
            WHERE o.is_active = 1";
    
    $params = [];
    
    // Filtre par catégorie
    if ($categorie !== 'all' && in_array($categorie, ['scientifique', 'didactique', 'culturel'])) {
        $sql .= " AND c.slug = :categorie";
        $params[':categorie'] = $categorie;
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
        $sql .= " AND (o.titre LIKE :search OR o.description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Tri
    switch ($sort) {
        case 'date-asc':
            $sql .= " ORDER BY o.annee_publication ASC, o.created_at ASC";
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
            'description' => $ouvrage['description'] ?? '',
            'categorie' => [
                'id' => (int)$ouvrage['categorie_id'],
                'nom' => $ouvrage['categorie_nom'] ?? 'Non catégorisé',
                'slug' => $ouvrage['categorie_id'],
                'couleur' => $ouvrage['categorie_couleur'] ?? '#cccccc'
            ],
            'couverture' => $ouvrage['couverture_url'],
            'annee' => $ouvrage['annee_publication'],
            'langue' => $ouvrage['langue'] ?? 'Français',
            'stock' => (int)$ouvrage['stock'],
            'isPhysical' => (bool)$ouvrage['is_physical'],
            'isDigital' => (bool)$ouvrage['is_digital'],
            'prix' => $ouvrage['prix_promotion'],
            'formats' => [
                'physique' => (bool)$ouvrage['is_physical'],
                'numerique' => (bool)$ouvrage['is_digital']
            ],
            'slug' => $ouvrage['slug'],
            'isbn' => $ouvrage['isbn'] ?? '',
            'editeur' => $ouvrage['editeur'] ?? '',
            'pages' => $ouvrage['nombre_pages'] ?? 0
        ];
    }
    
    // Construire la réponse
    $response = [
        'success' => true,
        'data' => [
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
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Autre type d'erreur
    $response = [
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ];
    
    http_response_code(500);
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>