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

    const observerOptions = {
        root: null,
        rootMargin: '-40% 0px -40% 0px',
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

    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 10) {
            if (sections.length > 0) {
                const lastSectionId = sections[sections.length - 1].getAttribute('id');
                setActiveLink(lastSectionId);
            }
        }
    });
});