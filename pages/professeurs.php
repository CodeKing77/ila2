<?php
// professeurs.php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'ila_publications_db';
$username = 'root'; // À adapter selon votre configuration
$password = ''; // À adapter selon votre configuration

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer tous les professeurs actifs
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM diplomes d WHERE d.professeur_id = p.id) as nb_diplomes,
           (SELECT COUNT(*) FROM experiences e WHERE e.professeur_id = p.id) as nb_experiences
    FROM professeurs p 
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si un ID spécifique est passé en paramètre, afficher ce professeur en détail
$professeur_detail = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM professeurs p 
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$_GET['id']]);
    $professeur_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($professeur_detail) {
        // Récupérer les diplômes
        $stmt = $pdo->prepare("SELECT * FROM diplomes WHERE professeur_id = ? ORDER BY ordre ASC, annee DESC");
        $stmt->execute([$_GET['id']]);
        $diplomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les expériences
        $stmt = $pdo->prepare("SELECT * FROM experiences WHERE professeur_id = ? ORDER BY ordre ASC, date_debut DESC");
        $stmt->execute([$_GET['id']]);
        $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Professeurs - ILA</title>
    <link rel="stylesheet" href="../styles/pages/professeurs_php.css">
    <link rel="stylesheet" href="../styles/variables.css">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="../styles/pages/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/responsive.css">
    <link rel="icon" href="../assets/images/logos/favicon.ico">

    <!-- Dans le <head> de professeurs.php -->
<!--<style>
/* Variables CSS pour le menu de connexion */
:root {
    --primary-color: #1a3c40;
    --secondary-color: #2d5d63;
    --accent-color: #38a3a5;
    --text-dark: #333333;
    --text-light: #666666;
    --bg-light: #f8f9fa;
    --border-color: #e9ecef;
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    --radius: 8px;
}

/* Animation d'entrée pour les cartes */
.professor-card {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
    transform: translateY(20px);
}

.professor-card:nth-child(1) { animation-delay: 0.1s; }
.professor-card:nth-child(2) { animation-delay: 0.2s; }
.professor-card:nth-child(3) { animation-delay: 0.3s; }
.professor-card:nth-child(4) { animation-delay: 0.4s; }
.professor-card:nth-child(5) { animation-delay: 0.5s; }
.professor-card:nth-child(6) { animation-delay: 0.6s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Effet de focus pour les cartes */
.professor-card:focus-within {
    outline: 3px solid var(--accent-color);
    outline-offset: 3px;
}

/* Style pour le chargement */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    100% {
        left: 100%;
    }
}

/* Amélioration de l'accessibilité */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ============================================
   STYLES SPÉCIFIQUES POUR LE MENU DE CONNEXION
   ============================================ */

.nav-auth-dropdown {
    position: relative;
}

.auth-container {
    position: relative;
}

.auth-btn {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 50px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(26, 60, 64, 0.2);
    min-width: 130px;
    justify-content: center;
}

.auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(26, 60, 64, 0.3);
}

.auth-btn:active {
    transform: translateY(0);
}

.auth-btn i {
    font-size: 16px;
}

.auth-btn i.fa-chevron-down {
    font-size: 12px;
    transition: transform 0.3s ease;
}

.auth-dropdown-menu.show + .auth-btn i.fa-chevron-down {
    transform: rotate(180deg);
}

.auth-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 350px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    z-index: 1001;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.auth-dropdown-menu.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.auth-tabs {
    display: flex;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 5px;
    margin: 20px 20px 0;
    border-radius: 8px;
}

.auth-tab {
    flex: 1;
    padding: 12px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
    color: var(--text-light);
    transition: all 0.3s ease;
    border-radius: 6px;
    font-size: 14px;
}

.auth-tab:hover {
    color: var(--primary-color);
    background: rgba(255, 255, 255, 0.7);
}

