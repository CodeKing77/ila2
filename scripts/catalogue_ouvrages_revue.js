/**
 * Gestionnaire complet du catalogue avec filtres
 */

class CatalogueManager {
    constructor() {
        this.currentPage = 1;
        this.currentFilters = {
            categorie: 'all',
            format: 'all',
            search: '',
            sort: 'date-desc'
        };
        
        this.init();
    }
    
    init() {
        // Initialiser les filtres avec les valeurs par défaut
        this.setupFilters();
        
        // Charger les ouvrages
        this.loadOuvrages();
    }
    
    setupFilters() {
        // Catégorie
        const categorieFilter = document.getElementById('categorieFilter');
        if (categorieFilter) {
            categorieFilter.value = this.currentFilters.categorie;
            categorieFilter.addEventListener('change', (e) => {
                this.currentFilters.categorie = e.target.value;
                this.currentPage = 1;
                this.loadOuvrages();
            });
        }
        
        // Format
        const formatFilter = document.getElementById('formatFilter');
        if (formatFilter) {
            formatFilter.value = this.currentFilters.format;
            formatFilter.addEventListener('change', (e) => {
                this.currentFilters.format = e.target.value;
                this.currentPage = 1;
                this.loadOuvrages();
            });
        }
        
        // Tri
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.value = this.currentFilters.sort;
            sortSelect.addEventListener('change', (e) => {
                this.currentFilters.sort = e.target.value;
                this.currentPage = 1;
                this.loadOuvrages();
            });
        }
        
