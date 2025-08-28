// JS commun pour la logique des popups (système de thème supprimé; mode clair uniquement)
function closePopup() {
  const overlay = document.getElementById('popup-overlay');
  if (overlay) overlay.classList.add('hidden');
}
function openPopup(type) {
  // Ceci doit être implémenté par page
}

// Amélioration UX: fermer avec ESC et en cliquant sur l'arrière-plan assombri
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closePopup();
});
document.addEventListener('click', (e) => {
  const overlay = document.getElementById('popup-overlay');
  if (!overlay || overlay.classList.contains('hidden')) return;
  if (e.target === overlay) closePopup();
});
// Pas d'initialisation de thème nécessaire
