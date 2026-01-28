<?php
/**
 * API de gestion des ouvrages
 * Fichier: api/manage_ouvrages.php
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

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

$action = isset($_POST['action']) ? cleanInput($_POST['action']) : '';
$professeur_id = $_SESSION['professeur_id'];

// ===========================================
// AJOUTER UN OUVRAGE
// ===========================================
if ($action === 'add') {
    try {
        // Récupération et validation des données
        $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
        $sous_titre = isset($_POST['sous_titre']) ? trim($_POST['sous_titre']) : null;
        $categorie_id = isset($_POST['categorie_id']) ? intval($_POST['categorie_id']) : null;
        $annee_publication = isset($_POST['annee_publication']) ? intval($_POST['annee_publication']) : null;
        $resume = isset($_POST['resume']) ? trim($_POST['resume']) : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : null;
        $editeur = isset($_POST['editeur']) ? trim($_POST['editeur']) : null;
        $nombre_pages = isset($_POST['nombre_pages']) ? intval($_POST['nombre_pages']) : null;
        $langue = isset($_POST['langue']) ? trim($_POST['langue']) : 'Français';
        $is_physical = isset($_POST['is_physical']) ? 1 : 0;
        $is_digital = isset($_POST['is_digital']) ? 1 : 0;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        
        // Validation
        if (empty($titre)) {
            sendJsonResponse(false, 'Le titre est requis');
        }
        
        // Générer un slug unique
        $slug = generateSlug($titre);
        $slugBase = $slug;
        $counter = 1;
        
        while (slugExists($pdo, $slug)) {
            $slug = $slugBase . '-' . $counter;
            $counter++;
        }
        
        // Gestion de l'upload de la couverture
        $couverture_url = null;
        if (isset($_FILES['couverture']) && $_FILES['couverture']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadCouverture($_FILES['couverture']);
            if ($uploadResult['success']) {
                $couverture_url = $uploadResult['path'];
            }
        }
        
        // Gestion de l'upload du PDF
        $fichier_pdf_url = null;
        if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadPDF($_FILES['fichier_pdf']);
            if ($uploadResult['success']) {
                $fichier_pdf_url = $uploadResult['path'];
            } else {
                sendJsonResponse(false, $uploadResult['error']);
            }
        }
        
        // Insertion dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO ouvrages (
                professeur_id, titre, sous_titre, slug, categorie_id,
                annee_publication, resume, description, isbn, editeur,
                nombre_pages, langue, couverture_url, fichier_pdf_url,
                is_physical, is_digital, stock, is_active, created_at
            ) VALUES (
                :professeur_id, :titre, :sous_titre, :slug, :categorie_id,
                :annee_publication, :resume, :description, :isbn, :editeur,
                :nombre_pages, :langue, :couverture_url, :fichier_pdf_url,
                :is_physical, :is_digital, :stock, 1, NOW()
            )
        ");
        
        $stmt->execute([
            'professeur_id' => $professeur_id,
            'titre' => $titre,
            'sous_titre' => $sous_titre,
            'slug' => $slug,
            'categorie_id' => $categorie_id,
            'annee_publication' => $annee_publication,
            'resume' => $resume,
            'description' => $description,
            'isbn' => $isbn,
            'editeur' => $editeur,
            'nombre_pages' => $nombre_pages,
            'langue' => $langue,
            'couverture_url' => $couverture_url,
            'fichier_pdf_url' => $fichier_pdf_url,
            'is_physical' => $is_physical,
            'is_digital' => $is_digital,
            'stock' => $stock
        ]);
        
        sendJsonResponse(true, 'Ouvrage ajouté avec succès', ['id' => $pdo->lastInsertId()]);
        
    } catch (PDOException $e) {
        error_log("Erreur ajout ouvrage: " . $e->getMessage());
        sendJsonResponse(false, 'Erreur lors de l\'ajout de l\'ouvrage');
    }
}

// ===========================================
// MODIFIER UN OUVRAGE
// ===========================================
else if ($action === 'edit') {
    try {
        $ouvrage_id = isset($_POST['ouvrage_id']) ? intval($_POST['ouvrage_id']) : 0;
        
        if (!$ouvrage_id) {
            sendJsonResponse(false, 'ID d\'ouvrage invalide');
        }
        
        // Vérifier que l'ouvrage appartient au professeur
        $checkStmt = $pdo->prepare("SELECT id, couverture_url, fichier_pdf_url FROM ouvrages WHERE id = :id AND professeur_id = :professeur_id");
        $checkStmt->execute(['id' => $ouvrage_id, 'professeur_id' => $professeur_id]);
        $ouvrage = $checkStmt->fetch();
        
        if (!$ouvrage) {
            sendJsonResponse(false, 'Ouvrage non trouvé ou non autorisé');
        }
        
        // Récupération des données
        $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
        $sous_titre = isset($_POST['sous_titre']) ? trim($_POST['sous_titre']) : null;
        $categorie_id = isset($_POST['categorie_id']) ? intval($_POST['categorie_id']) : null;
        $annee_publication = isset($_POST['annee_publication']) ? intval($_POST['annee_publication']) : null;
        $resume = isset($_POST['resume']) ? trim($_POST['resume']) : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : null;
        $editeur = isset($_POST['editeur']) ? trim($_POST['editeur']) : null;
        $nombre_pages = isset($_POST['nombre_pages']) ? intval($_POST['nombre_pages']) : null;
        $langue = isset($_POST['langue']) ? trim($_POST['langue']) : 'Français';
        $is_physical = isset($_POST['is_physical']) ? 1 : 0;
        $is_digital = isset($_POST['is_digital']) ? 1 : 0;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        
        if (empty($titre)) {
            sendJsonResponse(false, 'Le titre est requis');
        }
        
        // Gestion de la couverture
        $couverture_url = $ouvrage['couverture_url'];
        if (isset($_FILES['couverture']) && $_FILES['couverture']['error'] === UPLOAD_ERR_OK) {
            // Supprimer l'ancienne image
            if (!empty($ouvrage['couverture_url']) && file_exists('../' . $ouvrage['couverture_url'])) {
                @unlink('../' . $ouvrage['couverture_url']);
            }
            
            $uploadResult = uploadCouverture($_FILES['couverture']);
            if ($uploadResult['success']) {
                $couverture_url = $uploadResult['path'];
            }
        }
        
        // Gestion du PDF
        $fichier_pdf_url = $ouvrage['fichier_pdf_url'];
        if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === UPLOAD_ERR_OK) {
            // Supprimer l'ancien PDF
            if (!empty($ouvrage['fichier_pdf_url']) && file_exists('../' . $ouvrage['fichier_pdf_url'])) {
                @unlink('../' . $ouvrage['fichier_pdf_url']);
            }
            
            $uploadResult = uploadPDF($_FILES['fichier_pdf']);
            if ($uploadResult['success']) {
                $fichier_pdf_url = $uploadResult['path'];
            } else {
                sendJsonResponse(false, $uploadResult['error']);
            }
        }
        
        // Mise à jour
        $stmt = $pdo->prepare("
            UPDATE ouvrages SET
                titre = :titre,
                sous_titre = :sous_titre,
                categorie_id = :categorie_id,
                annee_publication = :annee_publication,
                resume = :resume,
                description = :description,
                isbn = :isbn,
                editeur = :editeur,
                nombre_pages = :nombre_pages,
                langue = :langue,
                couverture_url = :couverture_url,
                fichier_pdf_url = :fichier_pdf_url,
                is_physical = :is_physical,
                is_digital = :is_digital,
                stock = :stock,
                updated_at = NOW()
            WHERE id = :id AND professeur_id = :professeur_id
        ");
        
        $stmt->execute([
            'titre' => $titre,
            'sous_titre' => $sous_titre,
            'categorie_id' => $categorie_id,
            'annee_publication' => $annee_publication,
            'resume' => $resume,
            'description' => $description,
            'isbn' => $isbn,
            'editeur' => $editeur,
            'nombre_pages' => $nombre_pages,
            'langue' => $langue,
            'couverture_url' => $couverture_url,
            'fichier_pdf_url' => $fichier_pdf_url,
            'is_physical' => $is_physical,
            'is_digital' => $is_digital,
            'stock' => $stock,
            'id' => $ouvrage_id,
            'professeur_id' => $professeur_id
        ]);
        
        sendJsonResponse(true, 'Ouvrage modifié avec succès');
        
    } catch (PDOException $e) {
        error_log("Erreur modification ouvrage: " . $e->getMessage());
        sendJsonResponse(false, 'Erreur lors de la modification de l\'ouvrage');
    }
}

// ===========================================
// SUPPRIMER UN OUVRAGE
// ===========================================
else if ($action === 'delete') {
    try {
        $ouvrage_id = isset($_POST['ouvrage_id']) ? intval($_POST['ouvrage_id']) : 0;
        
        if (!$ouvrage_id) {
            sendJsonResponse(false, 'ID d\'ouvrage invalide');
        }
        
        // Vérifier que l'ouvrage appartient au professeur
        $checkStmt = $pdo->prepare("SELECT id, couverture_url, fichier_pdf_url FROM ouvrages WHERE id = :id AND professeur_id = :professeur_id");
        $checkStmt->execute(['id' => $ouvrage_id, 'professeur_id' => $professeur_id]);
        $ouvrage = $checkStmt->fetch();
        
        if (!$ouvrage) {
            sendJsonResponse(false, 'Ouvrage non trouvé ou non autorisé');
        }
        
        // Supprimer l'image de couverture
        if (!empty($ouvrage['couverture_url']) && file_exists('../' . $ouvrage['couverture_url'])) {
            @unlink('../' . $ouvrage['couverture_url']);
        }
        
        // Supprimer le fichier PDF
        if (!empty($ouvrage['fichier_pdf_url']) && file_exists('../' . $ouvrage['fichier_pdf_url'])) {
            @unlink('../' . $ouvrage['fichier_pdf_url']);
        }
        
        // Supprimer de la base de données
        $stmt = $pdo->prepare("DELETE FROM ouvrages WHERE id = :id AND professeur_id = :professeur_id");
        $stmt->execute(['id' => $ouvrage_id, 'professeur_id' => $professeur_id]);
        
        sendJsonResponse(true, 'Ouvrage supprimé avec succès');
        
    } catch (PDOException $e) {
        error_log("Erreur suppression ouvrage: " . $e->getMessage());
        sendJsonResponse(false, 'Erreur lors de la suppression de l\'ouvrage');
    }
}

else {
    sendJsonResponse(false, 'Action non reconnue');
}

// ===========================================
// FONCTIONS UTILITAIRES
// ===========================================

/**
 * Générer un slug à partir d'un titre
 */
