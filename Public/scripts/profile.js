document.addEventListener('DOMContentLoaded', () => {
    fetchProfileData();
    setupAvatarModal();
    setupFormSubmission();
    setupThemeToggle();
});

function fetchProfileData() {
    fetch('/api/profile')
        .then(response => {
            if (!response.ok) throw new Error('Failed to load profile');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                showPopup('Error', data.error, 'error');
                return;
            }
            populateProfile(data);
        })
        .catch(err => {
            console.error(err);
            showPopup('Error', 'Could not load profile data.', 'error');
        });
}

function populateProfile(data) {
    document.getElementById('csrf_token').value = data.csrf;

    document.getElementById('profile-username').textContent = data.username;
    document.getElementById('display_name').value = data.username;
    document.getElementById('email').value = data.email;

    updateAvatarPreview(data.avatar);
}

function updateAvatarPreview(avatarUrl) {
    const container = document.getElementById('profile-avatar-container');
    if (avatarUrl) {
        container.innerHTML = `<img src="${avatarUrl}" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
    } else {
        container.innerHTML = `<i class="fa-solid fa-user"></i>`;
    }
}

function setupFormSubmission() {
    const form = document.getElementById('settingsForm');
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('/update-settings', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                showPopup(
                    data.type === 'success' ? 'Success!' : 'Error!',
                    data.message,
                    data.type
                );

                if (data.type === 'success' && data.user) {
                    document.getElementById('profile-username').textContent = data.user.username;
                    updateAvatarPreview(data.user.avatar);

                    document.getElementById('current_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_password').value = '';

                    if (window.updateNavbarUserInfo) {
                        window.updateNavbarUserInfo();
                    }
                }
            })
            .catch(err => {
                console.error(err);
                showPopup('Error', 'An unexpected error occurred.', 'error');
            });
    });
}

let currentSelection = {type: null, value: null};

function setupAvatarModal() {
    const changeBtn = document.getElementById('changeAvatarBtn');
    if (changeBtn) {
        changeBtn.addEventListener('click', () => toggleAvatarModal(true));
    }

    const fileInput = document.getElementById('avatar_upload_input');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            previewUploadedFile(this);
        });
    }

    const uploadZone = document.querySelector('.upload-zone');
    if (uploadZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        uploadZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files && files[0] && fileInput) {
                fileInput.files = files;
                previewUploadedFile(fileInput);
            }
        }
    }
}

window.toggleAvatarModal = function (show) {
    const modal = document.getElementById('avatarModal');
    if (modal) {
        modal.style.display = show ? 'flex' : 'none';
        if (show) switchTab('defaults');
    }
};

window.switchTab = function (tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

    const targetContent = document.getElementById(`tab-${tabName}`);
    if (targetContent) targetContent.classList.add('active');

    const buttons = document.querySelectorAll('.tab-btn');
    if (buttons.length > 0) {
        if (tabName === 'defaults') buttons[0].classList.add('active');
        else buttons[1].classList.add('active');
    }
};

window.selectDefault = function (filename, element) {
    document.querySelectorAll('.avatar-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    currentSelection = {type: 'default', value: filename};

    const fileInput = document.getElementById('avatar_upload_input');
    if (fileInput) fileInput.value = '';
};

window.previewUploadedFile = function (input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const uploadZone = document.querySelector('.upload-zone');
            if (uploadZone) {
                uploadZone.style.backgroundImage = `url(${e.target.result})`;
                uploadZone.style.backgroundSize = 'cover';
                uploadZone.style.backgroundPosition = 'center';
                uploadZone.innerHTML = '';
            }
            currentSelection = {type: 'upload', value: e.target.result};
        };
        reader.readAsDataURL(input.files[0]);
    }
};

window.confirmAvatarSelection = function () {
    const defaultInput = document.getElementById('input_default_avatar');
    const fileInput = document.getElementById('avatar_upload_input');

    if (currentSelection.type === 'default') {
        if (defaultInput) defaultInput.value = currentSelection.value;
        if (fileInput) fileInput.value = '';

        updateAvatarPreview(`Public/assets/avatars/${currentSelection.value}`);

    } else if (currentSelection.type === 'upload') {
        if (defaultInput) defaultInput.value = '';
        updateAvatarPreview(currentSelection.value);
    }

    toggleAvatarModal(false);
};

window.showPopup = function (title, message, type) {
    const popup = document.getElementById('messagePopup');
    const box = document.getElementById('popupBox');
    const icon = document.getElementById('popupIcon');
    const titleEl = document.getElementById('popupTitle');
    const msgEl = document.getElementById('popupMessage');

    if (!popup) return;

    box.className = 'popup-box';
    icon.className = 'fa-solid';

    if (type === 'success') {
        box.classList.add('success');
        icon.classList.add('fa-circle-check');
    } else {
        box.classList.add('error');
        icon.classList.add('fa-circle-xmark');
    }

    titleEl.textContent = title;
    msgEl.textContent = message;

    popup.style.display = 'flex';
    popup.style.opacity = '1';
};

window.closePopup = function () {
    const popup = document.getElementById('messagePopup');
    if (popup) {
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 300);
    }
};

function setupThemeToggle() {
    const themeCheckbox = document.getElementById('themeToggleCheckbox');
    if (!themeCheckbox) return;

    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        themeCheckbox.checked = true;
    } else {
        themeCheckbox.checked = false;
    }

    themeCheckbox.addEventListener('change', () => {
        if (themeCheckbox.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        }
    });
}