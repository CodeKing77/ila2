// professor-form.js - Version unifiée pour l'inscription professeur
class ProfessorRegistration {
    constructor() {
        this.form = document.getElementById('professorRegistrationForm');
        this.photoInput = document.getElementById('photo');
        this.photoPreview = document.getElementById('photoPreview');
        this.titreSelect = document.getElementById('titre_academique');
        this.universiteInput = document.getElementById('universite_input');
        this.universitesList = document.getElementById('universites_list');
        this.universitesHidden = document.getElementById('universites');
        this.passwordInput = document.getElementById('password');
        this.confirmPasswordInput = document.getElementById('confirm_password');
        this.confirmationModal = document.getElementById('confirmationModal');
        
        // Configuration
        this.isDevelopment = this.checkDevelopmentMode();
        
        // Compteurs
        this.diplomeCount = 0;
        this.experienceCount = 0;
        this.step = 1;
        
        this.init();
    }

    checkDevelopmentMode() {
        return window.location.hostname === 'localhost' || 
               window.location.hostname === '127.0.0.1' || 
               window.location.protocol === 'file:';
    }

    init() {
        if (!this.form) return;

        this.initEventListeners();
        this.initDynamicLists();
        this.initPhotoUpload();
        this.initTitreToggle();
        this.initUniversites();
        this.initPasswordValidation();
        this.initModal();
        this.injectDevelopmentStyles();
    }

    injectDevelopmentStyles() {
        if (!this.isDevelopment) return;
        
        const devStyles = `
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
                width: 100%;
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
                word-break: break-all;
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
        `;
        
        const styleSheet = document.createElement("style");
        styleSheet.type = "text/css";
        styleSheet.innerText = devStyles;
        document.head.appendChild(styleSheet);
    }