.auth-tab.active {
    color: white;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    box-shadow: 0 4px 12px rgba(26, 60, 64, 0.2);
}

.auth-tab i {
    font-size: 16px;
}

.auth-forms {
    padding: 20px;
}

.auth-form {
    display: none;
}

.auth-form.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(10px);
    }
    to { 
        opacity: 1; 
        transform: translateY(0);
    }
}

.auth-form h4 {
    margin-bottom: 25px;
    color: var(--primary-color);
    text-align: center;
    font-size: 1.4rem;
    font-weight: 700;
}

.form-group {
    margin-bottom: 20px;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus {
    outline: none;
    border-color: var(--accent-color);
    background: white;
    box-shadow: 0 0 0 3px rgba(56, 163, 165, 0.1);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    font-size: 14px;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: var(--text-light);
}

.checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--accent-color);
}

.forgot-password {
    color: var(--accent-color);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.forgot-password:hover {
    color: var(--primary-color);
    text-decoration: underline;
}

.btn-auth-submit {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(26, 60, 64, 0.2);
}

.btn-auth-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(26, 60, 64, 0.3);
}

.auth-divider {
    text-align: center;
    margin: 25px 0;
    color: var(--text-light);
    font-size: 14px;
    position: relative;
}

.auth-divider:before,
.auth-divider:after {
    content: '';
    position: absolute;
    top: 50%;
    width: 45%;
    height: 1px;
    background: var(--border-color);
}

.auth-divider:before {
    left: 0;
}

.auth-divider:after {
    right: 0;
}

.btn-auth-register {
    display: block;
    width: 100%;
    padding: 16px;
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.btn-auth-register:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26, 60, 64, 0.2);
}

/* Indicateur de flèche */
.auth-dropdown-menu:before {
    content: '';
    position: absolute;
    top: -8px;
    right: 30px;
    width: 16px;
    height: 16px;
    background: white;
    transform: rotate(45deg);
    border-top: 1px solid var(--border-color);
    border-left: 1px solid var(--border-color);
    z-index: -1;
}

/* Assurer que le dropdown reste au-dessus des autres éléments */
.nav-menu {
    position: relative;
    z-index: 1000;
}

/* Style responsive pour le menu de connexion */
@media (max-width: 768px) {
    .auth-dropdown-menu {
        position: fixed;
        top: auto;
        right: 20px;
        left: 20px;
        width: auto;
        margin-top: 10px;
    }
    
    .auth-dropdown-menu:before {
        right: 30px;
    }
}
</style>-->
</head>

