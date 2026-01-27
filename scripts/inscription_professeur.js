// inscription_professeur.js - Version avec vérification de force du mot de passe
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
        
        // Compter les éléments existants
        this.diplomeCount = 0;
        this.experienceCount = 0;
        
        this.init();
    }

    init() {
        if (!this.form) return;

        this.initEventListeners();
        this.initDynamicLists();
        this.initPhotoUpload();
        this.initTitreToggle();
        this.initUniversites();
        this.initPasswordValidation(); // Nouveau
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

        // Nouveaux écouteurs pour le mot de passe
        if (this.passwordInput) {
            this.passwordInput.addEventListener('input', () => this.checkPasswordStrength());
        }
        
        if (this.confirmPasswordInput) {
            this.confirmPasswordInput.addEventListener('input', () => this.checkPasswordMatch());
        }
    }

    // Nouvelle méthode pour initialiser la validation du mot de passe
    initPasswordValidation() {
        // Règles de validation pour les professeurs (plus strictes)
        this.passwordRules = {
            length: { 
                regex: /.{12,}/, // 12 caractères minimum pour les professeurs
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

        // Initialiser l'affichage des règles si elles existent
        const passwordRequirements = document.querySelector('.password-requirements');
        if (passwordRequirements) {
            // Ajouter les règles dynamiquement ou les initialiser
            this.createPasswordRequirements();
        }
    }

    // Nouvelle méthode pour créer l'affichage des règles de mot de passe
    createPasswordRequirements() {
        const passwordGroup = this.passwordInput ? this.passwordInput.closest('.form-group') : null;
        if (!passwordGroup) return;

        // Créer la liste des exigences si elle n'existe pas
        let requirementsList = passwordGroup.querySelector('.password-requirements');
        if (!requirementsList) {
            requirementsList = document.createElement('ul');
            requirementsList.className = 'password-requirements';
            
            Object.keys(this.passwordRules).forEach(rule => {
                const li = document.createElement('li');
                li.setAttribute('data-rule', rule);
                li.textContent = this.passwordRules[rule].message;
                requirementsList.appendChild(li);
            });
            
            passwordGroup.appendChild(requirementsList);
        }
    }

    // Méthode pour vérifier la force du mot de passe (inspirée de inscription_client.js)
    checkPasswordStrength() {
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

        // Mettre à jour la barre de progression
        const percentage = (score / Object.keys(this.passwordRules).length) * 100;
        strengthBar.style.width = `${percentage}%`;
        
        // Déterminer la couleur et le texte
        let color, text;
        if (percentage < 40) {
            color = '#e74c3c'; // Rouge
            text = 'Faible';
        } else if (percentage < 70) {
            color = '#f39c12'; // Orange
            text = 'Moyen';
        } else if (percentage < 90) {
            color = '#3498db'; // Bleu
            text = 'Bon';
        } else {
            color = '#2ecc71'; // Vert
            text = 'Excellent';
        }
        
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = text;
        
        // Ajouter une classe pour le style
        strengthBar.classList.remove('weak', 'medium', 'good', 'excellent');
        if (percentage < 40) {
            strengthBar.classList.add('weak');
        } else if (percentage < 70) {
            strengthBar.classList.add('medium');
        } else if (percentage < 90) {
            strengthBar.classList.add('good');
        } else {
            strengthBar.classList.add('excellent');
        }
    }

    // Méthode pour vérifier la correspondance des mots de passe
    checkPasswordMatch() {
        const password = this.passwordInput.value;
        const confirm = this.confirmPasswordInput.value;
        const matchElement = document.querySelector('.password-match');
        
        if (!matchElement) return;
        
        if (!confirm) {
            matchElement.classList.remove('valid');
            matchElement.innerHTML = '<i class="fas fa-check-circle"></i><span>Confirmez votre mot de passe</span>';
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

    initPhotoUpload() {
        if (!this.photoInput) return;
        
        this.photoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Vérifier la taille
            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert('Le fichier est trop volumineux (max 5MB)');
                this.photoInput.value = '';
                return;
            }

            // Vérifier le type
            if (!file.type.match('image.*')) {
                alert('Veuillez sélectionner une image');
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
            
            if (e.target.value === 'autre') {
                autreField.style.display = 'block';
            } else {
                autreField.style.display = 'none';
            }
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

    addUniversite() {
        const value = this.universiteInput.value.trim();
        if (!value) return;

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
        
        // Mettre à jour le champ hidden
        this.updateUniversitesHidden();
    }

    updateUniversitesHidden() {
        const universites = Array.from(this.universitesList.querySelectorAll('.tag'))
            .map(tag => tag.childNodes[0].textContent.trim())
            .join(',');
        this.universitesHidden.value = universites;
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

    addDiplome() {
        this.diplomeCount++;
        const template = document.getElementById('diplomeTemplate');
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        
        const item = clone.querySelector('.dynamic-item');
        const indexSpan = item.querySelector('.item-index');
        if (indexSpan) {
            indexSpan.textContent = this.diplomeCount;
        }
        
        item.querySelectorAll('input, textarea, select').forEach(input => {
            const originalName = input.name;
            if (originalName.includes('[]')) {
                input.name = originalName.replace('[]', `[${this.diplomeCount}]`);
            }
        });
        
        const removeBtn = item.querySelector('.btn-remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                item.remove();
                this.reindexItems('diplome');
            });
        }
        
        const listItems = document.querySelector('#diplomes_list .list-items');
        if (listItems) {
            listItems.appendChild(item);
        }
    }

    addExperience() {
        this.experienceCount++;
        const template = document.getElementById('experienceTemplate');
        if (!template) return;
        
        const clone = template.content.cloneNode(true);
        
        const item = clone.querySelector('.dynamic-item');
        const indexSpan = item.querySelector('.item-index');
        if (indexSpan) {
            indexSpan.textContent = this.experienceCount;
        }
        
        item.querySelectorAll('input, textarea, select').forEach(input => {
            const originalName = input.name;
            if (originalName.includes('[]')) {
                input.name = originalName.replace('[]', `[${this.experienceCount}]`);
            }
        });
        
        const removeBtn = item.querySelector('.btn-remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                item.remove();
                this.reindexItems('experience');
            });
        }
        
        const actuelCheckbox = item.querySelector('input[type="checkbox"]');
        const finInput = item.querySelector('input[name*="experience_fin"]');
        
        if (actuelCheckbox && finInput) {
            actuelCheckbox.addEventListener('change', (e) => {
                finInput.disabled = e.target.checked;
                if (e.target.checked) {
                    finInput.value = '';
                }
            });
        }
        
        const listItems = document.querySelector('#experiences_list .list-items');
        if (listItems) {
            listItems.appendChild(item);
        }
    }

    reindexItems(type) {
        const items = document.querySelectorAll(`.dynamic-item[data-type="${type}"]`);
        let count = 0;
        
        items.forEach((item, index) => {
            count++;
            
            const indexSpan = item.querySelector('.item-index');
            if (indexSpan) {
                indexSpan.textContent = count;
            }
            
            item.querySelectorAll('input, textarea, select').forEach(input => {
                const name = input.name;
                const newName = name.replace(/\[\d+\]/g, `[${count}]`);
                input.name = newName;
            });
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

        switch (field.type) {
            case 'email':
                if (field.id === 'email_academique' && value) {
                    if (!value.match(/\.(edu|ac\..*|univ-.*)$/i)) {
                        this.showError(field, 'Veuillez utiliser un email académique');
                        return false;
                    }
                }
                if (value && !this.isValidEmail(value)) {
                    this.showError(field, 'Email invalide');
                    return false;
                }
                break;

            case 'password':
                // Nouvelle validation pour le mot de passe des professeurs
                if (field.id === 'password' && value) {
                    if (!this.isValidPassword(value)) {
                        this.showError(field, 'Le mot de passe ne respecte pas les critères de sécurité');
                        return false;
                    }
                }
                break;

            case 'url':
                if (value && !this.isValidUrl(value)) {
                    this.showError(field, 'URL invalide');
                    return false;
                }
                break;
        }

        return true;
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        let isValid = true;
        const requiredFields = this.form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Vérifier les mots de passe
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            this.showError(confirmPassword, 'Les mots de passe ne correspondent pas');
            isValid = false;
        }

        if (!isValid) {
            const firstError = this.form.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        this.showLoader(true);

        try {
            const formData = new FormData(this.form);
            
            const diplomes = this.getDynamicData('diplome');
            diplomes.forEach((diplome, index) => {
                Object.keys(diplome).forEach(key => {
                    formData.append(`diplomes[${index}][${key}]`, diplome[key]);
                });
            });
            
            const experiences = this.getDynamicData('experience');
            experiences.forEach((experience, index) => {
                Object.keys(experience).forEach(key => {
                    formData.append(`experiences[${index}][${key}]`, experience[key]);
                });
            });
            
            await this.submitRegistration(formData);

            const email = document.getElementById('email_academique').value;
            this.showConfirmation(email);

        } catch (error) {
            console.error('Erreur d\'inscription:', error);
            alert('Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');
        } finally {
            this.showLoader(false);
        }
    }

    getDynamicData(type) {
        const items = [];
        const elements = document.querySelectorAll(`.dynamic-item[data-type="${type}"]`);
        
        elements.forEach((element, index) => {
            const data = {};
            element.querySelectorAll('input, textarea, select').forEach(input => {
                const name = input.name;
                const fieldName = name.match(/\[([^\]]+)\]$/);
                
                if (fieldName) {
                    if (input.type === 'checkbox') {
                        data[fieldName[1]] = input.checked;
                    } else {
                        data[fieldName[1]] = input.value;
                    }
                }
            });
            items.push(data);
        });
        
        return items;
    }

    async submitRegistration(formData) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                console.log('Données professeur envoyées:', Object.fromEntries(formData));
                resolve({ success: true, message: 'Demande envoyée avec succès' });
            }, 3000);
        });
    }

    showConfirmation(email) {
        const profEmailElement = document.getElementById('profEmail');
        if (profEmailElement) {
            profEmailElement.textContent = email;
        }
        
        const modal = document.getElementById('confirmationModal');
        if (modal) {
            modal.style.display = 'flex';
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
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
        }
    }

    clearError(field) {
        field.classList.remove('error');
        const errorElement = document.getElementById(`${field.id}-error`);
        if (errorElement) {
            errorElement.textContent = '';
        }
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

    // Nouvelle méthode pour valider le mot de passe
    isValidPassword(password) {
        // Vérifier toutes les règles
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

// Initialiser quand la page est chargée
document.addEventListener('DOMContentLoaded', () => {
    new ProfessorRegistration();
});