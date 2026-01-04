document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Obsługa Popupów (Komunikatów) ---
    window.closePopup = function() {
        const popup = document.getElementById('messagePopup');
        if (popup) {
            popup.style.opacity = '0';
            setTimeout(() => {
                if (popup.parentNode) {
                    popup.parentNode.removeChild(popup);
                }
            }, 300);
        }
    };

    // Dodajemy listener TYLKO jeśli popup istnieje w DOM
    const messagePopup = document.getElementById('messagePopup');
    if (messagePopup) {
        messagePopup.addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    }

    // --- 2. Zmienne globalne dla Avatara ---
    let currentSelection = { type: null, value: null };

    // --- 3. Funkcje Modala ---
    window.toggleAvatarModal = function(show) {
        const modal = document.getElementById('avatarModal');
        if (modal) {
            modal.style.display = show ? 'flex' : 'none';
            if (show) {
                switchTab('defaults');
            }
        }
    };

    // Podpięcie przycisku "Change Avatar" z zabezpieczeniem
    const changeAvatarBtn = document.querySelector('.btn-secondary.btn-full-width');
    if (changeAvatarBtn) {
        changeAvatarBtn.addEventListener('click', () => {
            toggleAvatarModal(true);
        });
    }

    // --- 4. Przełączanie zakładek ---
    window.switchTab = function(tabName) {
        // Ukryj wszystkie treści
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

        // Pokaż wybraną treść
        const targetContent = document.getElementById(`tab-${tabName}`);
        if (targetContent) {
            targetContent.classList.add('active');
        }

        // Aktywuj odpowiedni przycisk
        const buttons = document.querySelectorAll('.tab-btn');
        if (buttons.length > 0) {
            if (tabName === 'defaults') {
                buttons[0].classList.add('active');
            } else {
                buttons[1].classList.add('active'); // Zakładamy, że upload jest drugi
            }
        }
    };

    // --- 5. Wybór domyślnego avatara ---
    window.selectDefault = function(filename, element) {
        // Usuń zaznaczenie z innych
        document.querySelectorAll('.avatar-option').forEach(el => el.classList.remove('selected'));
        // Zaznacz kliknięty
        element.classList.add('selected');

        // Zapisz wybór
        currentSelection = { type: 'default', value: filename };

        // Resetujemy input pliku, jeśli wcześniej coś wybrano
        const fileInput = document.getElementById('avatar_upload_input');
        if(fileInput) fileInput.value = '';
    };

    // --- 6. Podgląd Uploadu ---
    window.previewUploadedFile = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const uploadZone = document.querySelector('.upload-zone');
                if (uploadZone) {
                    uploadZone.style.backgroundImage = `url(${e.target.result})`;
                    uploadZone.style.backgroundSize = 'cover';
                    uploadZone.style.backgroundPosition = 'center';
                    uploadZone.innerHTML = ''; // Usuń tekst i ikonę wewnątrz strefy
                }
                currentSelection = { type: 'upload', value: e.target.result };
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    // --- 7. Zatwierdzenie (Confirm) ---
    window.confirmAvatarSelection = function() {
        const mainAvatarPreview = document.querySelector('.profile-avatar-container');
        const defaultInput = document.getElementById('input_default_avatar');
        const fileInput = document.getElementById('avatar_upload_input');

        if (currentSelection.type === 'default') {
            if (defaultInput) defaultInput.value = currentSelection.value;
            if (fileInput) fileInput.value = ''; // Czyścimy input pliku

            // Aktualizacja wizualna na głównej stronie
            if (mainAvatarPreview) {
                // Używamy assets/avatars/ zgodnie z twoim HTMLem
                mainAvatarPreview.innerHTML = `<img src="Public/assets/avatars/${currentSelection.value}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
            }

        } else if (currentSelection.type === 'upload') {
            if (defaultInput) defaultInput.value = ''; // Czyścimy domyślny

            // Aktualizacja wizualna (base64)
            if (mainAvatarPreview) {
                mainAvatarPreview.innerHTML = `<img src="${currentSelection.value}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
            }
        }

        toggleAvatarModal(false);
    };
});