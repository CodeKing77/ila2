// ============================================
// VALIDATION ET GESTION DES INSCRIPTIONS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation globale
    initializeForms();
});

// Fonction d'initialisation qui détecte le type de formulaire
function initializeForms() {
    // Vérifier si nous sommes en mode développement
    const isDevelopment = window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1' || 
                         window.location.protocol === 'file:';
    
    // Injecter les styles CSS pour le mode développement
    injectDevelopmentStyles();
    
    // Détecter le type de formulaire
    const clientForm = document.getElementById('clientRegistrationForm');
    const professorForm = document.getElementById('professorRegistrationForm');
    
    if (clientForm) {
        initializeClientForm(clientForm, isDevelopment);
    }
    
    if (professorForm) {
        initializeProfessorForm(professorForm, isDevelopment);
    }
    
    // Initialiser les fonctionnalités communes
    initializeCommonFeatures();
}

// Fonction pour injecter les styles de développement
function injectDevelopmentStyles() {
    const devStyles = `
        /* Style pour le mode développement */
        .dev-mode-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.9rem;
            text-align: left;
        }
        
        .dev-mode-notice strong {
            display: block;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        
        .btn-dev-bypass {
            background: linear-gradient(135deg, #ff5722, #ff9800);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin: 15px auto;
            transition: all 0.3s ease;
            display: block;
            font-size: 1rem;
        }
        
        .btn-dev-bypass:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 34, 0.3);
        }
        
        .dev-bypass-section {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #ff9800;
        }
        
        .dev-email-preview {
            background: white;
            border: 1px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 0.9rem;
            text-align: left;
        }
        
        .dev-console-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1565c0;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            margin: 10px 0;
        }
        
        /* Styles pour les interfaces de validation */
        .validation-content {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 20px;
        }
        
        .validation-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 30px;
        }
        
        .validation-content h3 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .email-confirmation {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }
        
        .email-display {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 1.1rem;
        }
        
        .email-display i {
            color: var(--primary-color);
        }
        
        .instruction {
            color: var(--text-light);
            margin-top: 15px;
            font-size: 0.95rem;
        }
        
        .email-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: left;
        }
        
        .action-item i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-top: 5px;
        }
        
        .action-item h4 {
            margin: 0 0 8px;
            color: var(--text-dark);
            font-size: 1.1rem;
        }
        
        .action-item p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .btn-link {
            background: none;
            border: none;
            color: var(--primary-color);
            text-decoration: underline;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
            font-weight: 600;
        }
        
        .btn-link:hover {
            color: var(--secondary-color);
        }
        
        .timer-display {
            background: #fff8e1;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
            border: 1px solid #ffecb3;
        }
        
        .timer-display p {
            margin: 0;
            color: var(--text-dark);
        }
        
        #countdown {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .back-to-home {
            margin-top: 30px;
        }
        
        .step.completed .step-number {
            background: #2ecc71;
            color: white;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateX(150%);
            transition: transform 0.3s ease;
            z-index: 9999;
            max-width: 400px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-success {
            border-left: 4px solid #2ecc71;
        }
        
        .notification-warning {
            border-left: 4px solid #f39c12;
        }
        
        .notification-info {
            border-left: 4px solid #3498db;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 5px;
            margin-left: 10px;
        }
        
        .notification-close:hover {
            color: var(--text-dark);
        }
        
        /* Styles spécifiques pour professeur */
        .professor-validation {
            background: #f0f8ff;
            border-radius: 10px;
            padding: 30px;
            margin: 30px 0;
        }
        
        .professor-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .professor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .professor-details h4 {
            margin: 0 0 5px;
            color: var(--text-dark);
        }
        
        .professor-details p {
            margin: 0;
            color: var(--text-light);
        }
        
        .verification-status {
            background: #fff8e1;
            border-left: 4px solid #f39c12;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .verification-steps {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .verification-steps li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .verification-steps li.completed {
            border-color: #2ecc71;
            background: #f8fff8;
        }
        
        .verification-steps li i {
            color: #f39c12;
        }
        
        .verification-steps li.completed i {
            color: #2ecc71;
        }
        
        @media (max-width: 768px) {
            .validation-content {
                padding: 20px 10px;
            }
            
            .email-actions {
                grid-template-columns: 1fr;
            }
            
            .notification {
                left: 20px;
                right: 20px;
                max-width: none;
            }
            
            .professor-info {
                flex-direction: column;
                text-align: center;
            }
        }
    `;
    
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = devStyles;
    document.head.appendChild(styleSheet);
}



// ============================================
// INSCRIPTION PROFESSEUR
// ============================================

