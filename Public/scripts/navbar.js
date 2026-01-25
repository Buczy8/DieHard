document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    const avatarBtn = document.getElementById('avatarBtn');
    const dropdown = document.getElementById('userDropdown');

    if (avatarBtn && dropdown) {
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
            avatarBtn.classList.toggle('active');
        });

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

            const usernameEl = document.getElementById('nav-username');
            if (usernameEl) usernameEl.textContent = data.username;

            const avatarBtn = document.getElementById('avatarBtn');
            if (avatarBtn) {
                if (data.avatar) {
                    avatarBtn.innerHTML = `<img src="${data.avatar}" alt="Avatar" class="user-avatar-img">`;
                } else {
                    const avatarSrc = `https://ui-avatars.com/api/?name=${data.username}&background=random`;
                    avatarBtn.innerHTML = `<img src="${avatarSrc}" alt="Avatar" class="user-avatar-img">`;
                }
            }

            if (data.role === 'admin') {
                const adminLink = document.getElementById('nav-admin-link');
                if (adminLink) adminLink.classList.remove('hidden');
            }
        })
        .catch(err => console.error('Failed to load user info', err));
}

window.updateNavbarUserInfo = fetchUserInfo;