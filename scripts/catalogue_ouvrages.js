/**
 * Catalogue des ouvrages - ILA
 * Affichage et filtrage dynamique des ouvrages
 */

document.addEventListener('DOMContentLoaded', function() {
    loadOuvrages();
    initFilters();
});

let allOuvrages = [];
let filteredOuvrages = [];
let currentPage = 1;
const itemsPerPage = 9;

/**
 * Charger les ouvrages depuis l'API
 */
async function loadOuvrages() {
    try {
        const response = await fetch('../api/get_ouvrages.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.ouvrages) {
            allOuvrages = data.ouvrages;
            filteredOuvrages = [...allOuvrages];
            displayOuvrages();
            updateResultsCount();
        } else {
            showError('Aucun ouvrage disponible pour le moment.');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des ouvrages:', error);
        showError('Erreur lors du chargement des ouvrages. Veuillez réessayer.');
    }
}

/**
 * Afficher les ouvrages
 */
function displayOuvrages() {
    const grid = document.getElementById('ouvragesGrid');
    
    if (!grid) return;
    
    // Vider la grille
    grid.innerHTML = '';
    
    if (filteredOuvrages.length === 0) {
        grid.innerHTML = '<p class="no-results">Aucun ouvrage ne correspond à vos critères.</p>';
        return;
    }
    
    // Pagination
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const ouvragesPage = filteredOuvrages.slice(start, end);
    
    // Afficher les ouvrages
    ouvragesPage.forEach(ouvrage => {
        const card = createOuvrageCard(ouvrage);
        grid.appendChild(card);
    });
    
    // Générer la pagination
    generatePagination();
}

/**
 * Créer une carte d'ouvrage
 */
function createOuvrageCard(ouvrage) {
    const card = document.createElement('div');
    card.className = 'ouvrage-card';
    
    // Image de couverture
    const coverUrl = ouvrage.couverture_url || '../assets/covers/default-book.png';
    
    // Formats disponibles
    const formats = [];
    if (ouvrage.is_physical == 1) formats.push('Physique');
    if (ouvrage.is_digital == 1) formats.push('Numérique');
    
    // Badge PDF si disponible
    const pdfBadge = ouvrage.fichier_pdf_url ? 
        '<span class="badge badge-pdf"><i class="fas fa-file-pdf"></i> PDF</span>' : '';
    
    // Badge stock
    const stockBadge = ouvrage.stock > 0 ? 
        `<span class="badge badge-stock">Stock: ${ouvrage.stock}</span>` : 
        '<span class="badge badge-stock badge-out">Épuisé</span>';
    
    card.innerHTML = `
        <div class="ouvrage-image">
            <img src="../${coverUrl}" alt="${escapeHtml(ouvrage.titre)}" loading="lazy">
            <div class="ouvrage-badges">
                ${pdfBadge}
                ${ouvrage.is_physical == 1 ? stockBadge : ''}
            </div>
        </div>
        <div class="ouvrage-content">
            <h3 class="ouvrage-title">${escapeHtml(ouvrage.titre)}</h3>
            ${ouvrage.sous_titre ? `<p class="ouvrage-subtitle">${escapeHtml(ouvrage.sous_titre)}</p>` : ''}
            <p class="ouvrage-author">
                <i class="fas fa-user"></i> ${escapeHtml(ouvrage.auteur_nom || 'Auteur inconnu')}
            </p>
            ${ouvrage.resume ? `<p class="ouvrage-resume">${escapeHtml(truncateText(ouvrage.resume, 120))}</p>` : ''}
            <div class="ouvrage-meta">
                <span class="meta-item">
                    <i class="fas fa-tag"></i> ${escapeHtml(ouvrage.categorie_nom || 'Non catégorisé')}
                </span>
                ${ouvrage.annee_publication ? `
                <span class="meta-item">
                    <i class="fas fa-calendar"></i> ${ouvrage.annee_publication}
                </span>` : ''}
                ${ouvrage.nombre_pages ? `
                <span class="meta-item">
                    <i class="fas fa-book-open"></i> ${ouvrage.nombre_pages} pages
                </span>` : ''}
            </div>
            <div class="ouvrage-formats">
                ${formats.map(f => `<span class="format-badge">${f}</span>`).join('')}
            </div>
            <div class="ouvrage-actions">
                ${ouvrage.fichier_pdf_url ? `
                <a href="../${ouvrage.fichier_pdf_url}" target="_blank" class="btn btn-primary btn-small" download>
                    <i class="fas fa-download"></i> Télécharger PDF
                </a>` : ''}
                <button class="btn btn-secondary btn-small" onclick="viewDetails(${ouvrage.id})">
                    <i class="fas fa-info-circle"></i> Détails
                </button>
            </div>
        </div>
    `;
    
    return card;
}

/**
 * Initialiser les filtres
 */
function initFilters() {
    const categorieFilter = document.getElementById('categorieFilter');
    const formatFilter = document.getElementById('formatFilter');
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    
    if (categorieFilter) {
        categorieFilter.addEventListener('change', applyFilters);
    }
    
    if (formatFilter) {
        formatFilter.addEventListener('change', applyFilters);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(applyFilters, 300));
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', applySort);
    }
}

