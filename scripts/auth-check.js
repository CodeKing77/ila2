// auth-check.js - À inclure dans les pages protégées
document.addEventListener('DOMContentLoaded', () => {
    const authManager = window.authManager;
    
    if (!authManager || !authManager.currentUser) {
        // Rediriger vers la page de connexion
        window.location.href = 'connexion_client.html?redirect=' + encodeURIComponent(window.location.pathname);
        return;
    }
    
    // Vérifier les permissions selon la page
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('espace_professeur') && authManager.userType !== 'professeur') {
        window.location.href = '403.html'; // Page non autorisée
    }
    
    if (currentPage.includes('espace_client') && authManager.userType !== 'client') {
        window.location.href = '403.html';
    }
});