    initEventListeners() {
        // Soumission du formulaire
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Validation en temps réel
        this.form.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });

        // Toggle mot de passe
        this.form.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => this.togglePasswordVisibility(e));
        });

        // Écouteurs pour le mot de passe
        if (this.passwordInput) {
            this.passwordInput.addEventListener('input', () => {
                this.checkPasswordStrength();
                this.checkPasswordMatch();
            });
        }
        
        if (this.confirmPasswordInput) {
            this.confirmPasswordInput.addEventListener('input', () => this.checkPasswordMatch());
        }

        // Menu toggle pour mobile
        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => this.toggleMobileMenu());
        }
    }

    initModal() {
        if (!this.confirmationModal) return;
        
        // Fermer modal en cliquant à l'extérieur
        this.confirmationModal.addEventListener('click', (e) => {
            if (e.target === this.confirmationModal) {
                this.closeModal();
            }
        });

        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.confirmationModal.style.display === 'flex') {
                this.closeModal();
            }
        });
    }

    initPhotoUpload() {
        if (!this.photoInput) return;
        
        this.photoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Vérifier la taille (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                this.showNotification('Le fichier est trop volumineux (max 5MB)', 'warning');
                this.photoInput.value = '';
                return;
            }

            // Vérifier le type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                this.showNotification('Format de fichier non supporté. Utilisez JPG, PNG ou GIF', 'warning');
                this.photoInput.value = '';
                return;
            }

            // Afficher la prévisualisation
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview.innerHTML = `<img src="${e.target.result}" alt="Photo de profil">`;
            };
            reader.readAsDataURL(file);
        });
    }

    initTitreToggle() {
        if (!this.titreSelect) return;
        
        this.titreSelect.addEventListener('change', (e) => {
            const autreField = document.getElementById('autre_titre_field');
            if (!autreField) return;
            
            autreField.style.display = e.target.value === 'autre' ? 'block' : 'none';
        });
    }

    initUniversites() {
        const addUniversiteBtn = document.getElementById('addUniversite');
        if (addUniversiteBtn) {
            addUniversiteBtn.addEventListener('click', () => this.addUniversite());
        }
        
        if (this.universiteInput) {
            this.universiteInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.addUniversite();
                }
            });
        }
    }

    initDynamicLists() {
        const diplomeBtn = document.querySelector('#diplomes_list .add-item');
        const experienceBtn = document.querySelector('#experiences_list .add-item');
        
        if (diplomeBtn) {
            diplomeBtn.addEventListener('click', () => this.addDiplome());
        }
        
        if (experienceBtn) {
            experienceBtn.addEventListener('click', () => this.addExperience());
        }
    }

    initPasswordValidation() {
        this.passwordRules = {
            length: { 
                regex: /.{12,}/,
                message: 'Minimum 12 caractères' 
            },
            uppercase: { 
                regex: /[A-Z]/, 
                message: 'Une majuscule' 
            },
            lowercase: { 
                regex: /[a-z]/, 
                message: 'Une minuscule' 
            },
            number: { 
                regex: /[0-9]/, 
                message: 'Un chiffre' 
            },
            special: { 
                regex: /[@$!%*?&]/, 
                message: 'Un caractère spécial (@$!%*?&)' 
            }
        };
    }

    addUniversite() {
        const value = this.universiteInput.value.trim();
        if (!value) {
            this.showNotification('Veuillez entrer le nom d\'une université', 'warning');
            return;
        }

        // Créer le tag
        const tag = document.createElement('div');
        tag.className = 'tag';
        tag.innerHTML = `
            ${value}
            <button type="button" class="tag-remove" aria-label="Supprimer cette université">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Ajouter l'événement de suppression
        tag.querySelector('.tag-remove').addEventListener('click', () => {
            tag.remove();
            this.updateUniversitesHidden();
        });

        // Ajouter à la liste
        this.universitesList.appendChild(tag);
        this.universiteInput.value = '';
        this.updateUniversitesHidden();
    }

    updateUniversitesHidden() {
        const universites = Array.from(this.universitesList.querySelectorAll('.tag'))
            .map(tag => tag.childNodes[0].textContent.trim())
            .join(', ');
        this.universitesHidden.value = universites;
    }

    addDiplome() {
        this.diplomeCount++;
        const template = document.getElementById('diplomeTemplate');
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.dynamic-item');
        const indexSpan = item.querySelector('.item-index');
        
        if (indexSpan) indexSpan.textContent = this.diplomeCount;
        
        const removeBtn = item.querySelector('.btn-remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                item.remove();
                this.reindexItems('diplome');
            });
        }
        
        const listItems = document.querySelector('#diplomes_list .list-items');
        if (listItems) listItems.appendChild(item);
    }

    addExperience() {
        this.experienceCount++;
        const template = document.getElementById('experienceTemplate');
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.dynamic-item');
        const indexSpan = item.querySelector('.item-index');
        
        if (indexSpan) indexSpan.textContent = this.experienceCount;
        
        const removeBtn = item.querySelector('.btn-remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                item.remove();
                this.reindexItems('experience');
            });
        }
        
        const actuelCheckbox = item.querySelector('input[type="checkbox"]');
        const finInput = item.querySelector('input[name*="experience_fin"]');
        
        if (actuelCheckbox && finInput) {
            actuelCheckbox.addEventListener('change', (e) => {
                finInput.disabled = e.target.checked;
                if (e.target.checked) finInput.value = '';
            });
        }
        
        const listItems = document.querySelector('#experiences_list .list-items');
        if (listItems) listItems.appendChild(item);
    }

    reindexItems(type) {
        const items = document.querySelectorAll(`.dynamic-item[data-type="${type}"]`);
        let count = 0;
        
        items.forEach((item, index) => {
            count++;
            const indexSpan = item.querySelector('.item-index');
            if (indexSpan) indexSpan.textContent = count;
        });
        
        if (type === 'diplome') {
            this.diplomeCount = count;
        } else if (type === 'experience') {
            this.experienceCount = count;
        }
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldId = field.id;
        const errorElement = document.getElementById(`${fieldId}-error`);
        
        if (!errorElement) return true;

        errorElement.textContent = '';
        field.classList.remove('error');

        if (field.hasAttribute('required') && !value) {
            this.showError(field, 'Ce champ est obligatoire');
            return false;
        }

        // Validation spécifique par type
        switch (field.type) {
            case 'email':
                if (field.id === 'email_academique' && value) {
                    // Validation email académique
                    const academicPattern = /\.(edu|ac\.[a-z]{2,}|univ-|\.edu\.[a-z]{2,})$/i;
                    if (!academicPattern.test(value)) {
                        this.showError(field, 'Veuillez utiliser un email académique (ex: prenom.nom@universite.edu)');
                        return false;
                    }
                }
                if (value && !this.isValidEmail(value)) {
                    this.showError(field, 'Format d\'email invalide');
                    return false;
                }
                break;

            case 'password':
                if (field.id === 'password' && value && !this.isValidPassword(value)) {
                    this.showError(field, 'Le mot de passe ne respecte pas les critères de sécurité');
                    return false;
                }
                break;

            case 'url':
                if (value && !this.isValidUrl(value)) {
                    this.showError(field, 'URL invalide (commencez par http:// ou https://)');
                    return false;
                }
                break;

            case 'text':
                if (field.id === 'nom_complet' && value && value.length < 5) {
                    this.showError(field, 'Le nom complet doit contenir au moins 5 caractères');
                    return false;
                }
                break;
        }

        return true;
    }

    async handleSubmit(e) {
    // NE PAS appeler preventDefault() immédiatement
    console.log("Form submission started...");
    
    let isValid = true;
    const requiredFields = this.form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            this.showError(field, 'Ce champ est obligatoire');
            isValid = false;
        }
    });

    // Vérifier les mots de passe
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (password && confirmPassword && password.value !== confirmPassword.value) {
        e.preventDefault(); // ✅ Seulement si erreur
        this.showError(confirmPassword, 'Les mots de passe ne correspondent pas');
        this.showNotification('Les mots de passe ne correspondent pas', 'warning');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault(); // ✅ Seulement si validation échoue
        const firstError = this.form.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        this.showNotification('Veuillez corriger les erreurs dans le formulaire', 'warning');
        return;
    }

    // Si validation OK, laisser PHP traiter la soumission
    console.log("Form validated, allowing PHP to process...");
    // NE PAS appeler e.preventDefault() - le formulaire sera soumis normalement
}

    showConfirmation(email, nomComplet) {
        console.log("Showing confirmation for:", email);
        
        // Mettre à jour l'email dans la modal
        const profEmailElement = document.getElementById('profEmail');
        if (profEmailElement) {
            profEmailElement.textContent = email;
        }
        
        // Afficher la modal
        if (this.confirmationModal) {
            this.confirmationModal.style.display = 'flex';
            
            // Ajouter le bouton de bypass en mode développement
            if (this.isDevelopment) {
                this.addDevBypassToModal(email, nomComplet);
            }
            
            // Après 4 secondes, passer à l'étape de vérification
            setTimeout(() => {
                this.closeModal();
                this.goToVerificationStep(email, nomComplet);
            }, 4000);
        }
    }

    addDevBypassToModal(email, nomComplet) {
        if (!this.confirmationModal) return;
        
        const modalContent = this.confirmationModal.querySelector('.modal-content');
        if (!modalContent) return;
        
        const devSection = document.createElement('div');
        devSection.className = 'dev-bypass-section';
        devSection.innerHTML = `
            <div class="dev-mode-notice">
                <strong>🛠 MODE DÉVELOPPEMENT LOCAL</strong>
                Aucun email réel n'est envoyé. Pour accélérer le test :
            </div>
            
            <button class="btn-dev-bypass" id="devBypassButton">
                🚀 Accélérer la vérification
            </button>
            
            <div class="dev-email-preview">
                <strong>Email simulé :</strong> ${email}<br>
                <strong>Nom :</strong> ${nomComplet}<br>
                <strong>Type :</strong> Validation de compte professeur<br>
                <strong>Décision simulée :</strong><br>
                <code>APPROUVÉ - Compte professeur activé</code>
            </div>
        `;
        
        const modalActions = modalContent.querySelector('.modal-actions');
        if (modalActions) {
            modalContent.insertBefore(devSection, modalActions);
        }
        
        // Écouter le bouton de bypass
        setTimeout(() => {
            const bypassButton = document.getElementById('devBypassButton');
            if (bypassButton) {
                bypassButton.addEventListener('click', () => {
                    this.closeModal();
                    this.goToVerificationStep(email, nomComplet);
                });
            }
        }, 100);
    }

    goToVerificationStep(email, nomComplet) {
        console.log("Going to verification step for:", email);
        
        // Mettre à jour les étapes visuelles
        this.updateSteps(2);
        
        // Mettre à jour le contenu de la page
        this.showVerificationContent(email, nomComplet);
    }

    updateSteps(stepNumber) {
        const steps = document.querySelectorAll('.step');
        if (!steps.length) return;
        
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            
            if (index + 1 < stepNumber) {
                step.classList.add('completed');
            } else if (index + 1 === stepNumber) {
                step.classList.add('active');
            }
        });
        
        this.step = stepNumber;
    }

    showVerificationContent(email, nomComplet) {
        const authContainer = document.querySelector('.auth-container');
        if (!authContainer) return;
        
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
                    </div>
                </div>
                
                ${this.isDevelopment ? `
                <div class="dev-bypass-section">
                    <div class="dev-mode-notice">
                        <strong>🛠 MODE DÉVELOPPEMENT LOCAL</strong>
                        Le processus de vérification est simulé. Pour tester le flux complet :
                    </div>
                    
                    <button class="btn-dev-bypass" id="devSimulateApproval">
                        🚀 Simuler l'approbation du compte
                    </button>
                    
                    <button class="btn-dev-bypass" id="devSimulateRejection" style="background: #dc3545; margin-top: 10px;">
                        🚫 Simuler un refus
                    </button>
                </div>` : ''}
                
                <div class="email-actions">
                    <div class="action-item">
                        <i class="fas fa-history"></i>
                        <div>
                            <h4>Suivi de votre demande</h4>
                            <p>Vous pouvez suivre le statut par email</p>
                        </div>
                    </div>
                    
                    <div class="action-item">
                        <i class="fas fa-edit"></i>
                        <div>
                            <h4>Modifier votre demande</h4>
                            <p>Contactez-nous si vous devez modifier vos informations</p>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 40px;">
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
            </div>
        `;
        
        // Ajouter les écouteurs d'événements pour les boutons de développement
        if (this.isDevelopment) {
            setTimeout(() => {
                const approveButton = document.getElementById('devSimulateApproval');
                const rejectButton = document.getElementById('devSimulateRejection');
                
                if (approveButton) {
                    approveButton.addEventListener('click', () => {
                        this.showApprovalContent(email, nomComplet);
                    });
                }
                
                if (rejectButton) {
                    rejectButton.addEventListener('click', () => {
                        this.showRejectionContent(email);
                    });
                }
            }, 100);
        }
    }

    showApprovalContent(email, nomComplet) {
        const authContainer = document.querySelector('.auth-container');
        if (!authContainer) return;
        
        this.updateSteps(3);
        
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
                            <h4>${nomComplet}</h4>
                            <p>${email}</p>
                            <p><small style="color: #2ecc71; font-weight: bold;">✓ Compte professeur validé</small></p>
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
                
                ${this.isDevelopment ? `
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
                </div>
            </div>
            
            <div class="auth-footer">
                <p>Bienvenue dans la communauté ILA !</p>
            </div>
        `;
    }

    showRejectionContent(email) {
        const authContainer = document.querySelector('.auth-container');
        if (!authContainer) return;
        
        this.updateSteps(3);
        
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
                    
                    ${this.isDevelopment ? `
                    <div class="dev-mode-notice">
                        <strong>🛠 MODE DÉVELOPPEMENT - Simulation</strong>
                        En production, cette décision serait accompagnée d'explications détaillées.
                    </div>` : ''}
                    
                    <div class="instruction">
                        <p>Vous pouvez postuler à nouveau après avoir complété votre profil académique.</p>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 40px;">
                    <button onclick="window.location.reload()" class="btn-secondary">
                        <i class="fas fa-redo"></i> Soumettre une nouvelle demande
                    </button>
                    
                    <a href="../index.html" class="btn-secondary" style="margin-top: 10px;">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
            
            <div class="auth-footer">
                <p>Nous vous remercions de l'intérêt porté à notre plateforme.</p>
            </div>
        `;
    }

    checkPasswordStrength() {
        if (!this.passwordInput) return;
        
        const password = this.passwordInput.value;
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.querySelector('.strength-text');
        
        if (!password || !strengthBar || !strengthText) {
            if (strengthBar) strengthBar.style.width = '0%';
            if (strengthText) strengthText.textContent = 'Force du mot de passe';
            return;
        }

        // Calculer la force
        let score = 0;
        Object.keys(this.passwordRules).forEach(rule => {
            const li = document.querySelector(`[data-rule="${rule}"]`);
            if (li && this.passwordRules[rule].regex.test(password)) {
                li.classList.add('valid');
                score++;
            } else if (li) {
                li.classList.remove('valid');
            }
        });

        // Mettre à jour la barre
        const percentage = (score / Object.keys(this.passwordRules).length) * 100;
        strengthBar.style.width = `${percentage}%`;
        
        let color, text;
        if (percentage < 40) {
            color = '#e74c3c';
            text = 'Faible';
        } else if (percentage < 70) {
            color = '#f39c12';
            text = 'Moyen';
        } else if (percentage < 90) {
            color = '#3498db';
            text = 'Bon';
        } else {
            color = '#2ecc71';
            text = 'Excellent';
        }
        
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = `Force du mot de passe: ${text}`;
    }

    checkPasswordMatch() {
        if (!this.passwordInput || !this.confirmPasswordInput) return;
        
        const password = this.passwordInput.value;
        const confirm = this.confirmPasswordInput.value;
        const matchElement = document.querySelector('.password-match');
        
        if (!matchElement) return;
        
        if (!confirm) {
            matchElement.style.opacity = '0';
            return;
        }

        if (password === confirm) {
            matchElement.classList.add('valid');
            matchElement.innerHTML = '<i class="fas fa-check-circle"></i><span>Les mots de passe correspondent</span>';
        } else {
            matchElement.classList.remove('valid');
            matchElement.innerHTML = '<i class="fas fa-times-circle"></i><span>Les mots de passe ne correspondent pas</span>';
        }
        
        matchElement.style.opacity = '1';
    }

    showLoader(show) {
        const submitButton = this.form.querySelector('button[type="submit"]');
        if (!submitButton) return;
        
        if (show) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-chalkboard-teacher"></i> Devenir Professeur Partenaire';
        }
    }

    showError(field, message) {
        field.classList.add('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    clearError(field) {
        field.classList.remove('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    showNotification(message, type = 'info') {  // Créer une notification temporaire
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 9999;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid ${type === 'success' ? '#2ecc71' : type === 'warning' ? '#f39c12' : type === 'error' ? '#e74c3c' : '#3498db'};
            transform: translateX(150%);
            transition: transform 0.3s ease;
        `;
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(150%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    togglePasswordVisibility(e) {
        const button = e.currentTarget;
        const passwordInput = button.closest('.password-input');
        if (!passwordInput) return;
        
        const input = passwordInput.querySelector('input');
        const icon = button.querySelector('i');
        
        if (!input || !icon) return;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
            button.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
            button.setAttribute('aria-label', 'Afficher le mot de passe');
        }
    }

    toggleMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        if (navMenu) {
            navMenu.classList.toggle('show');
        }
    }

    closeModal() {
        if (this.confirmationModal) {
            this.confirmationModal.style.display = 'none';
        }
    }

    isValidPassword(password) {
        for (const rule in this.passwordRules) {
            if (!this.passwordRules[rule].regex.test(password)) {
                return false;
            }
        }
        return true;
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
}

// Initialiser l'application quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM loaded, initializing ProfessorRegistration...");
    new ProfessorRegistration();
});