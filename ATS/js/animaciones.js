const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
});

const userProfile = document.getElementById('profilePic');
const profileDropdown = document.getElementById('profileDropdown');

userProfile.addEventListener('click', () => {
    profileDropdown.classList.toggle('active');
});

const viewProfile = document.getElementById('viewProfile');
const profileModal = document.getElementById('profileModal');
const closeModal = document.getElementById('closeModal');

viewProfile.addEventListener('click', (e) => {
    e.preventDefault();
    profileModal.style.display = 'flex';
});

closeModal.addEventListener('click', () => {
    profileModal.style.display = 'none';
});

window.addEventListener('click', (e) => {
    if (e.target === profileModal) {
        profileModal.style.display = 'none';
    }
});
