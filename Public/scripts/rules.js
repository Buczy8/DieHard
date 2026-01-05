document.addEventListener('DOMContentLoaded', () => {

    const navLinks = document.querySelectorAll('.toc-link');
    const sections = document.querySelectorAll('.rules-section');

    const setActiveLink = (id) => {
        navLinks.forEach(link => {
            link.classList.remove('active');
            if(link.getAttribute('href') === `#${id}`) {
                link.classList.add('active');
            }
        });
    };

    // Ustawienie domyślne na starcie (pierwsza sekcja)
    if (sections.length > 0 && window.scrollY < 100) {
        setActiveLink(sections[0].getAttribute('id'));
    }

    const observerOptions = {
        root: null,
        // Zmieniamy marginesy: 
        // top: -100px (żeby navbar nie przeszkadzał)
        // bottom: -60% (żeby sekcja musiała wejść dość wysoko, by stać się aktywną)
        rootMargin: '-100px 0px -60% 0px', 
        threshold: 0
    };

    const observerCallback = (entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                setActiveLink(entry.target.getAttribute('id'));
            }
        });
    };

    const observer = new IntersectionObserver(observerCallback, observerOptions);
    sections.forEach(section => observer.observe(section));

    // Obsługa scrollowania na sam dół (dla ostatniej sekcji)
    window.addEventListener('scroll', () => {
        // Jeśli jesteśmy na samej górze, wymuś pierwszą sekcję
        if (window.scrollY < 50) {
            if (sections.length > 0) {
                setActiveLink(sections[0].getAttribute('id'));
            }
            return;
        }

        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 10) {
            if (sections.length > 0) {
                const lastSectionId = sections[sections.length - 1].getAttribute('id');
                setActiveLink(lastSectionId);
            }
        }
    });
});