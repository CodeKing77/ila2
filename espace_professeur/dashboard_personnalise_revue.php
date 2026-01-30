<?php
/**
 * Dashboard personnalisé pour professeurs
 * Fichier: espace_professeur/dashboard_personnalise.php
 */

require_once '../config/config.php';

// Démarrer la session
startSecureSession();

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn() || !validateSession()) {
    header('Location: ../index.html');
    exit;
}

// Récupérer les informations du professeur
$pdo = getDbConnection();
if (!$pdo) {
    die('Erreur de connexion à la base de données');
}

try {
    // Récupérer les infos complètes du professeur
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            COUNT(DISTINCT o.id) as nombre_ouvrages,
            COALESCE(SUM(o.views_count), 0) as total_vues
        FROM professeurs p
        LEFT JOIN ouvrages o ON p.id = o.professeur_id AND o.is_active = 1
        WHERE p.id = :id
        GROUP BY p.id
    ");
    
    $stmt->execute(['id' => $_SESSION['professeur_id']]);
    $professeur = $stmt->fetch();
    
    if (!$professeur || $professeur['is_active'] != 1) {
        session_destroy();
        header('Location: ../index.html');
        exit;
    }
    
    // Récupérer les derniers ouvrages du professeur
    $stmtOuvrages = $pdo->prepare("
        SELECT 
            o.*,
            c.nom as categorie_nom,
            DATE_FORMAT(o.created_at, '%d/%m/%Y') as date_ajout
        FROM ouvrages o
        LEFT JOIN categories_ouvrages c ON o.categorie_id = c.id
        WHERE o.professeur_id = :id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    
    $stmtOuvrages->execute(['id' => $_SESSION['professeur_id']]);
    $derniers_ouvrages = $stmtOuvrages->fetchAll();
    
    // Récupérer toutes les catégories
    $stmtCategories = $pdo->prepare("SELECT id, nom FROM categories_ouvrages ORDER BY nom");
    $stmtCategories->execute();
    $categories = $stmtCategories->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
    die('Erreur lors du chargement du dashboard');
}

// Séparer le nom complet
$nomParts = explode(' ', $professeur['nom_complet'], 2);
$prenom = $nomParts[0] ?? '';
$nom = $nomParts[1] ?? $professeur['nom_complet'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo htmlspecialchars($prenom); ?> | ILA</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="../styles/pages/dashbord.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #4a8c6d;
            color: white;
        }
        .btn-primary:hover { background: #3a7c5d; }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .alert.show { display: block; animation: slideIn 0.3s ease; }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-delete {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-edit {
            background: #007bff;
            color: white;
        }
        .btn-edit:hover { background: #0056b3; }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="professor-dashboard">
        <!-- Sidebar -->
        <aside class="professor-sidebar">
            <div class="sidebar-header">
                <a href="../index.html" class="logo">
                    <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo">
                </a>
                <h2>Espace Professeur</h2>
            </div>
            
            <div class="professor-profile">
                <div class="profile-avatar">
                    <?php if (!empty($professeur['photo_url']) && file_exists('../' . $professeur['photo_url'])): ?>
                        <img src="../<?php echo htmlspecialchars($professeur['photo_url']); ?>" 
                             alt="<?php echo htmlspecialchars($professeur['nom_complet']); ?>">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($prenom); ?></h3>
                    <p><?php echo htmlspecialchars($professeur['titre_academique'] ?? 'Professeur'); ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#overview" class="nav-item active" onclick="scrollToSection('overview')">
                    <i class="fas fa-tachometer-alt"></i>
                    Vue d'ensemble
                </a>
                <a href="#ouvrages" class="nav-item" onclick="scrollToSection('ouvrages')">
                    <i class="fas fa-book"></i>
                    Mes ouvrages
                </a>
                <a href="#" class="nav-item" onclick="openModal('modalOuvrage')">
                    <i class="fas fa-plus-circle"></i>
                    Ajouter un ouvrage
                </a>
                <a href="#" class="nav-item" onclick="openModal('modalProfil')">
                    <i class="fas fa-user-edit"></i>
                    Modifier mon profil
                </a>
                <a href="../api/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-header">
                <h1>Bienvenue, <?php echo htmlspecialchars($prenom); ?> !</h1>
                <p>Gérez vos publications et suivez vos statistiques</p>
            </header>
            
            <!-- Vue d'ensemble -->
            <section id="overview" class="dashboard-section">
                <h2>Vue d'ensemble</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $professeur['nombre_ouvrages']; ?></h3>
                            <p>Ouvrages publiés</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $professeur['total_vues']; ?></h3>
                            <p>Vues totales</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo date('Y', strtotime($professeur['created_at'])); ?></h3>
                            <p>Membre depuis</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Mes ouvrages -->
            <section id="ouvrages" class="dashboard-section">
                <div class="section-header">
                    <h2>Mes ouvrages récents</h2>
                    <button class="btn-primary" onclick="openModal('modalOuvrage')">
                        <i class="fas fa-plus"></i> Nouvel ouvrage
                    </button>
                </div>
                
                <?php if (empty($derniers_ouvrages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>Aucun ouvrage publié</h3>
                        <p>Commencez par ajouter votre premier ouvrage</p>
                        <button class="btn-primary" onclick="openModal('modalOuvrage')">
                            <i class="fas fa-plus"></i> Ajouter un ouvrage
                        </button>
                    </div>
                <?php else: ?>
                    <div class="ouvrages-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Catégorie</th>
                                    <th>Date d'ajout</th>
                                    <th>Vues</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_ouvrages as $ouvrage): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ouvrage['titre']); ?></strong>
                                            <?php if (!empty($ouvrage['sous_titre'])): ?>
                                                <br><small><?php echo htmlspecialchars($ouvrage['sous_titre']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($ouvrage['categorie_nom'] ?? 'Non catégorisé'); ?></td>
                                        <td><?php echo htmlspecialchars($ouvrage['date_ajout']); ?></td>
                                        <td><?php echo htmlspecialchars($ouvrage['views_count']); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn-edit" 
                                                    onclick="editOuvrage(<?php echo $ouvrage['id']; ?>)">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <button class="btn-delete" 
                                                    onclick="deleteOuvrage(<?php echo $ouvrage['id']; ?>, '<?php echo htmlspecialchars($ouvrage['titre'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    
    <!-- Modal Ouvrage -->
    <div id="modalOuvrage" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalOuvrageTitle"><i class="fas fa-book"></i> Ajouter un ouvrage</h2>
                <button class="modal-close" onclick="closeModal('modalOuvrage')">×</button>
            </div>
            
            <div id="alertOuvrage" class="alert"></div>
            
            <form id="formOuvrage" enctype="multipart/form-data">
                <input type="hidden" id="ouvrage_id" name="ouvrage_id" value="">
                
                <div class="form-group">
                    <label for="titre">Titre *</label>
                    <input type="text" id="titre" name="titre" required>
                </div>
                
                <div class="form-group">
                    <label for="sous_titre">Sous-titre</label>
                    <input type="text" id="sous_titre" name="sous_titre">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="categorie_id">Catégorie</label>
                        <select id="categorie_id" name="categorie_id">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="annee_publication">Année de publication</label>
                        <input type="number" id="annee_publication" name="annee_publication" 
                               min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="resume">Résumé</label>
                    <textarea id="resume" name="resume" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn">
                    </div>
                    
                    <div class="form-group">
                        <label for="editeur">Éditeur</label>
                        <input type="text" id="editeur" name="editeur">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_pages">Nombre de pages</label>
                        <input type="number" id="nombre_pages" name="nombre_pages" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="langue">Langue</label>
                        <select id="langue" name="langue">
                            <option value="Français">Français</option>
                            <option value="Anglais">Anglais</option>
                            <option value="Espagnol">Espagnol</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Format disponible</label>
                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" id="is_physical" name="is_physical" value="1" checked>
                            <span>Physique</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" id="is_digital" name="is_digital" value="1">
                            <span>Numérique</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock (pour version physique)</label>
                    <input type="number" id="stock" name="stock" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label for="couverture">Image de couverture</label>
                    <input type="file" id="couverture" name="couverture" accept="image/*">
                    <small>Formats acceptés : JPG, PNG, WEBP (max 2 Mo)</small>
                </div>
                
                <div class="form-group">
                    <label for="fichier_pdf">Fichier PDF (optionnel)</label>
                    <input type="file" id="fichier_pdf" name="fichier_pdf" accept=".pdf">
                    <small>Format PDF uniquement (max 10 Mo)</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('modalOuvrage')">Annuler</button>
                    <button type="submit" class="btn-primary" id="btnSubmitOuvrage">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Profil -->
    <div id="modalProfil" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Modifier mon profil</h2>
                <button class="modal-close" onclick="closeModal('modalProfil')">×</button>
            </div>
            
            <div id="alertProfil" class="alert"></div>
            
            <form id="formProfil" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom_complet">Nom complet *</label>
                    <input type="text" id="nom_complet" name="nom_complet" 
                           value="<?php echo htmlspecialchars($professeur['nom_complet']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="titre_academique">Titre académique</label>
                        <input type="text" id="titre_academique" name="titre_academique" 
                               value="<?php echo htmlspecialchars($professeur['titre_academique'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="specialite">Spécialité</label>
                        <input type="text" id="specialite" name="specialite" 
                               value="<?php echo htmlspecialchars($professeur['specialite'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="universites">Universités</label>
                    <textarea id="universites" name="universites" rows="2"><?php echo htmlspecialchars($professeur['universites'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="bio">Biographie</label>
                    <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($professeur['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" 
                               value="<?php echo htmlspecialchars($professeur['telephone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_web">Site web</label>
                        <input type="url" id="site_web" name="site_web" 
                               value="<?php echo htmlspecialchars($professeur['site_web'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="photo">Photo de profil</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                    <small style="color: #666;">Image actuelle: 
                        <?php echo !empty($professeur['photo_url']) ? basename($professeur['photo_url']) : 'Aucune photo'; ?>
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('modalProfil')">Annuler</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../scripts/auth-manager.js"></script>
    <script>
        // Gestion des modals
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            if (modalId === 'modalOuvrage') {
                // Réinitialiser le formulaire pour l'ajout
                document.getElementById('formOuvrage').reset();
                document.getElementById('ouvrage_id').value = '';
                document.getElementById('modalOuvrageTitle').innerHTML = 
                    '<i class="fas fa-book"></i> Ajouter un ouvrage';
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
        }
        
        // Soumettre le formulaire d'ouvrage
        document.getElementById('formOuvrage').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const ouvrageId = document.getElementById('ouvrage_id').value;
            formData.set('action', ouvrageId ? 'edit' : 'add');
            
            showLoadingButton('btnSubmitOuvrage');
            
            try {
                const response = await fetch('../api/manage_ouvrages.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('alertOuvrage', 'success', data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('alertOuvrage', 'danger', data.message);
                    resetButton('btnSubmitOuvrage');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('alertOuvrage', 'danger', 'Erreur de connexion au serveur');
                resetButton('btnSubmitOuvrage');
            }
        });
        
        // Soumettre le formulaire de profil
        document.getElementById('formProfil').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('../api/update_profil.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('alertProfil', 'success', data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('alertProfil', 'danger', data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('alertProfil', 'danger', 'Erreur de connexion au serveur');
            }
        });
        
        // Modifier un ouvrage
        async function editOuvrage(id) {
            try {
                const response = await fetch(`../api/get_ouvrages_revue.php?id=${id}`);
                const data = await response.json();
                
                console.log('Données reçues pour édition:', data);
                
                if (data.success) {
                    // CORRECTION: Accès correct aux données de l'ouvrage (data.data.ouvrage)
                    const ouvrage = data.data.ouvrage;
                    
                    document.getElementById('ouvrage_id').value = ouvrage.id;
                    document.getElementById('titre').value = ouvrage.titre || '';
                    document.getElementById('sous_titre').value = ouvrage.sous_titre || '';
                    document.getElementById('categorie_id').value = ouvrage.categorie_id || '';
                    document.getElementById('annee_publication').value = ouvrage.annee_publication || '';
                    document.getElementById('resume').value = ouvrage.resume || '';
                    document.getElementById('description').value = ouvrage.description || '';
                    document.getElementById('isbn').value = ouvrage.isbn || '';
                    document.getElementById('editeur').value = ouvrage.editeur || '';
                    document.getElementById('nombre_pages').value = ouvrage.nombre_pages || '';
                    document.getElementById('langue').value = ouvrage.langue || 'Français';
                    document.getElementById('is_physical').checked = ouvrage.is_physical == 1;
                    document.getElementById('is_digital').checked = ouvrage.is_digital == 1;
                    document.getElementById('stock').value = ouvrage.stock || 0;
                    
                    document.getElementById('modalOuvrageTitle').innerHTML = 
                        '<i class="fas fa-edit"></i> Modifier l\'ouvrage';
                    
                    openModal('modalOuvrage');
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de charger l\'ouvrage'));
                }
            } catch (error) {
                console.error('Erreur complète:', error);
                alert('Erreur de chargement de l\'ouvrage: ' + error.message);
            }
        }
        
        // Supprimer un ouvrage
        async function deleteOuvrage(id, titre) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer l'ouvrage "${titre}" ?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('ouvrage_id', id);
                
                const response = await fetch('../api/manage_ouvrages.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression');
            }
        }
        
        // Fonctions utilitaires
        function showAlert(alertId, type, message) {
            const alert = document.getElementById(alertId);
            alert.className = `alert alert-${type} show`;
            alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;
        }
        
        function showLoadingButton(btnId) {
            const btn = document.getElementById(btnId);
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        }
        
        function resetButton(btnId) {
            const btn = document.getElementById(btnId);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
        }
        
        // Fermer les modals en cliquant à l'extérieur
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
