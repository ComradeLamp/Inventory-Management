document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('addProductModal');
    const openBtn = document.getElementById('openAddProductModal');
    const closeBtn = document.getElementById('closeAddProductModal');
    const cancelBtn = document.getElementById('cancelAddProduct');

    if (!modal) return;

    function openModal() {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restore scroll
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    //Close modal when clicking on the dark overlay (outside content)
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    //Close modal when pressing Escape
    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
});