function closePopup() {
    const popup = document.getElementById('messagePopup');
    popup.style.opacity = '0';
    setTimeout(() => {
        popup.remove();
    }, 300); // Czeka na koniec animacji zanikania
}

// Opcjonalnie: Zamknij klikając w tło
document.getElementById('messagePopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closePopup();
    }
});