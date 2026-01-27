// auth-manager.js - VERSION FINALE CORRIGÉE
class AuthManager {
    constructor() {
        this.currentProfesseur = null;
        this.init();
    }

    init() {
        this.loadAuthState();
        this.initDropdownToggle();
        this.initAuthForm();
        this.initUserMenu();
        this.updateUI();
        this.initAuthStateListener();
    }

    loadAuthState() {
        const savedAuth = sessionStorage.getItem('ila_professeur_auth') || 
                         localStorage.getItem('ila_professeur_auth');
        if (savedAuth) {
            try {
                const authData = JSON.parse(savedAuth);
                this.currentProfesseur = authData.professeur;
            } catch (e) {
                console.error('Erreur lors du chargement de l\'état d\'authentification:', e);
                this.clearAuth();
            }
        }
    }

    clearAuth() {
        this.currentProfesseur = null;
        sessionStorage.removeItem('ila_professeur_auth');
        localStorage.removeItem('ila_professeur_auth');
    }

    initDropdownToggle() {
        const authToggle = document.getElementById('authToggle');
        const authDropdown = document.querySelector('.auth-dropdown-menu');
        
        if (authToggle && authDropdown) {
            authToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                
                document.querySelectorAll('.user-dropdown-menu.active').forEach(menu => {
                    if (menu !== authDropdown) menu.classList.remove('active');
                });
                
                authDropdown.classList.toggle('active');
                
                const chevron = authToggle.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.style.transform = authDropdown.classList.contains('active') 
                        ? 'rotate(180deg)' 
                        : 'rotate(0deg)';
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!authDropdown.contains(e.target) && !authToggle.contains(e.target)) {
                    authDropdown.classList.remove('active');
                    const chevron = authToggle.querySelector('.fa-chevron-down');
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                }
            });
            
            authDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }

    initAuthForm() {
        const professeurForm = document.getElementById('professeurForm');
        if (professeurForm) {
            professeurForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfesseurLogin(new FormData(professeurForm));
            });
        }
    }

    async handleProfesseurLogin(formData) {
        const email = formData.get('email') || 
                     document.querySelector('#professeurForm input[type="email"]')?.value;
        const password = formData.get('password') || 
                        document.querySelector('#professeurForm input[type="password"]')?.value;
        const rememberMe = formData.get('remember') === 'on' || 
                          document.querySelector('#professeurForm input[type="checkbox"]')?.checked;

        if (!email || !password) {
            this.showError('Veuillez remplir tous les champs');
            return;
        }

        this.showLoader(true);

        try {
            const response = await this.apiLogin({ email, password });
            
            console.log('✅ Réponse API:', response);
            
            if (response.success) {
                this.currentProfesseur = response.professeur;
                
                const authData = {
                    professeur: this.currentProfesseur,
                    timestamp: Date.now()
                };
                
                if (rememberMe) {
                    localStorage.setItem('ila_professeur_auth', JSON.stringify(authData));
                } else {
                    sessionStorage.setItem('ila_professeur_auth', JSON.stringify(authData));
                }

                this.updateUI();
                this.showSuccess('Connexion réussie !');
                
                setTimeout(() => {
                    window.location.href = response.redirect || 'espace_professeur/dashboard_personnalise.php';  // Creer ce fichier 
                }, 1000);

            } else {
                this.showError(response.message || 'Échec de la connexion');
            }
        } catch (error) {
            console.error('❌ Erreur de connexion:', error);
            this.showError('Erreur de connexion au serveur: ' + error.message);
        } finally {
            this.showLoader(false);
        }
    }

    async apiLogin(credentials) {
        const data = new URLSearchParams();
        data.append('email', credentials.email);
        data.append('password', credentials.password);
        
        console.log('🔐 Tentative de connexion:', credentials.email);
        
        // 🔧 FIX FINAL: Chemin relatif à la racine du site (pas ../api/)
        // Fonctionne que tu sois sur index.html ou dans un sous-dossier
        const response = await fetch('api/process_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data
        });
        
        console.log('📡 Statut HTTP:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('📦 Réponse brute:', result);
        
        // Adapter la réponse: PHP renvoie 'user', on le transforme en 'professeur'
        if (result.success && result.user) {
            result.professeur = {
                id: result.user.id,
                nom: (result.user.prenom || '') + ' ' + (result.user.nom || ''),
                prenom: result.user.prenom,
                email: result.user.email,
                titre: result.user.professeur_titre || result.user.titre_academique || '',
                avatar: 'professor'
            };
            console.log('✅ Professeur créé:', result.professeur);
        }
        
        return result;
    }

    handleLogout() {
        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
            this.clearAuth();
            
            // 🔧 FIX: Chemin relatif à la racine
            fetch('api/logout.php', { method: 'POST' })
                .then(() => {
                    this.updateUI();
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 500);
                })
                .catch(error => {
                    console.error('Erreur lors de la déconnexion:', error);
                    this.updateUI();
                    window.location.href = 'index.html';
                });
        }
    }

    initUserMenu() {
        document.querySelectorAll('.logout-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        });

        document.querySelectorAll('.user-menu-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const menu = e.currentTarget.closest('.user-profile').querySelector('.user-dropdown-menu');
                menu.classList.toggle('active');
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-profile')) {
                document.querySelectorAll('.user-dropdown-menu').forEach(menu => {
                    menu.classList.remove('active');
                });
            }
        });
    }

    initAuthStateListener() {
        window.addEventListener('storage', (e) => {
            if (e.key === 'ila_professeur_auth') {
                this.loadAuthState();
                this.updateUI();
            }
        });
    }

    updateUI() {
        const authDropdown = document.getElementById('authDropdown');
        if (!authDropdown) return;

        document.querySelectorAll('.auth-container').forEach(container => {
            container.classList.remove('active', 'hidden');
            container.style.display = 'none';
        });

        if (this.currentProfesseur) {
            const professeurContainer = authDropdown.querySelector('.professeur-logged');
            if (professeurContainer) {
                professeurContainer.style.display = 'block';
                professeurContainer.classList.add('active');
                this.updateProfesseurInfo(professeurContainer);
            }
            
            const notLoggedIn = authDropdown.querySelector('.not-logged-in');
            if (notLoggedIn) notLoggedIn.style.display = 'none';
        } else {
            const notLoggedIn = authDropdown.querySelector('.not-logged-in');
            if (notLoggedIn) {
                notLoggedIn.style.display = 'block';
                notLoggedIn.classList.add('active');
            }
            
            const professeurContainer = authDropdown.querySelector('.professeur-logged');
            if (professeurContainer) {
                professeurContainer.style.display = 'none';
            }
        }
    }

    updateProfesseurInfo(container) {
        if (!this.currentProfesseur) return;

        const userName = container.querySelector('.user-name');
        const userType = container.querySelector('.user-type');
        const userAvatar = container.querySelector('.user-avatar i');

        if (userName && this.currentProfesseur.nom) {
            userName.textContent = this.currentProfesseur.nom;
        }
        
        if (userType) {
            userType.textContent = 'Professeur';
        }
        
        if (userAvatar && this.currentProfesseur.avatar === 'professor') {
            userAvatar.className = 'fas fa-chalkboard-teacher';
        }
    }

    showLoader(show) {
        const submitBtn = document.querySelector('#professeurForm .btn-auth-submit');
        if (!submitBtn) return;

        if (show) {
            const originalText = submitBtn.innerHTML;
            submitBtn.setAttribute('data-original', originalText);
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
            submitBtn.disabled = true;
        } else {
            const originalText = submitBtn.getAttribute('data-original');
            if (originalText) {
                submitBtn.innerHTML = originalText;
            }
            submitBtn.disabled = false;
        }
    }

    showError(message) {
        this.clearMessages('.auth-error, .login-error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'auth-error';
        errorDiv.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            </div>
        `;
        
        const professeurForm = document.getElementById('professeurForm');
        if (professeurForm) {
            professeurForm.insertBefore(errorDiv, professeurForm.firstChild);
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        setTimeout(() => {
            errorDiv.style.transition = 'opacity 0.5s';
            errorDiv.style.opacity = '0';
            setTimeout(() => errorDiv.remove(), 500);
        }, 5000);
    }

    showSuccess(message) {
        const submitBtn = document.querySelector('#professeurForm .btn-auth-submit');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            submitBtn.style.background = '#28a745';
            submitBtn.disabled = true;
        }
    }

    clearMessages(selector) {
        document.querySelectorAll(selector).forEach(msg => msg.remove());
    }
}

const additionalCSS = `
.auth-error, .login-error {
    background: #f8d7da;
    color: #721c24;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #dc3545;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    animation: slideIn 0.3s ease;
}

.error-message {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-auth-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.fa-spinner {
    margin-right: 8px;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
`;

const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 AuthManager initialisé');
    window.authManager = new AuthManager();
    
    function checkLoginStatus() {
        // 🔧 FIX: Chemin relatif à la racine
        fetch('api/check_session.php')
            .then(response => response.json())
            .then(data => {
                console.log('🔍 Session check:', data);
                if (data.is_logged_in && data.user && !window.authManager.currentProfesseur) {
                    const authData = {
                        professeur: {
                            id: data.user.id,
                            nom: (data.user.prenom || '') + ' ' + (data.user.nom || ''),
                            prenom: data.user.prenom,
                            email: data.user.email,
                            titre: data.user.titre_academique || '',
                            avatar: 'professor'
                        },
                        timestamp: Date.now()
                    };
                    
                    sessionStorage.setItem('ila_professeur_auth', JSON.stringify(authData));
                    window.authManager.loadAuthState();
                    window.authManager.updateUI();
                }
            })
            .catch(error => console.error('❌ Erreur vérification session:', error));
    }
    
    checkLoginStatus();
});