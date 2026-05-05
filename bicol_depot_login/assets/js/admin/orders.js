document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('statusModal');
    if (!modal) return;

    const closeBtn = modal.querySelector('.close-modal');
    const cancelBtn = modal.querySelector('.cancel-modal');
    const statusForm = modal.querySelector('form');
    const statusSelect = document.getElementById('modalStatusSelect');
    const currentStatusBadge = document.getElementById('modalCurrentStatus');

    //Populate modal elements
    const elOrderId = document.getElementById('modalOrderId');
    const elProduct = document.getElementById('modalProductName');
    const elQty = document.getElementById('modalQuantity');
    const elTotal = document.getElementById('modalTotalPrice');

    function openModal(id, product, qty, total, status) {
        statusForm.querySelector('input[name="reservation_id"]').value = id;
        elOrderId.textContent = `#${id}`;
        elProduct.textContent = product;
        elQty.textContent = qty;
        elTotal.textContent = total;
        statusSelect.value = status;

        //Update badge styling dynamically
        currentStatusBadge.className = `status-badge status-${status}`;
        currentStatusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);

        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    //Event delegation for update buttons
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.update-status-btn');
        if (btn) {
            openModal(
                btn.dataset.id,
                btn.dataset.product,
                btn.dataset.quantity,
                btn.dataset.price,
                btn.dataset.status
            );
        }
    });

    //Close triggers
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    window.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
});