function initializeProfessorForm(form, isDevelopment) {
    const confirmationModal = document.getElementById('confirmationModal');
    const profEmail = document.getElementById('profEmail');
    const steps = document.querySelectorAll('.step');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateProfessorForm()) {
            const email = document.getElementById('email_academique').value;
            const nomComplet = document.getElementById('nom_complet').value;
            
            if (profEmail) {
                profEmail.textContent = email;
            }
            
            if (confirmationModal) {
                confirmationModal.style.display = 'flex';
                
                if (isDevelopment) {
                    addDevNoticeToModal(confirmationModal, email, 'professor', nomComplet);
                }
                
                setTimeout(function() {
                    goToVerificationStep(email, nomComplet);
                }, 4000);
            }
        }
    });
}

// ============================================
// FONCTIONS COMMUNES
// ============================================

function initializeCommonFeatures() {
    // Gestion de l'affichage/masquage du mot de passe
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Masquer le mot de passe');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Afficher le mot de passe');
            }
        });
    });
    
    // Validation en temps réel du mot de passe
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('input', validatePasswordStrength);
    }
    
    const confirmPasswordField = document.getElementById('confirm_password');
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', validatePasswordMatch);
    }
}

// ============================================
// VALIDATION DES FORMULAIRES
// ============================================

function validateClientForm() {
    let isValid = true;
    clearErrors();
    
    const requiredFields = [
        { id: 'nom', name: 'Nom' },
        { id: 'prenom', name: 'Prénom' },
        { id: 'email', name: 'Email' },
        { id: 'password', name: 'Mot de passe' },
        { id: 'confirm_password', name: 'Confirmation du mot de passe' }
    ];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            showError(field.id, `Le champ ${field.name} est obligatoire`);
            isValid = false;
        }
    });
    
    const emailField = document.getElementById('email');
    if (emailField && emailField.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            showError('email', 'Veuillez entrer une adresse email valide');
            isValid = false;
        }
    }
    
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && passwordField.value && confirmPasswordField && confirmPasswordField.value) {
        if (passwordField.value !== confirmPasswordField.value) {
            showError('confirm_password', 'Les mots de passe ne correspondent pas');
            isValid = false;
        }
        
        if (passwordField.value.length < 8) {
            showError('password', 'Le mot de passe doit contenir au moins 8 caractères');
            isValid = false;
        }
    }
    
    const termsCheckbox = document.getElementById('terms');
    if (termsCheckbox && !termsCheckbox.checked) {
        showError('terms', 'Vous devez accepter les conditions générales');
        isValid = false;
    }
    
    return isValid;
}

function validateProfessorForm() {
    let isValid = true;
    clearErrors();
    
    const requiredFields = [
        { id: 'nom_complet', name: 'Nom complet' },
        { id: 'titre_academique', name: 'Titre académique' },
        { id: 'email_academique', name: 'Email académique' },
        { id: 'universites', name: 'Universités' },
        { id: 'specialites', name: 'Domaines de recherche' },
        { id: 'password', name: 'Mot de passe' },
        { id: 'confirm_password', name: 'Confirmation du mot de passe' }
    ];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            showError(field.id, `Le champ ${field.name} est obligatoire`);
            isValid = false;
        }
    });
    
    const emailField = document.getElementById('email_academique');
    if (emailField && emailField.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            showError('email_academique', 'Veuillez entrer une adresse email académique valide');
            isValid = false;
        }
    }
    
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && passwordField.value && confirmPasswordField && confirmPasswordField.value) {
        if (passwordField.value !== confirmPasswordField.value) {
            showError('confirm_password', 'Les mots de passe ne correspondent pas');
            isValid = false;
        }
        
        if (passwordField.value.length < 12) {
            showError('password', 'Le mot de passe doit contenir au moins 12 caractères');
            isValid = false;
        }
    }
    
    const termsCheckbox = document.getElementById('terms_prof');
    if (termsCheckbox && !termsCheckbox.checked) {
        showError('terms_prof', 'Vous devez accepter les conditions de partenariat');
        isValid = false;
    }
    
    return isValid;
}

// ============================================
// GESTION DES ÉTAPES
// ============================================

function goToValidationStep(email, type = 'client') {
    const modal = type === 'client' ? 
        document.getElementById('successModal') : 
        document.getElementById('confirmationModal');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    const steps = document.querySelectorAll('.step');
    if (steps.length >= 2) {
        steps[0].classList.remove('active');
        steps[1].classList.add('active');
        updateContentForVerificationStep(email, type);
    }
}

function goToVerificationStep(email, nomComplet) {
    const confirmationModal = document.getElementById('confirmationModal');
    if (confirmationModal) {
        confirmationModal.style.display = 'none';
    }
    
    const steps = document.querySelectorAll('.step');
    if (steps.length >= 2) {
        steps[0].classList.remove('active');
        steps[1].classList.add('active');
        updateContentForProfessorVerification(email, nomComplet);
    }
}

// ============================================
// INTERFACES DE VÉRIFICATION
// ============================================

