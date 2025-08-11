// Common JS for popup logic (theme system removed; light mode only)
function closePopup() {
  const overlay = document.getElementById('popup-overlay');
  if (overlay) overlay.classList.add('hidden');
}
function openPopup(type) {
  // This should be implemented per page
}

// Small UX win: close on ESC and when clicking the dimmed overlay
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closePopup();
});
document.addEventListener('click', (e) => {
  const overlay = document.getElementById('popup-overlay');
  if (!overlay || overlay.classList.contains('hidden')) return;
  if (e.target === overlay) closePopup();
});
// No theme initialization needed
