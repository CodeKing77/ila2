<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - ILA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a3c40 0%, #4a8c6d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: #1a3c40;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #4a8c6d;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .password-strength {
            margin-top: 10px;
            font-size: 12px;
        }

        .strength-bar {
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }

        .btn {
            width: 100%;
            padding: 14px;
            background: #4a8c6d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn:hover {
            background: #3a7c5d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 140, 109, 0.3);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #4a8c6d;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .requirements {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .requirements li {
            margin-bottom: 5px;
        }

        .requirement-met {
            color: #28a745;
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

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 600px) {
            .content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-key"></i>
            <h1>Réinitialisation du mot de passe</h1>
            <p>Créez un nouveau mot de passe sécurisé</p>
        </div>

        <div class="content">
            <div id="alertBox" class="alert"></div>

            <form id="resetForm">
                <input type="hidden" id="token" name="token">

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-lock"></i> Nouveau mot de passe
                    </label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Entrez votre nouveau mot de passe"
                            required
                        >
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                    </div>
                    <div class="password-strength">
                        Force du mot de passe: <span id="strengthText">Faible</span>
                        <div class="strength-bar">
                            <div id="strengthBar" class="strength-bar-fill"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmer le mot de passe
                    </label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Confirmez votre mot de passe"
                            required
                        >
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>

                <div class="requirements">
                    <strong>Exigences du mot de passe :</strong>
                    <ul>
                        <li id="req-length">• Au moins 8 caractères</li>
                        <li id="req-upper">• Au moins une majuscule (A-Z)</li>
                        <li id="req-lower">• Au moins une minuscule (a-z)</li>
                        <li id="req-number">• Au moins un chiffre (0-9)</li>
                    </ul>
                </div>

                <button type="submit" id="submitBtn" class="btn">
                    <i class="fas fa-check-circle"></i> Réinitialiser le mot de passe
                </button>
            </form>

            <div class="back-link">
                <a href="../index.html">
                    <i class="fas fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>
        </div>
    </div>

    <script>
        // Récupérer le token depuis l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        // Vérifier la présence du token au chargement
        if (!token) {
            showAlert('danger', 'Token manquant. Veuillez utiliser le lien reçu par email.');
            document.getElementById('resetForm').style.display = 'none';
        } else {
            document.getElementById('token').value = token;
            verifyToken(token);
        }

        // Vérifier la validité du token
        async function verifyToken(token) {
            try {
                const response = await fetch('../pages/motdepasse_oublie.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'verify_token',
                        token: token
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    showAlert('danger', data.message);
                    document.getElementById('resetForm').style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur de vérification:', error);
                showAlert('danger', 'Erreur de connexion au serveur');
            }
        }

        // Gestion du formulaire
        document.getElementById('resetForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');

            // Validation côté client
            if (newPassword !== confirmPassword) {
                showAlert('danger', 'Les mots de passe ne correspondent pas');
                return;
            }

            if (!validatePassword(newPassword)) {
                showAlert('danger', 'Le mot de passe ne respecte pas les exigences');
                return;
            }

            // Désactiver le bouton
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Réinitialisation...';

            try {
                const formData = new FormData(e.target);
                formData.append('action', 'reset_password');

                const response = await fetch('../pages/motdepasse_oublie.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', data.message);
                    document.getElementById('resetForm').style.display = 'none';
                    setTimeout(() => {
                        window.location.href = '../index.html';
                    }, 2000);
                } else {
                    showAlert('danger', data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Réinitialiser le mot de passe';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('danger', 'Erreur de connexion au serveur');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Réinitialiser le mot de passe';
            }
        });

        // Validation du mot de passe
        function validatePassword(password) {
            return password.length >= 8 &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password);
        }

        // Vérification de la force du mot de passe en temps réel
        document.getElementById('new_password').addEventListener('input', (e) => {
            const password = e.target.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');

            let strength = 0;
            const checks = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            };

            // Mettre à jour les exigences
            document.getElementById('req-length').className = checks.length ? 'requirement-met' : '';
            document.getElementById('req-upper').className = checks.upper ? 'requirement-met' : '';
            document.getElementById('req-lower').className = checks.lower ? 'requirement-met' : '';
            document.getElementById('req-number').className = checks.number ? 'requirement-met' : '';

            // Calculer la force
            Object.values(checks).forEach(check => {
                if (check) strength += 25;
            });

            // Mettre à jour la barre
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.className = 'strength-bar-fill strength-weak';
                strengthText.textContent = 'Faible';
            } else if (strength <= 75) {
                strengthBar.className = 'strength-bar-fill strength-medium';
                strengthText.textContent = 'Moyen';
            } else {
                strengthBar.className = 'strength-bar-fill strength-strong';
                strengthText.textContent = 'Fort';
            }
        });

        // Basculer la visibilité du mot de passe
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Afficher une alerte
        function showAlert(type, message) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `alert alert-${type} show`;
            alertBox.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
</body>
</html>
