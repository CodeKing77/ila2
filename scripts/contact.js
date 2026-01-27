// Gestion de l'accordéon FAQ
document.addEventListener('DOMContentLoaded', function() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const faqAnswer = this.nextElementSibling;
            
            // Fermer tous les autres items
            document.querySelectorAll('.faq-item.active').forEach(activeItem => {
                if (activeItem !== faqItem) {
                    activeItem.classList.remove('active');
                    const activeAnswer = activeItem.querySelector('.faq-answer');
                    activeAnswer.style.maxHeight = '0';
                    activeAnswer.style.opacity = '0';
                    activeAnswer.style.padding = '0 var(--spacing-lg)';
                }
            });
            
            // Basculer l'état actuel
            faqItem.classList.toggle('active');
            
            if (faqItem.classList.contains('active')) {
                faqAnswer.style.maxHeight = faqAnswer.scrollHeight + 'px';
                faqAnswer.style.opacity = '1';
                faqAnswer.style.padding = 'var(--spacing-md) var(--spacing-lg)';
            } else {
                faqAnswer.style.maxHeight = '0';
                faqAnswer.style.opacity = '0';
                faqAnswer.style.padding = '0 var(--spacing-lg)';
            }
        });
    });
    
    // Ouvrir le premier élément FAQ par défaut
    const firstFaqItem = document.querySelector('.faq-item');
    if (firstFaqItem) {
        firstFaqItem.classList.add('active');
        const firstAnswer = firstFaqItem.querySelector('.faq-answer');
        firstAnswer.style.maxHeight = firstAnswer.scrollHeight + 'px';
        firstAnswer.style.opacity = '1';
        firstAnswer.style.padding = 'var(--spacing-md) var(--spacing-lg)';
    }
});