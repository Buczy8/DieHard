document.addEventListener('DOMContentLoaded', () => {
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdown = document.getElementById('userDropdown');

    if (!avatarBtn || !dropdown) return;

    // Kliknięcie w awatar
    avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Zapobiega zamknięciu przy kliknięciu w sam przycisk
        dropdown.classList.toggle('show');
        avatarBtn.classList.toggle('active');
    });

    // Kliknięcie gdziekolwiek indziej zamyka panel
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && e.target !== avatarBtn) {
            dropdown.classList.remove('show');
            avatarBtn.classList.remove('active');
        }
    });
});