<body>
    <!-- Navigation (identique à votre version HTML) -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.html" class="logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo">
            </a>
            
            <ul class="nav-menu">
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="about.html">À propos</a></li>
                <li><a href="missions.html">Missions</a></li>
                <li><a href="recherche.html">Recherche</a></li>
                <li><a href="professeurs.php" class="active">Professeurs</a></li>
                <li><a href="ouvrages.html">Ouvrages</a></li>
                <li><a href="contact.html">Contact</a></li>
                
                <!-- Menu connexion (ajusté pour PHP) -->
                <li class="nav-auth-dropdown" id="authDropdown">
                    <div class="auth-container not-logged-in">
                        <button class="auth-btn" id="authToggle">
                            <i class="fas fa-user"></i>
                            <span>Connexion</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="auth-dropdown-menu">
                            <div class="auth-tabs">
                                <button class="auth-tab" data-tab="professeur">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    Professeur
                                </button>
                            </div>
                            
                            <div class="auth-forms">
                                <form class="auth-form" id="professeurForm" data-type="professeur">
                                <h4>Connexion Professeur</h4>
                                
                                <div class="form-group">
                                    <input type="email" placeholder="Email universitaire" required name="email" autocomplete="email">
                                </div>
                                
                                <div class="form-group">
                                    <input type="password" placeholder="Mot de passe" required name="password" autocomplete="current-password">
                                </div>
                                
                                <div class="form-options">
                                    <label class="checkbox">
                                        <input type="checkbox" name="remember"> Se souvenir
                                    </label>
                                    <a href="pages/motdepasse_oublie.php" class="forgot-password">Mot de passe oublié ?</a>
                                </div>
                                
                                <button type="submit" class="btn-auth-submit">
                                    <i class="fas fa-sign-in-alt"></i> Accéder à l'espace
                                </button>
                                
                                <div class="auth-divider">ou</div>
                                <a href="pages/inscription_professeur.php" class="btn-auth-register">
                                    <i class="fas fa-user-plus"></i> S'inscrire comme professeur
                                </a>
                            </form>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            
            <button class="menu-toggle" type="button" aria-label="Basculer le menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <main class="container">
        <?php if ($professeur_detail): ?>
            <!-- Page de détail d'un professeur -->
            <section class="page-header">
                <h1>Profil du Professeur</h1>
                <p>Découvrez le parcours académique et les domaines d'expertise</p>
                <a href="professeurs.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </section>

            <section class="professor-profile">
                <div class="profile-header">
                    <div class="profile-photo">
                        <?php if ($professeur_detail['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars($professeur_detail['photo_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($professeur_detail['nom_complet']); ?>"
                                 width="250" height="250">
                        <?php else: ?>
                            <div class="photo-placeholder">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($professeur_detail['nom_complet']); ?></h2>
                        <p class="title"><?php echo htmlspecialchars($professeur_detail['titre_academique']); ?></p>
                        
                        <?php if ($professeur_detail['specialites']): ?>
                        <div class="specialites">
                            <h3><i class="fas fa-search"></i> Domaines d'expertise</h3>
                            <p><?php echo nl2br(htmlspecialchars($professeur_detail['specialites'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($professeur_detail['universites']): ?>
                        <div class="universities">
                            <h3><i class="fas fa-university"></i> Université(s) d'affiliation</h3>
                            <div class="university-badges">
                                <?php 
                                $universites = explode(',', $professeur_detail['universites']);
                                foreach ($universites as $universite): 
                                    $universite = trim($universite);
                                    if (!empty($universite)):
                                ?>
                                <span class="university-badge"><?php echo htmlspecialchars($universite); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($professeur_detail['bio']): ?>
                        <div class="bio-preview">
                            <h3><i class="fas fa-file-alt"></i> Biographie</h3>
                            <p><?php echo nl2br(htmlspecialchars(substr($professeur_detail['bio'], 0,5000))); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contenu détaillé du profil - Affichage des Diplômes Universitaires -->
                <div class="profile-content">
                    <?php if (!empty($diplomes)): ?>
                    <section class="academic-section">
                        <h3><i class="fas fa-graduation-cap"></i> Diplômes universitaires</h3>
                        <div class="timeline">
                            <?php foreach ($diplomes as $diplome): ?>
                            <div class="timeline-item">
                                <div class="timeline-year"><?php echo htmlspecialchars($diplome['annee']); ?></div>
                                <div class="timeline-content">
                                    <h4><?php echo htmlspecialchars($diplome['diplome']); ?></h4>
                                    <p><?php echo htmlspecialchars($diplome['institution']); ?>
                                        <?php if ($diplome['pays']): ?>
                                        , <?php echo htmlspecialchars($diplome['pays']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($diplome['mention']): ?>
                                    <p class="honor"><?php echo htmlspecialchars($diplome['mention']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    
                    <!-- Affichage des Expériences Professionnelles -->
                    <?php if (!empty($experiences)): ?>
                    <section class="academic-section">
                        <h3><i class="fas fa-briefcase"></i> Expérience professionnelle</h3>
                        <div class="experience-list">
                            <?php foreach ($experiences as $experience): ?>
                            <div class="experience-item">
                                <h4><?php echo htmlspecialchars($experience['poste']); ?></h4>
                                <p class="experience-institution"><?php echo htmlspecialchars($experience['institution']); ?></p>
                                
                                <div class="experience-period">
                                    <?php if ($experience['date_debut']): ?>
                                        De <?php echo date('F Y', strtotime($experience['date_debut'])); ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($experience['est_actuel']): ?>
                                        - Actuellement
                                    <?php elseif ($experience['date_fin']): ?>
                                        - <?php echo date('F Y', strtotime($experience['date_fin'])); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($experience['description']): ?>
                                <p class="experience-description"><?php echo nl2br(htmlspecialchars($experience['description'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    
                    <?php if ($professeur_detail['bio']): ?>
                    <section class="academic-section">
                        <h3><i class="fas fa-file-alt"></i> Biographie complète</h3>
                        <div class="bio-content">
                            <p><?php echo nl2br(htmlspecialchars($professeur_detail['bio'])); ?></p>
                        </div>
                    </section>
                    <?php endif; ?>


                    <?php if ($professeur_detail['site_web'] || $professeur_detail['linkedin_url'] || $professeur_detail['google_scholar_url']): ?>
                    <section class="academic-section">
                        <h3><i class="fas fa-network-wired"></i> Liens et contacts</h3>
                        <div class="contact-links">
                            <?php if ($professeur_detail['site_web']): ?>
                            <a href="<?php echo htmlspecialchars($professeur_detail['site_web']); ?>" target="_blank" class="contact-link">
                                <i class="fas fa-globe"></i> Site web
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($professeur_detail['linkedin_url']): ?>
                            <a href="<?php echo htmlspecialchars($professeur_detail['linkedin_url']); ?>" target="_blank" class="contact-link">
                                <i class="fab fa-linkedin"></i> LinkedIn
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($professeur_detail['google_scholar_url']): ?>
                            <a href="<?php echo htmlspecialchars($professeur_detail['google_scholar_url']); ?>" target="_blank" class="contact-link">
                                <i class="fas fa-graduation-cap"></i> Google Scholar
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($professeur_detail['telephone']): ?>
                            <div class="contact-info">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($professeur_detail['telephone']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($professeur_detail['email_academique']): ?>
                            <div class="contact-info">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($professeur_detail['email_academique']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
            </section>

        <?php else: ?>
            <!-- Liste de tous les professeurs -->
            <section class="page-header">
                <h1>Nos Professeurs</h1>
                <p>Découvrez notre équipe de professeurs et chercheurs experts en linguistique, éducation et développement durable</p>
                
                <?php if (isset($_SESSION['new_professor_id'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Votre inscription a été enregistrée avec succès ! Votre profil sera visible après validation.
                    <?php unset($_SESSION['new_professor_id']); ?>
                </div>
                <?php endif; ?>
            </section>

            <section class="professors-grid">
                <div class="grid-header">
                    <h2><i class="fas fa-chalkboard-teacher"></i> Notre Équipe Académique</h2>
                    <p class="professor-count"><?php echo count($professeurs); ?> professeur<?php echo count($professeurs) > 1 ? 's' : ''; ?> actif<?php echo count($professeurs) > 1 ? 's' : ''; ?></p>
                </div>

                <?php if (empty($professeurs)): ?>
                <div class="no-professors">
                    <i class="fas fa-user-graduate" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Aucun professeur enregistré pour le moment</h3>
                    <p>Les professeurs inscrits apparaîtront ici après validation de leur compte.</p>
                    <a href="inscription_professeur.php" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Devenir le premier professeur
                    </a>
                </div>
                <?php else: ?>
                <div class="professor-cards">
                    <?php foreach ($professeurs as $professeur): ?>
                    <div class="professor-card">
                        <div class="card-header">
                            <div class="card-photo">
                                <?php if ($professeur['photo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($professeur['photo_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($professeur['nom_complet']); ?>">
                                        
                                <?php else: ?>
                                    <div class="photo-placeholder">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-stats">
                                <span class="stat-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo $professeur['nb_diplomes']; ?> diplôme<?php echo $professeur['nb_diplomes'] > 1 ? 's' : ''; ?></span>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo $professeur['nb_experiences']; ?> expérience<?php echo $professeur['nb_experiences'] > 1 ? 's' : ''; ?></span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h4><?php echo htmlspecialchars($professeur['nom_complet']); ?></h4>
                            <p class="card-title"><?php echo htmlspecialchars($professeur['titre_academique']); ?></p>
                            
                            <?php if ($professeur['universites']): ?>
                            <p class="card-university">
                                <i class="fas fa-university"></i>
                                <?php 
                                $universites = explode(',', $professeur['universites']);
                                echo htmlspecialchars(trim($universites[0]));
                                if (count($universites) > 1) echo ' et autres...';
                                ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($professeur['specialites']): ?>
                            <div class="card-specialites">
                                <i class="fas fa-search"></i>
                                <span class="specialites-preview">
                                    <?php 
                                    $specialites = explode(',', $professeur['specialites']);
                                    $first_specialite = trim($specialites[0]);
                                    echo htmlspecialchars($first_specialite);
                                    if (count($specialites) > 1) echo '...';
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($professeur['bio']): ?>
                            <p class="card-bio-preview">
                                <?php echo nl2br(htmlspecialchars(substr($professeur['bio'], 0, 150))); ?>...
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer">
                            <a href="professeurs.php?id=<?php echo $professeur['id']; ?>" class="btn-outline">
                                <i class="fas fa-eye"></i> Voir le profil
                            </a>
                            
                            <?php if ($professeur['site_web'] || $professeur['linkedin_url']): ?>
                            <div class="card-social">
                                <?php if ($professeur['site_web']): ?>
                                <a href="<?php echo htmlspecialchars($professeur['site_web']); ?>" target="_blank" title="Site web">
                                    <i class="fas fa-globe"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($professeur['linkedin_url']): ?>
                                <a href="<?php echo htmlspecialchars($professeur['linkedin_url']); ?>" target="_blank" title="LinkedIn">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <div class="join-team">
                <h3><i class="fas fa-user-plus"></i> Rejoignez notre équipe académique</h3>
                <p>Partagez vos connaissances et diffusez vos ouvrages à travers notre plateforme.</p>
                <a href="inscription_professeur.php" class="btn-primary">
                    <i class="fas fa-chalkboard-teacher"></i> Devenir Professeur Partenaire
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" class="footer-logo">
                <p>Institut de Linguistique Appliquée d'Abidjan<br>
                Université Félix Houphouët‑Boigny</p>
            </div>
            <div class="footer-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="../index.html">Accueil</a></li>
                    <li><a href="about.html">À propos</a></li>
                    <li><a href="missions.html">Missions</a></li>
                    <li><a href="recherche.html">Recherche</a></li>
                    <li><a href="professeurs.php">Professeurs</a></li>
                    <li><a href="ouvrages.html">Ouvrages</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p><i class="fas fa-map-marker-alt"></i> Abidjan, Côte d'Ivoire</p>
                <p><i class="fas fa-envelope"></i> contact@ila.edu</p>
                <p><i class="fas fa-phone"></i> +225 01 40 54 40 00</p>
            </div>
            <div class="footer-section">
                <h3>Suivez-nous</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Institut de Linguistique Appliquée d'Abidjan. Tous droits réservés.</p>
        </div>
    </footer>

    
</body>

<script>
// Gestion de la navigation responsive et des dropdowns
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé - initialisation du menu de connexion');
    
    // Menu responsive
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
    
    // DROPDOWN D'AUTHENTIFICATION - CODE CORRIGÉ
    const authToggle = document.getElementById('authToggle');
    const authDropdown = document.querySelector('.auth-dropdown-menu');
    
    console.log('Auth Toggle:', authToggle);
    console.log('Auth Dropdown:', authDropdown);
    
    if (authToggle && authDropdown) {
        // Ajouter l'événement au bouton
        authToggle.addEventListener('click', function(e) {
            console.log('Bouton auth cliqué');
            e.stopPropagation();
            e.preventDefault();
            
            // Fermer le menu mobile s'il est ouvert
            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                if (menuToggle) menuToggle.classList.remove('active');
            }
            
            // Basculer le dropdown
            authDropdown.classList.toggle('show');
            
            // Changer l'icône de la flèche
            const chevron = this.querySelector('.fa-chevron-down');
            if (chevron) {
                if (authDropdown.classList.contains('show')) {
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Fermer le dropdown en cliquant ailleurs sur la page
        document.addEventListener('click', function(event) {
            if (authDropdown.classList.contains('show') && 
                !authDropdown.contains(event.target) && 
                !authToggle.contains(event.target)) {
                authDropdown.classList.remove('show');
                
                // Réinitialiser l'icône de la flèche
                const chevron = authToggle.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Empêcher la fermeture en cliquant dans le dropdown
        authDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Fermer le dropdown avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && authDropdown.classList.contains('show')) {
                authDropdown.classList.remove('show');
                const chevron = authToggle.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
    } else {
        console.error('Éléments authToggle ou authDropdown non trouvés');
    }
    
    // Onglets d'authentification
    const authTabs = document.querySelectorAll('.auth-tab');
    const authForms = document.querySelectorAll('.auth-form');
    
    console.log('Auth Tabs trouvés:', authTabs.length);
    console.log('Auth Forms trouvés:', authForms.length);
    
    if (authTabs.length > 0 && authForms.length > 0) {
        // Activer le premier onglet par défaut
        const firstTab = authTabs[0];
        const firstForm = authForms[0];
        
        if (firstTab && firstForm) {
            firstTab.classList.add('active');
            firstForm.classList.add('active');
        }
        
        // Ajouter les événements aux onglets
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabType = this.getAttribute('data-tab');
                console.log('Onglet cliqué:', tabType);
                
                // Mettre à jour les onglets actifs
                authTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Afficher le formulaire correspondant
                authForms.forEach(form => {
                    if (form.getAttribute('id') === tabType + 'Form') {
                        form.classList.add('active');
                    } else {
                        form.classList.remove('active');
                    }
                });
            });
        });
    }
    
    // Gestion de la soumission des formulaires (prévenir l'envoi par défaut pour le test)
    const authFormsList = document.querySelectorAll('.auth-form');
    authFormsList.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Formulaire soumis:', this.id);
            // Ici vous pouvez ajouter votre logique de soumission AJAX
            alert('Formulaire soumis - Cette fonctionnalité est en développement'); //Partie à remplacer par la logique réelle
        });
    });
    
    // Fermer le dropdown lors du scroll
    window.addEventListener('scroll', function() {
        if (authDropdown && authDropdown.classList.contains('show')) {
            authDropdown.classList.remove('show');
            const chevron = authToggle.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = 'rotate(0deg)';
            }
        }
    });
});

// Fonction pour déboguer - à retirer en production
function debugAuth() {
    console.log('=== DEBUG MENU AUTH ===');
    console.log('Auth Toggle:', document.getElementById('authToggle'));
    console.log('Auth Dropdown:', document.querySelector('.auth-dropdown-menu'));
    console.log('Auth Tabs:', document.querySelectorAll('.auth-tab').length);
    console.log('Auth Forms:', document.querySelectorAll('.auth-form').length);
    console.log('======================');
}

// Exécuter le debug au chargement
window.addEventListener('load', debugAuth);
</script>
<script src="../scripts/auth-manager.js"></script>
<!--<script src="../scripts/main.js"></script>-->
<!--<script src="../scripts/contact.js"></script>-->

</html>