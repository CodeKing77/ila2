// inscription_client.js
class ClientRegistration {
    constructor() {
        this.form = document.getElementById('clientRegistrationForm');
        this.passwordInput = document.getElementById('password');
        this.confirmPasswordInput = document.getElementById('confirm_password');
        this.typeClientInputs = document.querySelectorAll('input[name="type_client"]');
        this.entrepriseField = document.getElementById('entreprise-field');
        
        this.init();
    }

    init() {
        if (!this.form) return;

        this.initEventListeners();
        this.initPasswordValidation();
        this.initTypeClientToggle();
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

        // Force du mot de passe
        this.passwordInput.addEventListener('input', () => this.checkPasswordStrength());

        // Confirmation mot de passe
        this.confirmPasswordInput.addEventListener('input', () => this.checkPasswordMatch());
    }

    initPasswordValidation() {
        // Règles de validation
        this.passwordRules = {
            length: { regex: /.{8,}/, message: 'Minimum 8 caractères' },
            uppercase: { regex: /[A-Z]/, message: 'Une majuscule' },
            lowercase: { regex: /[a-z]/, message: 'Une minuscule' },
            number: { regex: /[0-9]/, message: 'Un chiffre' },
            special: { regex: /[@$!%*?&]/, message: 'Un caractère spécial (@$!%*?&)' }
        };

        // Initialiser les règles
        Object.keys(this.passwordRules).forEach(rule => {
            const li = document.querySelector(`[data-rule="${rule}"]`);
            if (li) li.classList.remove('valid');
        });
    }

    initTypeClientToggle() {
        this.typeClientInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                if (e.target.value === 'entreprise') {
                    this.entrepriseField.style.display = 'block';
                } else {
                    this.entrepriseField.style.display = 'none';
                }
            });
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldId = field.id;
        const errorElement = document.getElementById(`${fieldId}-error`);
        
        if (!errorElement) return;

        // Réinitialiser l'erreur
        errorElement.textContent = '';
        field.classList.remove('error');

        // Validation spécifique
        if (field.hasAttribute('required') && !value) {
            this.showError(field, 'Ce champ est obligatoire');
            return false;
        }

        switch (field.type) {
            case 'email':
                if (value && !this.isValidEmail(value)) {
                    this.showError(field, 'Email invalide');
                    return false;
                }
                break;

            case 'password':
                if (value && !this.isValidPassword(value)) {
                    this.showError(field, 'Le mot de passe ne respecte pas les critères');
                    return false;
                }
                break;

            case 'tel':
                if (value && !this.isValidPhone(value)) {
                    this.showError(field, 'Numéro de téléphone invalide');
                    return false;
                }
                break;

            case 'date':
                if (value) {
                    const birthDate = new Date(value);
                    const today = new Date();
                    const minAgeDate = new Date(today.getFullYear() - 13, today.getMonth(), today.getDate());
                    
                    if (birthDate > minAgeDate) {
                        this.showError(field, 'Vous devez avoir au moins 13 ans');
                        return false;
                    }
                }
                break;
        }

        // Validation pattern
        if (field.pattern && value) {
            const regex = new RegExp(field.pattern);
            if (!regex.test(value)) {
                this.showError(field, field.title || 'Format invalide');
                return false;
            }
        }

        return true;
    }

    checkPasswordStrength() {
        const password = this.passwordInput.value;
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.querySelector('.strength-text');
        
        if (!password) {
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#e74c3c';
            strengthText.textContent = 'Force du mot de passe';
            return;
        }

        // Calculer la force
        let score = 0;
        Object.keys(this.passwordRules).forEach(rule => {
            const li = document.querySelector(`[data-rule="${rule}"]`);
            if (this.passwordRules[rule].regex.test(password)) {
                li.classList.add('valid');
                score++;
            } else {
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
        } else {
            color = '#2ecc71';
            text = 'Fort';
        }
        
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = text;
    }

    checkPasswordMatch() {
        const password = this.passwordInput.value;
        const confirm = this.confirmPasswordInput.value;
        const matchElement = document.querySelector('.password-match');
        
        if (!confirm) {
            matchElement.classList.remove('valid');
            matchElement.innerHTML = '<i class="fas fa-check-circle"></i><span>Les mots de passe correspondent</span>';
            return;
        }

        if (password === confirm) {
            matchElement.classList.add('valid');
            matchElement.innerHTML = '<i class="fas fa-check-circle"></i><span>Les mots de passe correspondent</span>';
        } else {
            matchElement.classList.remove('valid');
            matchElement.innerHTML = '<i class="fas fa-times-circle"></i><span>Les mots de passe ne correspondent pas</span>';
        }
    }

    togglePasswordVisibility(e) {
        const button = e.currentTarget;
        const input = button.closest('.password-input').querySelector('input');
        const icon = button.querySelector('i');
        
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

    async handleSubmit(e) {
        e.preventDefault();
        
        // Valider tous les champs
        let isValid = true;
        const fields = this.form.querySelectorAll('[required]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Vérifier les mots de passe
        if (this.passwordInput.value !== this.confirmPasswordInput.value) {
            this.showError(this.confirmPasswordInput, 'Les mots de passe ne correspondent pas');
            isValid = false;
        }

        if (!isValid) {
            // Scroll vers la première erreur
            const firstError = this.form.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Afficher le loader
        this.showLoader(true);

        try {
            // Envoyer les données
            const formData = new FormData(this.form);
            const data = Object.fromEntries(formData.entries());

            // Simulation d'envoi - À remplacer par un vrai appel API
            await this.submitRegistration(data);

            // Afficher le succès
            this.showSuccess(data.email);

        } catch (error) {
            console.error('Erreur d\'inscription:', error);
            alert('Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');
        } finally {
            this.showLoader(false);
        }
    }

    async submitRegistration(data) {
        // Simulation d'API - À remplacer par un vrai appel
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                // Simuler un succès
                console.log('Données envoyées:', data);
                resolve({ success: true, message: 'Inscription réussie' });
                
                // Pour simuler une erreur, décommentez :
                // reject(new Error('Erreur serveur'));
            }, 2000);
        });
    }

    showSuccess(email) {
        // Mettre à jour l'email dans le modal
        document.getElementById('registeredEmail').textContent = email;
        
        // Afficher le modal
        const modal = document.getElementById('successModal');
        modal.style.display = 'flex';
        
        // Fermer le modal en cliquant à l'extérieur
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    showLoader(show) {
        const submitButton = this.form.querySelector('button[type="submit"]');
        
        if (show) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription en cours...';
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-user-plus"></i> Créer mon compte';
        }
    }

    showError(field, message) {
        field.classList.add('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    clearError(field) {
        field.classList.remove('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = '';
        }
    }

    // Méthodes de validation
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidPassword(password) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password);
    }

    isValidPhone(phone) {
        // Validation simple pour numéros internationaux
        return /^[\+\d\s\-\(\)]{8,20}$/.test(phone);
    }
}

// Initialiser quand la page est chargée
document.addEventListener('DOMContentLoaded', () => {
    new ClientRegistration();
});