/**
 * Appliquer les filtres
 */
function applyFilters() {
    const categorie = document.getElementById('categorieFilter')?.value || 'all';
    const format = document.getElementById('formatFilter')?.value || 'all';
    const search = document.getElementById('searchInput')?.value.toLowerCase() || '';
    
    filteredOuvrages = allOuvrages.filter(ouvrage => {
        // Filtre par catégorie
        if (categorie !== 'all') {
            const catNom = (ouvrage.categorie_nom || '').toLowerCase();
            if (!catNom.includes(categorie.toLowerCase())) {
                return false;
            }
        }
        
        // Filtre par format
        if (format !== 'all') {
            if (format === 'numerique' && ouvrage.is_digital != 1) {
                return false;
            }
            if (format === 'physique' && ouvrage.is_physical != 1) {
                return false;
            }
        }
        
        // Filtre par recherche
        if (search) {
            const titre = (ouvrage.titre || '').toLowerCase();
            const auteur = (ouvrage.auteur_nom || '').toLowerCase();
            const description = (ouvrage.description || '').toLowerCase();
            const resume = (ouvrage.resume || '').toLowerCase();
            
            if (!titre.includes(search) && 
                !auteur.includes(search) && 
                !description.includes(search) &&
                !resume.includes(search)) {
                return false;
            }
        }
        
        return true;
    });
    
    currentPage = 1;
    displayOuvrages();
    updateResultsCount();
}

/**
 * Appliquer le tri
 */
function applySort() {
    const sort = document.getElementById('sortSelect')?.value || 'date-desc';
    
    filteredOuvrages.sort((a, b) => {
        switch(sort) {
            case 'date-desc':
                return new Date(b.created_at) - new Date(a.created_at);
            case 'date-asc':
                return new Date(a.created_at) - new Date(b.created_at);
            case 'titre-asc':
                return (a.titre || '').localeCompare(b.titre || '');
            case 'titre-desc':
                return (b.titre || '').localeCompare(a.titre || '');
            default:
                return 0;
        }
    });
    
    displayOuvrages();
}

/**
 * Générer la pagination
 */
function generatePagination() {
    const paginationDiv = document.getElementById('pagination');
    
    if (!paginationDiv) return;
    
    const totalPages = Math.ceil(filteredOuvrages.length / itemsPerPage);
    
    if (totalPages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }
    
    let html = '<button class="btn-page" ' + 
               (currentPage === 1 ? 'disabled' : '') + 
               ' onclick="changePage(' + (currentPage - 1) + ')">Précédent</button>';
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            html += '<button class="btn-page ' + (i === currentPage ? 'active' : '') + 
                    '" onclick="changePage(' + i + ')">' + i + '</button>';
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            html += '<span class="pagination-ellipsis">...</span>';
        }
    }
    
    html += '<button class="btn-page" ' + 
            (currentPage === totalPages ? 'disabled' : '') + 
            ' onclick="changePage(' + (currentPage + 1) + ')">Suivant</button>';
    
    paginationDiv.innerHTML = html;
}

/**
 * Changer de page
 */
function changePage(page) {
    const totalPages = Math.ceil(filteredOuvrages.length / itemsPerPage);
    
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    displayOuvrages();
    
    // Scroll vers le haut de la grille
    document.getElementById('ouvragesGrid')?.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Mettre à jour le compteur de résultats
 */
function updateResultsCount() {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        const count = filteredOuvrages.length;
        resultsCount.textContent = `${count} ouvrage${count > 1 ? 's' : ''} trouvé${count > 1 ? 's' : ''}`;
    }
}

/**
 * Afficher les détails d'un ouvrage (à implémenter)
 */
function viewDetails(ouvrageId) {
    // Rediriger vers une page de détails ou ouvrir un modal
    console.log('Voir détails ouvrage:', ouvrageId);
    // window.location.href = `ouvrage-detail.html?id=${ouvrageId}`;
    alert('Fonctionnalité en cours de développement');
}

/**
 * Afficher une erreur
 */
function showError(message) {
    const grid = document.getElementById('ouvragesGrid');
    if (grid) {
        grid.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }
}

/**
 * Fonctions utilitaires
 */

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncateText(text, maxLength) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Rendre certaines fonctions globales
window.changePage = changePage;
window.viewDetails = viewDetails;