function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    
    // Remplacer les caractères accentués
    $text = str_replace(
        ['à', 'á', 'â', 'ã', 'ä', 'å', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'è', 'é', 'ê', 'ë', 'ç', 'ì', 'í', 'î', 'ï', 'ù', 'ú', 'û', 'ü', 'ÿ', 'ñ'],
        ['a', 'a', 'a', 'a', 'a', 'a', 'o', 'o', 'o', 'o', 'o', 'o', 'e', 'e', 'e', 'e', 'c', 'i', 'i', 'i', 'i', 'u', 'u', 'u', 'u', 'y', 'n'],
        $text
    );
    
    // Remplacer les caractères spéciaux par des tirets
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Vérifier si un slug existe déjà
 */
function slugExists($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ouvrages WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Upload de la couverture
 */
function uploadCouverture($file) {
    $maxSize = 2 * 1024 * 1024; // 2 Mo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    
    // Vérifications
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 2Mo)'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Format non autorisé'];
    }
    
    // Créer le dossier s'il n'existe pas
    $uploadDir = '../assets/covers/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . time() . '_' . uniqid() . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'path' => 'assets/covers/' . $filename,
            'filename' => $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
}

/**
 * Upload du fichier PDF
 */
function uploadPDF($file) {
    $maxSize = 10 * 1024 * 1024; // 10 Mo
    $allowedTypes = ['application/pdf'];
    
    // Vérifications
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Fichier PDF trop volumineux (max 10Mo)'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Format non autorisé. Seul le PDF est accepté'];
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return ['success' => false, 'error' => 'Extension de fichier invalide'];
    }
    
    // Créer le dossier s'il n'existe pas
    $uploadDir = '../assets/pdfs/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom unique
    $filename = 'ouvrage_' . time() . '_' . uniqid() . '.pdf';
    $destination = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'path' => 'assets/pdfs/' . $filename,
            'filename' => $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Erreur lors de l\'upload du PDF'];
}