        // Recherche
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = this.currentFilters.search;
            
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilters.search = e.target.value;
                    this.currentPage = 1;
                    this.loadOuvrages();
                }, 500);
            });
            
            // Ajouter un bouton de recherche clair
            searchInput.insertAdjacentHTML('afterend', 
                '<button id="clearSearch" style="margin-left: 5px; padding: 5px 10px; display: none;">X</button>'
            );
            
            document.getElementById('clearSearch').addEventListener('click', () => {
                searchInput.value = '';
                this.currentFilters.search = '';
                this.currentPage = 1;
                this.loadOuvrages();
                document.getElementById('clearSearch').style.display = 'none';
            });
        }
    }
    
    async loadOuvrages() {
        try {
            // Afficher le chargement
            this.showLoading();
            
            // Construire l'URL avec les filtres
            const params = new URLSearchParams();
            params.append('page', this.currentPage);
            params.append('limit', 9);
            
            // Ajouter les filtres seulement s'ils ne sont pas à la valeur par défaut
            if (this.currentFilters.categorie !== 'all') {
                params.append('categorie', this.currentFilters.categorie);
            }
            if (this.currentFilters.format !== 'all') {
                params.append('format', this.currentFilters.format);
            }
            if (this.currentFilters.search !== '') {
                params.append('search', this.currentFilters.search);
                
                // Afficher le bouton de suppression
                const clearBtn = document.getElementById('clearSearch');
                if (clearBtn) clearBtn.style.display = 'inline-block';
            }
            if (this.currentFilters.sort !== 'date-desc') {
                params.append('sort', this.currentFilters.sort);
            }
            
            const apiUrl = `../api/get_ouvrages_revue.php?${params.toString()}`; // fichier API modifié
            console.log('Chargement depuis:', apiUrl);
            
            const response = await fetch(apiUrl);
            const data = await response.json();
            
            console.log('Données reçues:', data);
            
            if (data.success) {
                // CORRECTION: Accès direct aux données (data.ouvrages au lieu de data.data.ouvrages)
                this.renderOuvrages(data.ouvrages);
                this.renderPagination(data.pagination);
                this.updateResultsCount(data.pagination.total);
                
                // Mettre à jour l'info des filtres actifs
                this.updateActiveFilters();
            } else {
                throw new Error(data.message || data.error || 'Erreur API');
            }
        } catch (error) {
            console.error('Erreur complète:', error);
            this.showError('Erreur de chargement: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    renderOuvrages(ouvrages) {
        // conserver la dernière liste pour retrouver un ouvrage par id
        this.lastOuvrages = ouvrages || [];
        const grid = document.getElementById('ouvragesGrid');
        if (!grid) return;
        
        if (!ouvrages || ouvrages.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <i class="fas fa-book-open fa-3x" style="color: #ccc;"></i>
                    <h3 style="color: #666;">Aucun ouvrage trouvé</h3>
                    <p style="color: #888; margin-bottom: 1rem;">
                        Aucun ouvrage ne correspond à vos critères de recherche.
                    </p>
                    <button onclick="catalogue.clearFilters()" 
                            style="background: #4a8c6d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                        Réinitialiser les filtres
                    </button>
                </div>
            `;
            return;
        }
        
        // Styles CSS en ligne pour simplifier
        const styles = {
            card: 'border: 1px solid #ddd; border-radius: 8px; padding: 1rem; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;',
            cardHover: 'transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.2);',
            title: 'color: #333; font-size: 1.2rem; margin-top: 0; line-height: 1.4;',
            description: 'color: #666; font-size: 0.9rem; line-height: 1.5; max-height: 3.6em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;',
            meta: 'color: #888; font-size: 0.85rem; margin: 0.5rem 0;',
            category: 'display: inline-block; background: #4a8c6d; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem;',
            formatBadge: 'display: inline-block; background: #e9ecef; color: #495057; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem; margin-right: 5px;',
            footer: 'border-top: 1px solid #eee; padding-top: 1rem; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;',
            price: 'font-weight: bold; color: #4a8c6d;',
            button: 'background: #4a8c6d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: background 0.3s;'
        };
        
        grid.innerHTML = ouvrages.map(ouvrage => {
            // CORRECTION: Accéder correctement à la catégorie (vérifier structure)
            const categorieNom = ouvrage.categorie?.nom || ouvrage.categorie_nom || 'Non catégorisé';
            const categorieCouleur = ouvrage.categorie?.couleur || ouvrage.categorie_couleur || '#4a8c6d';
            
            return `
            <div style="${styles.card}" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)';" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <h3 style="${styles.title}">${ouvrage.titre || 'Sans titre'}</h3>
                    <span style="display: inline-block; background: ${categorieCouleur}; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem;">${categorieNom}</span>
                </div>
                
                <p style="${styles.description}">${ouvrage.description || ouvrage.resume || 'Pas de description disponible'}</p>
                
                <div style="${styles.meta}">
                    <span style="margin-right: 1rem;">
                        <i class="fas fa-calendar-alt"></i> ${ouvrage.annee || ouvrage.annee_publication || 'N/A'}
                    </span>
                    <span>
                        <i class="fas fa-language"></i> ${ouvrage.langue || 'Français'}
                    </span>
                </div>
                
                <div style="margin: 0.75rem 0;">
                    <strong style="color: #333; display: block; margin-bottom: 0.3rem;">Auteur:</strong>
                    <span style="color: #555;">${ouvrage.auteur_titre || ''} ${ouvrage.auteur || ouvrage.auteur_nom || 'Auteur inconnu'}</span>
                </div>
                
                <div style="display: flex; gap: 5px; margin: 0.5rem 0;">
                    ${ouvrage.is_physical ? `<span style="${styles.formatBadge}"><i class="fas fa-book"></i> Physique</span>` : ''}
                    ${ouvrage.is_digital ? `<span style="${styles.formatBadge}"><i class="fas fa-file-pdf"></i> Numérique</span>` : ''}
                </div>
                
                <div style="${styles.footer}">
                    ${ouvrage.prix_promotion ? `<span style="${styles.price}">${ouvrage.prix_promotion} FCFA</span>` : '<span style="color: #999;">Gratuit</span>'}
                    <button onclick="catalogue.showDetails(${ouvrage.id})" style="${styles.button}" onmouseover="this.style.background='#3a7c5d'" onmouseout="this.style.background='#4a8c6d'">
                        <i class="fas fa-eye"></i> Voir plus
                    </button>
                </div>
            </div>
        `;
        }).join('');
    }
    
    renderPagination(pagination) {
        const paginationEl = document.getElementById('pagination');
        if (!paginationEl || pagination.totalPages <= 1) {
            if (paginationEl) paginationEl.innerHTML = '';
            return;
        }
        
        const btnStyle = 'padding: 8px 12px; margin: 0 3px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; transition: all 0.3s;';
        const activeStyle = 'background: #4a8c6d; color: white; border-color: #4a8c6d;';
        const disabledStyle = 'opacity: 0.5; cursor: not-allowed;';
        
        let html = '';
        
        // Bouton précédent
        html += `
            <button ${this.currentPage === 1 ? 'disabled' : ''} 
                    onclick="catalogue.changePage(${this.currentPage - 1})"
                    style="${btnStyle} ${this.currentPage === 1 ? disabledStyle : ''}">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Pages
        for (let i = 1; i <= pagination.totalPages; i++) {
            if (i === 1 || i === pagination.totalPages || 
                (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                html += `
                    <button onclick="catalogue.changePage(${i})"
                            style="${btnStyle} ${i === this.currentPage ? activeStyle : ''}">
                        ${i}
                    </button>
                `;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                html += `<span style="padding: 5px 10px;">...</span>`;
            }
        }
        
        // Bouton suivant
        html += `
            <button ${this.currentPage === pagination.totalPages ? 'disabled' : ''} 
                    onclick="catalogue.changePage(${this.currentPage + 1})"
                    style="${btnStyle} ${this.currentPage === pagination.totalPages ? disabledStyle : ''}">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        paginationEl.innerHTML = html;
    }
    
    changePage(page) {
        if (page < 1) return;
        this.currentPage = page;
        this.loadOuvrages();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    updateResultsCount(total) {
        const countEl = document.getElementById('resultsCount');
        if (countEl) {
            countEl.textContent = `${total} ouvrage${total !== 1 ? 's' : ''} trouvé${total !== 1 ? 's' : ''}`;
        }
    }
    
    updateActiveFilters() {
        // Afficher les filtres actifs
        const activeFilters = [];
        
        if (this.currentFilters.categorie !== 'all') {
            const categorieSelect = document.getElementById('categorieFilter');
            const selectedOption = categorieSelect.options[categorieSelect.selectedIndex];
            activeFilters.push(`Catégorie: ${selectedOption.text}`);
        }
        
        if (this.currentFilters.format !== 'all') {
            const formatSelect = document.getElementById('formatFilter');
            const selectedOption = formatSelect.options[formatSelect.selectedIndex];
            activeFilters.push(`Format: ${selectedOption.text}`);
        }
        
        if (this.currentFilters.search !== '') {
            activeFilters.push(`Recherche: "${this.currentFilters.search}"`);
        }
        
        if (activeFilters.length > 0) {
            // Créer ou mettre à jour la zone des filtres actifs
            let filterInfo = document.getElementById('activeFilters');
            if (!filterInfo) {
                filterInfo = document.createElement('div');
                filterInfo.id = 'activeFilters';
                filterInfo.style.cssText = 'background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0;';
                document.querySelector('.filters-section').parentNode.insertBefore(filterInfo, document.querySelector('.results-info'));
            }
            
            filterInfo.innerHTML = `
                <strong>Filtres actifs:</strong> ${activeFilters.join(', ')}
                <button onclick="catalogue.clearFilters()" 
                        style="background: #6c757d; color: white; border: none; padding: 2px 8px; margin-left: 10px; border-radius: 3px; font-size: 0.8rem; cursor: pointer;">
                    Tout effacer
                </button>
            `;
        } else {
            // Supprimer la zone des filtres actifs
            const filterInfo = document.getElementById('activeFilters');
            if (filterInfo) filterInfo.remove();
        }
    }
    
    clearFilters() {
        // Réinitialiser les filtres
        this.currentFilters = {
            categorie: 'all',
            format: 'all',
            search: '',
            sort: 'date-desc'
        };
        
        // Réinitialiser les champs du formulaire
        const categorieFilter = document.getElementById('categorieFilter');
        const formatFilter = document.getElementById('formatFilter');
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');
        
        if (categorieFilter) categorieFilter.value = 'all';
        if (formatFilter) formatFilter.value = 'all';
        if (searchInput) {
            searchInput.value = '';
            const clearBtn = document.getElementById('clearSearch');
            if (clearBtn) clearBtn.style.display = 'none';
        }
        if (sortSelect) sortSelect.value = 'date-desc';
        
        // Supprimer la zone des filtres actifs
        const filterInfo = document.getElementById('activeFilters');
        if (filterInfo) filterInfo.remove();
        
        // Recharger
        this.currentPage = 1;
        this.loadOuvrages();
    }
    
    showLoading() {
        const grid = document.getElementById('ouvragesGrid');
        if (grid) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: #4a8c6d;"></i>
                    <p>Chargement des ouvrages...</p>
                </div>
            `;
        }
    }
    
    hideLoading() {
        // Géré par renderOuvrages
    }
    
    showError(message) {
        const grid = document.getElementById('ouvragesGrid');
        if (grid) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #dc3545;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <h3>Erreur de chargement</h3>
                    <p>${message}</p>
                    <button onclick="catalogue.loadOuvrages()" 
                            style="background: #4a8c6d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                        <i class="fas fa-redo"></i> Réessayer
                    </button>
                </div>
            `;
        }
    }
    
    showDetails(ouvrageId) {
        // Trouver l'ouvrage et ouvrir le lien externe si disponible, sinon page locale
        const ouvrage = (this.lastOuvrages || []).find(o => o.id === ouvrageId);
        if (!ouvrage) {
            window.location.href = `detail_ouvrage.html?id=${ouvrageId}`;
            return;
        }

        const external = this.getExternalLinkByTitle(ouvrage.titre);
        if (external) {
            window.open(external, '_blank', 'noopener,noreferrer');
        } else {
            window.location.href = `detail_ouvrage.html?id=${ouvrageId}`;
        }
    }

    getExternalLinkByTitle(title) {
        if (!title) return null;
        const t = title.toLowerCase();

        // Correspondances basées sur des mots-clés pour tolérer de légères variations de titre
        if (t.includes('afrique subsaharienne') || (t.includes('cote') && t.includes('ivoire')) || t.includes('école moins exclusive')) {
            return 'https://shs.cairn.info/revue-administration-et-education-2024-1-page-41?lang=fr';
        }

        if (t.includes('langues africaines')) {
            return 'https://www.fabula.org/actualites/94668/kouame-koia-jean-martial-houmega-munseu-alida-kakou-foba-antoine-langues-africaines-alternances-et.html#:~:text=Langues%20africaines%20:%20alternances%20et%20emprunts%20est,Kouam%C3%A9%2C%20Munseu%20Alida%20Houm%C3%A9ga%2C%20Foba%20Antoine%20Kakou.';
        }

        if (t.includes('cheminements')) {
            return 'https://www.eyrolles.com/Litterature/Livre/cheminements-linguistiques-9783841610836/';
        }

        return null;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.catalogue = new CatalogueManager();
});
