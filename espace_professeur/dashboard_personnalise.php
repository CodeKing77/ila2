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
                             alt="Photo de profil" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <i class="fas fa-chalkboard-teacher"></i>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($prenom . ' ' . substr($nom, 0, 15)); ?></h3>
                    <span><?php echo htmlspecialchars(substr($professeur['titre_academique'] ?? 'Professeur', 0, 30)); ?></span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="dashboard_personnalise.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="openModal('modalOuvrage'); return false;">
                            <i class="fas fa-plus-circle"></i>
                            <span>Ajouter un ouvrage</span>
                        </a>
                    </li>
                    <li>
                        <a href="#mes-ouvrages" onclick="scrollToSection('mes-ouvrages'); return false;">
                            <i class="fas fa-book"></i>
                            <span>Mes ouvrages</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="openModal('modalProfil'); return false;">
                            <i class="fas fa-user-cog"></i>
                            <span>Mon profil</span>
                        </a>
                    </li>
                    <li class="nav-divider"></li>
                    <li>
                        <a href="../index.html">
                            <i class="fas fa-home"></i>
                            <span>Retour au site</span>
                        </a>
                    </li>
                    <li>
                        <button class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </button>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <p>© 2024 ILA Publications</p>
                <p>Version 1.0.0</p>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="professor-main">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Tableau de bord</h1>
                    <p class="welcome-message" id="welcome-message">
                        <?php
                        $hour = date('H');
                        $greeting = ($hour < 12) ? 'Bonjour' : (($hour < 18) ? 'Bon après-midi' : 'Bonsoir');
                        echo $greeting . ', ' . htmlspecialchars($prenom);
                        ?>
                    </p>
                </div>
                <div class="header-right">
                    <div class="date-display">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo strftime('%A %d %B %Y'); ?></span>
                    </div>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card card-primary">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Ouvrages publiés</h3>
                        <div class="stat-number"><?php echo $professeur['nombre_ouvrages']; ?></div>
                    </div>
                </div>
                
                <div class="stat-card card-secondary">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total de vues</h3>
                        <div class="stat-number"><?php echo number_format($professeur['total_vues'], 0, ',', ' '); ?></div>
                    </div>
                </div>
                
                <div class="stat-card card-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Compte actif</h3>
                        <div class="stat-number">
                            <?php echo $professeur['is_active'] ? '<i class="fas fa-check" style="color: #28a745;"></i>' : '<i class="fas fa-times" style="color: #dc3545;"></i>'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card card-warning">
                    <div class="stat-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Profil</h3>
                        <div class="stat-number" style="font-size: 14px;">
                            <a href="#" onclick="openModal('modalProfil'); return false;" 
                               style="color: #333; text-decoration: none;">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mes ouvrages section -->
            <div class="dashboard-grid" id="mes-ouvrages">
                <div class="column-left" style="grid-column: 1 / -1;">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2><i class="fas fa-book"></i> Mes ouvrages récents</h2>
                            <button class="btn-primary" onclick="openModal('modalOuvrage')">
                                <i class="fas fa-plus"></i> Ajouter un ouvrage
                            </button>
                        </div>
                        <div class="section-content">
                            <?php if (empty($derniers_ouvrages)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-book" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                                    <p>Vous n'avez pas encore ajouté d'ouvrage</p>
                                    <button class="btn-primary" onclick="openModal('modalOuvrage')">
                                        <i class="fas fa-plus"></i> Ajouter votre premier ouvrage
                                    </button>
                                </div>
                            <?php else: ?>
                                <div id="recent-books">
                                    <?php foreach ($derniers_ouvrages as $ouvrage): ?>
                                        <div class="recent-book-item">
                                            <div class="book-info">
                                                <h4><?php echo htmlspecialchars($ouvrage['titre']); ?></h4>
                                                <p class="book-category"><?php echo htmlspecialchars($ouvrage['categorie_nom'] ?? 'Non catégorisé'); ?></p>
                                                <div class="book-meta">
                                                    <span><i class="far fa-calendar"></i> <?php echo $ouvrage['date_ajout']; ?></span>
                                                    <span><i class="far fa-eye"></i> <?php echo $ouvrage['views_count']; ?> vues</span>
                                                </div>
                                            </div>
                                            <div class="action-buttons">
                                                <button class="btn-edit" onclick="editOuvrage(<?php echo $ouvrage['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn-delete" onclick="deleteOuvrage(<?php echo $ouvrage['id']; ?>, '<?php echo htmlspecialchars(addslashes($ouvrage['titre'])); ?>')">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="dashboard-footer">
                <p>© <?php echo date('Y'); ?> Institut de Linguistique Appliquée d'Abidjan. Tous droits réservés.</p>
            </footer>
        </main>
    </div>
    
    <!-- Modal Ajouter/Modifier Ouvrage -->
    <div id="modalOuvrage" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalOuvrageTitle">
                    <i class="fas fa-book"></i> Ajouter un ouvrage
                </h2>
                <button class="modal-close" onclick="closeModal('modalOuvrage')">&times;</button>
            </div>
            <div id="alertOuvrage" class="alert"></div>
            <form id="formOuvrage" enctype="multipart/form-data">
                <input type="hidden" id="ouvrage_id" name="ouvrage_id">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="titre">Titre de l'ouvrage *</label>
                    <input type="text" id="titre" name="titre" required>
                </div>
                
                <div class="form-group">
                    <label for="sous_titre">Sous-titre</label>
                    <input type="text" id="sous_titre" name="sous_titre">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="categorie_id">Catégorie *</label>
                        <select id="categorie_id" name="categorie_id" required>
                            <option value="">Sélectionnez une catégorie</option>
                            <?php
                            $stmtCat = $pdo->query("SELECT id, nom FROM categories_ouvrages WHERE is_active = 1 ORDER BY nom");
                            while ($cat = $stmtCat->fetch()) {
                                echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['nom']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="annee_publication">Année de publication</label>
                        <input type="number" id="annee_publication" name="annee_publication" 
                               min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="resume">Résumé</label>
                    <textarea id="resume" name="resume" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description">Description complète</label>
                    <textarea id="description" name="description" rows="5"></textarea>
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
                        <input type="text" id="langue" name="langue" value="Français">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="couverture">Image de couverture</label>
                    <input type="file" id="couverture" name="couverture" accept="image/*">
                    <small style="color: #666;">Formats acceptés: JPG, PNG, WEBP (max 2Mo)</small>
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
    
    <!-- Modal Modifier Profil -->
    <div id="modalProfil" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Modifier mon profil</h2>
                <button class="modal-close" onclick="closeModal('modalProfil')">&times;</button>
            </div>
            <div id="alertProfil" class="alert"></div>
            <form id="formProfil" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom_complet">Nom complet *</label>
                    <input type="text" id="nom_complet" name="nom_complet" 
                           value="<?php echo htmlspecialchars($professeur['nom_complet']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email_academique">Email académique *</label>
                    <input type="email" id="email_academique" name="email_academique" 
                           value="<?php echo htmlspecialchars($professeur['email_academique']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="titre_academique">Titre académique</label>
                    <input type="text" id="titre_academique" name="titre_academique" 
                           value="<?php echo htmlspecialchars($professeur['titre_academique'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="specialites">Spécialités</label>
                    <input type="text" id="specialites" name="specialites" 
                           value="<?php echo htmlspecialchars($professeur['specialites'] ?? ''); ?>">
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
                showAlert('alertProfil', 'danger', 'Erreur de connexion au serveur');
            }
        });
        
        // Modifier un ouvrage
        async function editOuvrage(id) {
            try {
                const response = await fetch(`../api/get_ouvrage.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const ouvrage = data.ouvrage;
                    
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
                    
                    document.getElementById('modalOuvrageTitle').innerHTML = 
                        '<i class="fas fa-edit"></i> Modifier l\'ouvrage';
                    
                    openModal('modalOuvrage');
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                alert('Erreur de chargement de l\'ouvrage');
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
