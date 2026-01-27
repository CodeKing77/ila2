// Données d'exemple pour les publications
const ouvrages = [
    {
        id: 1,
        title: "Afrique subsaharienne de l’Ouest/Côte d’Ivoire : Pour une école moins exclusive des réalités socioculturelles et linguistiques locales",
        author: "Prof. Jean Martial KOUAME",
        price: 20000,
        category: "scientifique",
        image: "assets/covers/img_livre_JMK.jpg",
        externalLink: "https://shs.cairn.info/revue-administration-et-education-2024-1-page-41?lang=fr",
        description: "École ivoirienne : héritages coloniaux, directives floues, réalités locales ignorées."
    },
    {
        id: 2,
        title: "Langues africaines - Alternatives et Emprunts",
        author: "Prof Jean Martial KOUAME",
        price: 15000,
        category: "didactique",
        image: "assets/covers/img_livre_jmk2.jpg",
        externalLink: "https://www.fabula.org/actualites/94668/kouame-koia-jean-martial-houmega-munseu-alida-kakou-foba-antoine-langues-africaines-alternances-et.html#:~:text=Langues%20africaines%20:%20alternances%20et%20emprunts%20est,Kouam%C3%A9%2C%20Munseu%20Alida%20Houm%C3%A9ga%2C%20Foba%20Antoine%20Kakou.",
        description: "Dictionnaire bilingue complet avec plus de 10 000 entrées."
    },
    {
        id: 3,
        title: "Cheminements linguistiques ",
        author: "Prof. N'Guessan Jéremie KOUADIO",
        price: 18000, /*Version exclusivement numérique*/
        category: "culturel",
        image: "assets/covers/img_livre_NJK.png",
        externalLink: "https://www.eyrolles.com/Litterature/Livre/cheminements-linguistiques-9783841610836/",
        description: "Panorama complet des langues parlées en Côte d'Ivoire."
    }
];

// Afficher les dernières publications sur la page d'accueil
document.addEventListener('DOMContentLoaded', function() {
    const publicationsGrid = document.getElementById('latest-publications');
    
    if (publicationsGrid) {
        // Prendre les 3 premières publications
        const latestPublications = ouvrages.slice(0, 3);
        
        latestPublications.forEach(ouvrage => {
            const publicationCard = document.createElement('div');
            publicationCard.className = 'publication-card';
            publicationCard.innerHTML = `
                <div class="publication-image">
                    <img src="${ouvrage.image}" alt="${ouvrage.title}">
                </div>
                <div class="publication-content">
                    <h3 class="publication-title">${ouvrage.title}</h3>
                    <p class="publication-author">${ouvrage.author}</p>
                    <!--<div class="publication-price">${ouvrage.price.toLocaleString()} XOF</div>-->
                                        <a href="${ouvrage.externalLink}" target="_blank" rel="noopener noreferrer">
                                            <button class="btn btn-primary add-to-cart" data-id="${ouvrage.id}" style="margin-top: 10px; width: 100%;">
                                                Lire
                                            </button>
                                        </a>
                </div>
            `;
            publicationsGrid.appendChild(publicationCard);
        });
        
        // Ajouter les événements pour les boutons "Ajouter au panier"
        /*const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = parseInt(this.getAttribute('data-id'));
                addToCart(productId);
            });
        });*/
    }
});

// Fonction pour ajouter au panier
/*function addToCart(productId) {
    const product = ouvrages.find(p => p.id === productId);
    if (!product) return;
    
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Vérifier si le produit est déjà dans le panier
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    alert(`${product.title} a été ajouté au panier.`);
}*/