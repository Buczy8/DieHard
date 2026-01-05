document.addEventListener('DOMContentLoaded', () => {
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdown = document.getElementById('userDropdown');

    if (avatarBtn && dropdown) {
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
    }

    fetchUserInfo();
});

function fetchUserInfo() {
    fetch('/api/user-info')
        .then(response => {
            if (!response.ok) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            // Update Username
            const usernameEl = document.getElementById('nav-username');
            if (usernameEl) usernameEl.textContent = data.username;

            // Update Avatar
            const avatarBtn = document.getElementById('avatarBtn');
            if (avatarBtn) {
                if (data.avatar) {
                    avatarBtn.innerHTML = `<img src="${data.avatar}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                } else {
                    avatarBtn.innerHTML = `<i class="fa-solid fa-user"></i>`;
                }
            }

            // Show Admin Link if role is admin
            if (data.role === 'admin') {
                const adminLink = document.getElementById('nav-admin-link');
                if (adminLink) adminLink.style.display = 'block';
            }
        })
        .catch(err => console.error('Failed to load user info', err));
}