function updateContentForVerificationStep(email, type = 'client') {
    const authContainer = document.querySelector('.auth-container');
    if (!authContainer) return;
    
    const isDevelopment = window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1' || 
                         window.location.protocol === 'file:';
    
    let title, subtitle;
    if (type === 'client') {
        title = "Validation de votre compte";
        subtitle = "Veuillez vérifier votre adresse email pour activer votre compte";
    } else {
        title = "Vérification en cours";
        subtitle = "Votre demande est en cours de vérification par notre équipe";
    }
    
    let validationHTML = `
        <div class="auth-header">
            <a href="../index.html" class="auth-logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
            </a>
            <h1>${title}</h1>
            <p class="auth-subtitle">${subtitle}</p>
        </div>
        
        <div class="auth-steps">
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Informations</span>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <span class="step-text">${type === 'client' ? 'Validation' : 'Vérification'}</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span class="step-text">Complété</span>
            </div>
        </div>
        
        <div class="validation-content">
            <div class="validation-icon">
                <i class="fas fa-${type === 'client' ? 'envelope-circle-check' : 'user-check'}"></i>
            </div>
            
            <h3>${type === 'client' ? 'Email de confirmation envoyé' : 'Demande soumise avec succès'}</h3>`;
    
    if (type === 'client') {
        validationHTML += `
            <div class="email-confirmation">
                <p>Nous avons envoyé un email de confirmation à :</p>
                <div class="email-display">
                    <i class="fas fa-envelope"></i>
                    <strong>${email}</strong>
                </div>
                <p class="instruction">Veuillez cliquer sur le lien dans l'email pour valider votre adresse et activer votre compte.</p>
            </div>`;
    } else {
        validationHTML += `
            <div class="professor-validation">
                <div class="professor-info">
                    <div class="professor-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="professor-details">
                        <h4>${email.split('@')[0]}</h4>
                        <p>${email}</p>
                    </div>
                </div>
                
                <div class="verification-status">
                    <p><strong>📋 Vérification manuelle en cours</strong></p>
                    <p>Notre équipe académique examine votre profil. Ce processus prend généralement 48 heures.</p>
                </div>
                
                <ul class="verification-steps">
                    <li class="completed">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Formulaire soumis</strong>
                            <p>Vos informations ont été enregistrées</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-spinner fa-pulse"></i>
                        <div>
                            <strong>Vérification des informations</strong>
                            <p>Vérification de vos diplômes et expérience</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Approbation par l'équipe</strong>
                            <p>Validation finale par le comité académique</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Notification par email</strong>
                            <p>Vous recevrez un email avec la décision</p>
                        </div>
                    </li>
                </ul>
            </div>`;
    }
    
    if (isDevelopment) {
        validationHTML += `
            <div class="dev-bypass-section">
                <div class="dev-mode-notice">
                    <strong>🛠 MODE DÉVELOPPEMENT LOCAL</strong>
                    ${type === 'client' ? 
                        'Aucun email réel n\'a été envoyé. Pour continuer le test :' :
                        'Le processus de vérification est simulé. Pour accélérer le test :'}
                </div>
                
                <button class="btn-dev-bypass" id="devSimulate${type === 'client' ? 'Validation' : 'Approval'}">
                    🚀 Simuler ${type === 'client' ? 'la validation email' : 'l\'approbation'}
                </button>
                
                <div class="dev-email-preview">
                    <strong>Email simulé à :</strong> ${email}<br>
                    ${type === 'client' ? 
                        `<strong>Lien de vérification :</strong><br>
                        <code>http://localhost:3000/verify?token=dev_${Date.now()}_${Math.random().toString(36).substr(2, 9)}</code>` :
                        `<strong>Décision simulée :</strong><br>
                        <code>APPROUVÉ - Compte professeur activé avec succès</code>`}
                </div>
            </div>`;
    }
    
    validationHTML += `
            <div class="email-actions">
                <div class="action-item">
                    <i class="fas fa-redo"></i>
                    <div>
                        <h4>${type === 'client' ? 'Vous n\'avez pas reçu l\'email ?' : 'Vous voulez modifier votre demande ?'}</h4>
                        <p>${type === 'client' ? 
                            'Vérifiez votre dossier de spam ou <button id="resendEmail" class="btn-link">renvoyer l\'email</button>' :
                            '<button id="modifyRequest" class="btn-link">Modifier mes informations</button> ou <button id="contactSupport" class="btn-link">contacter le support</button>'}</p>
                    </div>
                </div>
                
                <div class="action-item">
                    <i class="fas fa-${type === 'client' ? 'edit' : 'question-circle'}"></i>
                    <div>
                        <h4>${type === 'client' ? 'Vous avez fait une erreur dans l\'email ?' : 'Questions fréquentes'}</h4>
                        <p>${type === 'client' ? 
                            '<button id="changeEmail" class="btn-link">Modifier l\'adresse email</button>' :
                            '<a href="faq_professeurs.html" class="btn-link" target="_blank">Consulter les FAQ</a>'}</p>
                    </div>
                </div>
            </div>
            
            ${type === 'client' ? `
            <div class="timer-display">
                <p>Vous pouvez fermer cette page. Votre formulaire sera sauvegardé pendant <span id="countdown">24:00:00</span>.</p>
            </div>` : ''}
            
            <div class="back-to-home">
                <a href="../index.html" class="btn-secondary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
            </div>
        </div>
        
        <div class="auth-footer">
            <p>Si vous avez des problèmes, <a href="contact.html">contactez-nous</a></p>
        </div>`;
    
    authContainer.innerHTML = validationHTML;
    
    setTimeout(() => {
        // Boutons communs
        const resendButton = document.getElementById('resendEmail');
        const changeEmailButton = document.getElementById('changeEmail');
        const modifyRequestButton = document.getElementById('modifyRequest');
        const contactSupportButton = document.getElementById('contactSupport');
        
        if (resendButton) {
            resendButton.addEventListener('click', function() {
                resendConfirmationEmail(email, type);
            });
        }
        
        if (changeEmailButton) {
            changeEmailButton.addEventListener('click', function() {
                goBackToInfoStep();
            });
        }
        
        if (modifyRequestButton) {
            modifyRequestButton.addEventListener('click', function() {
                goBackToInfoStep();
            });
        }
        
        if (contactSupportButton) {
            contactSupportButton.addEventListener('click', function() {
                window.location.href = 'contact.html';
            });
        }
        
        // Boutons développement
        const devSimulateButton = document.getElementById(type === 'client' ? 'devSimulateValidation' : 'devSimulateApproval');
        if (devSimulateButton && isDevelopment) {
            devSimulateButton.addEventListener('click', function() {
                showNotification(type === 'client' ? 'Validation email simulée avec succès !' : 'Demande professeur approuvée !', 'success');
                setTimeout(() => {
                    if (type === 'client') {
                        goToCompletedStep(email, 'client');
                    } else {
                        goToProfessorApprovalStep(email);
                    }
                }, 1500);
            });
        }
        
        // Compte à rebours pour les clients seulement
        if (type === 'client') {
            startCountdownTimer();
        }
    }, 100);
}

function updateContentForProfessorVerification(email, nomComplet) {
    const authContainer = document.querySelector('.auth-container');
    if (!authContainer) return;
    
    const isDevelopment = window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1' || 
                         window.location.protocol === 'file:';
    
    authContainer.innerHTML = `
        <div class="auth-header">
            <a href="../index.html" class="auth-logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
            </a>
            <h1>Vérification en cours</h1>
            <p class="auth-subtitle">Votre demande de compte professeur est en cours de traitement</p>
        </div>
        
        <div class="auth-steps">
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Informations</span>
            </div>
            <div class="step active">
                <span class="step-number">2</span>
                <span class="step-text">Vérification</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span class="step-text">Validation</span>
            </div>
        </div>
        
        <div class="validation-content">
            <div class="validation-icon">
                <i class="fas fa-user-check"></i>
            </div>
            
            <h3>Demande reçue avec succès</h3>
            
            <div class="professor-validation">
                <div class="professor-info">
                    <div class="professor-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="professor-details">
                        <h4>${nomComplet}</h4>
                        <p>${email}</p>
                        <p><small>Candidat Professeur Partenaire</small></p>
                    </div>
                </div>
                
                <div class="verification-status">
                    <p><strong>⏳ Processus de vérification</strong></p>
                    <p>Notre équipe académique examine attentivement votre dossier. Cette vérification manuelle garantit la qualité de notre communauté.</p>
                </div>
                
                <h4>Étapes de vérification :</h4>
                <ul class="verification-steps">
                    <li class="completed">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Soumission du formulaire</strong>
                            <p>Votre demande a été enregistrée</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-file-contract"></i>
                        <div>
                            <strong>Vérification des informations</strong>
                            <p>Examen de vos diplômes et expériences</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-university"></i>
                        <div>
                            <strong>Validation académique</strong>
                            <p>Confirmation de vos affiliations universitaires</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-users"></i>
                        <div>
                            <strong>Approbation du comité</strong>
                            <p>Décision finale par le comité académique ILA</p>
                        </div>
                    </li>
                </ul>
                
                <div class="instruction">
                    <p><strong>⏱️ Délai estimé :</strong> 24 à 48 heures</p>
                    <p><strong>📧 Notification :</strong> Vous recevrez un email à ${email} avec la décision</p>
                    <p><strong>📞 Contact :</strong> Pour toute urgence, contactez-nous à <a href="mailto:academique@ila.edu">academique@ila.edu</a></p>
                </div>
            </div>
            
            ${isDevelopment ? `
            <div class="dev-bypass-section">
                <div class="dev-mode-notice">
                    <strong>🛠 MODE DÉVELOPPEMENT LOCAL</strong>
                    Le processus de vérification est simulé. Pour tester le flux complet :
                </div>
                
                <button class="btn-dev-bypass" id="devSimulateProfessorApproval">
                    🚀 Simuler l'approbation du compte
                </button>
                
                <button class="btn-dev-bypass" id="devSimulateProfessorRejection" style="background: #dc3545; margin-top: 10px;">
                    🚫 Simuler un refus
                </button>
                
                <div class="dev-email-preview">
                    <strong>Processus simulé :</strong><br>
                    1. Vérification des diplômes ✓<br>
                    2. Confirmation des affiliations ✓<br>
                    3. Décision du comité : <strong>EN ATTENTE</strong>
                </div>
            </div>` : ''}
            
            <div class="email-actions">
                <div class="action-item">
                    <i class="fas fa-history"></i>
                    <div>
                        <h4>Suivi de votre demande</h4>
                        <p>Vous pouvez <button id="checkStatus" class="btn-link">vérifier le statut</button> ou <a href="contact.html?type=professeur" class="btn-link">contacter le support académique</a></p>
                    </div>
                </div>
                
                <div class="action-item">
                    <i class="fas fa-edit"></i>
                    <div>
                        <h4>Modifier votre demande</h4>
                        <p><button id="editApplication" class="btn-link">Modifier mes informations</button> avant la validation finale</p>
                    </div>
                </div>
            </div>
            
            <div class="back-to-home">
                <a href="../index.html" class="btn-secondary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
                <a href="professeurs.html" class="btn-secondary" style="margin-top: 10px;">
                    <i class="fas fa-chalkboard-teacher"></i> Voir les professeurs existants
                </a>
            </div>
        </div>
        
        <div class="auth-footer">
            <p>Merci de votre intérêt pour devenir Professeur Partenaire ILA !</p>
        </div>`;
    
    setTimeout(() => {
        const checkStatusButton = document.getElementById('checkStatus');
        const editApplicationButton = document.getElementById('editApplication');
        const devSimulateApprovalButton = document.getElementById('devSimulateProfessorApproval');
        const devSimulateRejectionButton = document.getElementById('devSimulateProfessorRejection');
        
        if (checkStatusButton) {
            checkStatusButton.addEventListener('click', function() {
                showNotification('Votre demande est toujours en cours de vérification. Vous recevrez un email une fois terminé.', 'info');
            });
        }
        
        if (editApplicationButton) {
            editApplicationButton.addEventListener('click', function() {
                goBackToInfoStep();
            });
        }
        
        if (devSimulateApprovalButton && isDevelopment) {
            devSimulateApprovalButton.addEventListener('click', function() {
                goToProfessorApprovalStep(email);
            });
        }
        
        if (devSimulateRejectionButton && isDevelopment) {
            devSimulateRejectionButton.addEventListener('click', function() {
                goToProfessorRejectionStep(email);
            });
        }
    }, 100);
}

// ============================================
// ÉTAPES FINALES
// ============================================

function goToCompletedStep(email, type = 'client') {
    const steps = document.querySelectorAll('.step');
    if (steps.length >= 3) {
        steps.forEach(step => step.classList.remove('active', 'completed'));
        steps[0].classList.add('completed');
        steps[1].classList.add('completed');
        steps[2].classList.add('active');
        updateContentForCompletedStep(email, type);
    }
}

function goToProfessorApprovalStep(email) {
    const steps = document.querySelectorAll('.step');
    if (steps.length >= 3) {
        steps.forEach(step => step.classList.remove('active', 'completed'));
        steps[0].classList.add('completed');
        steps[1].classList.add('completed');
        steps[2].classList.add('active');
        updateContentForProfessorApproval(email);
    }
}

function goToProfessorRejectionStep(email) {
    const authContainer = document.querySelector('.auth-container');
    if (!authContainer) return;
    
    authContainer.innerHTML = `
        <div class="auth-header">
            <a href="../index.html" class="auth-logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
            </a>
            <h1>Décision sur votre demande</h1>
            <p class="auth-subtitle">Réponse à votre candidature de Professeur Partenaire</p>
        </div>
        
        <div class="auth-steps">
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Informations</span>
            </div>
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Vérification</span>
            </div>
            <div class="step active">
                <span class="step-number">3</span>
                <span class="step-text">Validation</span>
            </div>
        </div>
        
        <div class="validation-content">
            <div class="validation-icon">
                <i class="fas fa-times-circle" style="color: #e74c3c;"></i>
            </div>
            
            <h3 style="color: #e74c3c;">Demande non retenue</h3>
            
            <div class="email-confirmation" style="border-left-color: #e74c3c;">
                <p>Après examen de votre dossier, nous regrettons de vous informer que votre demande de compte professeur n'a pas été retenue.</p>
                
                <div class="dev-mode-notice">
                    <strong>🛠 MODE DÉVELOPPEMENT - Simulation</strong>
                    En production, cette décision serait accompagnée d'explications détaillées.
                </div>
                
                <div class="instruction">
                    <p><strong>Raisons possibles (en développement) :</strong></p>
                    <ul style="text-align: left; margin: 15px 0;">
                        <li>Informations académiques insuffisamment documentées</li>
                        <li>Spécialités non alignées avec les axes de recherche ILA</li>
                        <li>Capacité d'accueil limitée de la plateforme</li>
                    </ul>
                </div>
            </div>
            
            <div class="email-actions">
                <div class="action-item">
                    <i class="fas fa-redo"></i>
                    <div>
                        <h4>Repostuler ultérieurement</h4>
                        <p>Vous pouvez <button onclick="window.location.reload()" class="btn-link">soumettre une nouvelle demande</button> après avoir complété votre profil</p>
                    </div>
                </div>
                
                <div class="action-item">
                    <i class="fas fa-question-circle"></i>
                    <div>
                        <h4>Besoin de précisions ?</h4>
                        <p><a href="contact.html?type=professeur_rejet" class="btn-link">Demander des explications détaillées</a></p>
                    </div>
                </div>
            </div>
            
            <div class="back-to-home">
                <a href="../index.html" class="btn-secondary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
                <a href="professeurs.html" class="btn-secondary" style="margin-top: 10px;">
                    <i class="fas fa-chalkboard-teacher"></i> Consulter les critères d'acceptation
                </a>
            </div>
        </div>
        
        <div class="auth-footer">
            <p>Nous vous remercions de l'intérêt porté à notre plateforme.</p>
        </div>`;
}

function updateContentForCompletedStep(email, type = 'client') {
    const authContainer = document.querySelector('.auth-container');
    if (!authContainer) return;
    
    const isDevelopment = window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1' || 
                         window.location.protocol === 'file:';
    
    const loginUrl = type === 'client' ? 'connexion.html' : 'connexion.html?type=professeur';
    const welcomeMessage = type === 'client' ? 
        'Votre compte client a été activé avec succès.' :
        'Félicitations ! Votre compte professeur a été validé.';
    
    authContainer.innerHTML = `
        <div class="auth-header">
            <a href="../index.html" class="auth-logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
            </a>
            <h1>${type === 'client' ? 'Inscription réussie !' : 'Compte professeur validé !'}</h1>
            <p class="auth-subtitle">${welcomeMessage}</p>
        </div>
        
        <div class="auth-steps">
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Informations</span>
            </div>
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">${type === 'client' ? 'Validation' : 'Vérification'}</span>
            </div>
            <div class="step active">
                <span class="step-number">3</span>
                <span class="step-text">Complété</span>
            </div>
        </div>
        
        <div class="validation-content">
            <div class="validation-icon success">
                <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
            </div>
            
            <h3>${type === 'client' ? 'Félicitations !' : 'Bienvenue Professeur !'}</h3>
            
            <div class="email-confirmation" style="border-left-color: #2ecc71;">
                <p>${welcomeMessage}</p>
                <div class="email-display">
                    <i class="fas fa-user-check" style="color: #2ecc71;"></i>
                    <strong>${email}</strong>
                </div>
                <p class="instruction">
                    ${type === 'client' ? 
                    'Vous pouvez maintenant vous connecter et bénéficier de tous les avantages du compte client.' :
                    'Accédez à votre espace professeur pour gérer vos publications et consulter les statistiques.'}
                </p>
            </div>
            
            ${isDevelopment ? `
            <div class="dev-mode-notice">
                <strong>🛠 MODE DÉVELOPPEMENT - Test terminé</strong>
                Le flux d'inscription ${type === 'client' ? 'client' : 'professeur'} a été testé avec succès en local.
            </div>` : ''}
            
            <div class="form-actions" style="margin-top: 40px;">
                <a href="${loginUrl}" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> ${type === 'client' ? 'Se connecter maintenant' : 'Accéder à mon espace professeur'}
                </a>
                
                <div class="auth-divider">
                    <span>ou</span>
                </div>
                
                <a href="../index.html" class="btn-secondary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
                ${type === 'professor' ? `
                <a href="professeurs.html" class="btn-secondary" style="margin-top: 10px;">
                    <i class="fas fa-chalkboard-teacher"></i> Voir mon futur profil
                </a>` : ''}
            </div>
        </div>
        
        <div class="auth-footer">
            <p>Merci de votre ${type === 'client' ? 'inscription' : 'candidature'} ! Pour toute question, <a href="contact.html">contactez-nous</a></p>
        </div>`;
}

function updateContentForProfessorApproval(email) {
    const authContainer = document.querySelector('.auth-container');
    if (!authContainer) return;
    
    const isDevelopment = window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1' || 
                         window.location.protocol === 'file:';
    
    authContainer.innerHTML = `
        <div class="auth-header">
            <a href="../index.html" class="auth-logo">
                <img src="../assets/images/logos/logo_ILA.png" alt="ILA Logo" width="80">
            </a>
            <h1>Félicitations Professeur !</h1>
            <p class="auth-subtitle">Votre compte professeur a été validé avec succès</p>
        </div>
        
        <div class="auth-steps">
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Informations</span>
            </div>
            <div class="step completed">
                <span class="step-number"><i class="fas fa-check"></i></span>
                <span class="step-text">Vérification</span>
            </div>
            <div class="step active">
                <span class="step-number">3</span>
                <span class="step-text">Validation</span>
            </div>
        </div>
        
        <div class="validation-content">
            <div class="validation-icon success">
                <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
            </div>
            
            <h3>Bienvenue dans la communauté ILA !</h3>
            
            <div class="professor-validation" style="border-left-color: #2ecc71;">
                <div class="professor-info">
                    <div class="professor-avatar" style="background: #2ecc71;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="professor-details">
                        <h4>Professeur Partenaire ILA</h4>
                        <p>${email}</p>
                        <p><small style="color: #2ecc71; font-weight: bold;">✓ Compte validé</small></p>
                    </div>
                </div>
                
                <div class="verification-status" style="border-left-color: #2ecc71; background: #f8fff8;">
                    <p><strong>🎉 Candidature approuvée !</strong></p>
                    <p>Le comité académique ILA a validé votre dossier. Vous faites maintenant partie de notre réseau de professeurs partenaires.</p>
                </div>
                
                <h4>Prochaines étapes :</h4>
                <ul class="verification-steps">
                    <li class="completed">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Activation du compte</strong>
                            <p>Votre compte est maintenant actif</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email de bienvenue</strong>
                            <p>Vous recevrez un email avec vos identifiants</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-book-open"></i>
                        <div>
                            <strong>Première publication</strong>
                            <p>Soumettez votre premier ouvrage</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <strong>Accès aux statistiques</strong>
                            <p>Suivez les performances de vos publications</p>
                        </div>
                    </li>
                </ul>
            </div>
            
            ${isDevelopment ? `
            <div class="dev-mode-notice">
                <strong>🛠 MODE DÉVELOPPEMENT - Simulation d'approbation</strong>
                En production, vous recevriez un email officiel avec vos accès.
            </div>` : ''}
            
            <div class="form-actions" style="margin-top: 40px;">
                <a href="connexion.html?type=professeur" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Accéder à mon espace professeur
                </a>
                
                <div class="auth-divider">
                    <span>ou</span>
                </div>
                
                <a href="../index.html" class="btn-secondary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
                <a href="professeurs.html" class="btn-secondary" style="margin-top: 10px;">
                    <i class="fas fa-chalkboard-teacher"></i> Voir les autres professeurs
                </a>
            </div>
        </div>
        
        <div class="auth-footer">
            <p>Bienvenue dans la communauté ILA ! <a href="contact.html?type=professeur_accueil">Découvrez nos ressources pour professeurs</a></p>
        </div>`;
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

function addDevNoticeToModal(modal, email, type, nomComplet = '') {
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal-content');
    if (!modalContent) return;
    
    const devSection = document.createElement('div');
    devSection.className = 'dev-bypass-section';
    
    const modalType = modal.id.includes('confirmation') ? 'professor' : 'client';
    
    devSection.innerHTML = `
        <div class="dev-mode-notice">
            <strong>🛠 MODE DÉVELOPPEMENT LOCAL</strong>
            Aucun email réel n'est envoyé. Pour tester le flux complet :
        </div>
        
        <button class="btn-dev-bypass" id="devBypassButton">
            🚀 ${modalType === 'client' ? 'Simuler la vérification email' : 'Simuler l\'approbation'}
        </button>
        
        <div class="dev-email-preview">
            <strong>${modalType === 'client' ? 'Email simulé à' : 'Candidature simulée'} :</strong> ${email}<br>
            ${nomComplet ? `<strong>Nom :</strong> ${nomComplet}<br>` : ''}
            <strong>${modalType === 'client' ? 'Sujet' : 'Type'} :</strong> ${modalType === 'client' ? 'Confirmation de votre compte ILA' : 'Validation de compte professeur'}<br>
            ${modalType === 'client' ? 
            `<strong>Lien de vérification :</strong> <br>
            <code>http://localhost:3000/verify?token=dev_test_${Date.now()}</code>` :
            `<strong>Décision simulée :</strong> <br>
            <code>APPROUVÉ - Compte professeur activé</code>`}
        </div>
        
        <div class="dev-console-info">
            📋 Info aussi disponible dans la console (F12)
        </div>
    `;
    
    const modalActions = modalContent.querySelector('.modal-actions');
    if (modalActions) {
        modalContent.insertBefore(devSection, modalActions.nextSibling);
    } else {
        modalContent.appendChild(devSection);
    }
    
    setTimeout(() => {
        const bypassButton = document.getElementById('devBypassButton');
        if (bypassButton) {
            bypassButton.addEventListener('click', function() {
                modal.style.display = 'none';
                if (modalType === 'client') {
                    goToCompletedStep(email, 'client');
                } else {
                    goToProfessorApprovalStep(email);
                }
            });
        }
    }, 100);
    
    console.log("🔧 MODE DÉVELOPPEMENT - Email simulé");
    console.log("=".repeat(50));
    console.log("📧 Destinataire :", email);
    if (nomComplet) console.log("👤 Nom :", nomComplet);
    console.log("💡 En production, un vrai email serait envoyé via un service SMTP");
    console.log("=".repeat(50));
}

function resendConfirmationEmail(email, type = 'client') {
    const resendButton = document.getElementById('resendEmail');
    if (resendButton) {
        const originalText = resendButton.textContent;
        resendButton.disabled = true;
        resendButton.textContent = 'Envoi en cours...';
        
        setTimeout(function() {
            showNotification(
                type === 'client' ? 
                    'Email de confirmation renvoyé avec succès !' : 
                    'Rappel envoyé à l\'équipe de vérification !', 
                'success'
            );
            resendButton.disabled = false;
            resendButton.textContent = originalText;
            
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.log(`📧 ${type === 'client' ? 'Email de confirmation' : 'Rappel'} renvoyé à : ${email}`);
            }
        }, 2000);
    }
}

function goBackToInfoStep() {
    window.location.reload();
}

function startCountdownTimer() {
    let hours = 24;
    let minutes = 0;
    let seconds = 0;
    
    const countdownElement = document.getElementById('countdown');
    if (!countdownElement) return;
    
    const timer = setInterval(function() {
        seconds--;
        if (seconds < 0) {
            seconds = 59;
            minutes--;
        }
        
        if (minutes < 0) {
            minutes = 59;
            hours--;
        }
        
        if (hours < 0) {
            clearInterval(timer);
            countdownElement.textContent = "00:00:00";
            showNotification("Le délai de validation a expiré. Veuillez recommencer l'inscription.", 'warning');
            return;
        }
        
        const formattedHours = hours.toString().padStart(2, '0');
        const formattedMinutes = minutes.toString().padStart(2, '0');
        const formattedSeconds = seconds.toString().padStart(2, '0');
        
        countdownElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
    }, 1000);
}

function showError(fieldId, message) {
    const errorElement = document.getElementById(`${fieldId}-error`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    const fieldElement = document.getElementById(fieldId);
    if (fieldElement) {
        fieldElement.classList.add('error');
    }
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.form-error');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
    
    const errorFields = document.querySelectorAll('.error');
    errorFields.forEach(field => {
        field.classList.remove('error');
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    const closeButton = notification.querySelector('.notification-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

// ============================================
// VALIDATION DES MOTS DE PASSE (fonctions globales)
// ============================================

function validatePasswordStrength() {
    const password = this.value;
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };
    
    const requirementItems = document.querySelectorAll('.password-requirements li');
    requirementItems.forEach(item => {
        const rule = item.getAttribute('data-rule');
        if (requirements[rule]) {
            item.classList.add('valid');
        } else {
            item.classList.remove('valid');
        }
    });
    
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    
    if (strengthBar && strengthText) {
        let strength = 0;
        let text = 'Faible';
        let colorClass = 'weak';
        
        Object.values(requirements).forEach(isMet => {
            if (isMet) strength++;
        });
        
        if (strength === 5) {
            text = 'Excellent';
            colorClass = 'excellent';
        } else if (strength >= 4) {
            text = 'Bon';
            colorClass = 'good';
        } else if (strength >= 3) {
            text = 'Moyen';
            colorClass = 'medium';
        }
        
        strengthBar.style.width = `${(strength / 5) * 100}%`;
        strengthBar.className = `strength-bar ${colorClass}`;
        strengthText.textContent = `Force du mot de passe: ${text}`;
    }
}

function validatePasswordMatch() {
    const password = document.getElementById('password')?.value;
    const confirmPassword = this.value;
    const matchElement = document.querySelector('.password-match');
    
    if (matchElement) {
        if (!confirmPassword) {
            matchElement.style.opacity = '0';
            return;
        }
        
        if (password === confirmPassword) {
            matchElement.classList.add('valid');
            matchElement.innerHTML = '<i class="fas fa-check-circle"></i><span>Les mots de passe correspondent</span>';
        } else {
            matchElement.classList.remove('valid');
            matchElement.innerHTML = '<i class="fas fa-times-circle"></i><span>Les mots de passe ne correspondent pas</span>';
        }
        
        matchElement.style.opacity = '1';
    }
}