//Handles: user search filter, view-messages modal, modal close behavior
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('user-search');
    const searchButton = document.querySelector('.search-button');
    const clearButton = document.getElementById('clear-search');

    if (searchButton) {
        searchButton.addEventListener('click', filterUsers);
    }

    //Trigger filter when Enter is pressed in the search input
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterUsers();
            }
        });

        //Show/hide clear button based on input content
        searchInput.addEventListener('input', toggleClearButton);
        toggleClearButton(); //Run once on load to set initial state
    }

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            filterUsers();
            toggleClearButton();
            searchInput.focus();
        });
    }
});

//Show the clear button only when the search input has text
function toggleClearButton() {
    const input = document.getElementById('user-search');
    const clearBtn = document.getElementById('clear-search');
    if (!input || !clearBtn) return;

    if (input.value.length > 0) {
        clearBtn.style.display = 'inline-flex';
    } else {
        clearBtn.style.display = 'none';
    }
}

//Filter table rows by username or email based on the search input value
function filterUsers() {
    const inputEl = document.getElementById('user-search');
    if (!inputEl) return;

    const input = inputEl.value.toLowerCase();
    const rows = document.querySelectorAll('.users-table tbody tr');

    rows.forEach(function (row) {
        //Skip the "no users found" row (it has only one cell)
        if (row.cells.length < 3) return;

        const username = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();

        if (username.includes(input) || email.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

//Open the messages modal and load this user's messages via AJAX
//Called from inline onclick attributes on each row's "view" button
function viewUserMessages(userId) {
    const modal = document.getElementById('messagesModal');
    const container = document.getElementById('userMessagesContainer');
    if (!modal || !container) return;

    modal.style.display = 'block';
    container.innerHTML = '<p class="modal-empty">Loading messages...</p>';

    fetch('get_user_messages.php?user_id=' + userId)
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data || data.length === 0) {
                container.innerHTML = '<p class="modal-empty">No messages found for this user.</p>';
                return;
            }

            let html = '';
            data.forEach(function (message) {
                html += ''
                    + '<div class="message-item">'
                    +   '<div class="message-meta">'
                    +     '<i class="fa-solid fa-clock"></i> ' + message.created_at
                    +   '</div>'
                    +   '<div class="message-content">' + message.content + '</div>'
                    + '</div>';
            });

            container.innerHTML = html;
        })
        .catch(function (error) {
            container.innerHTML = '<p class="modal-empty">Error loading messages. Please try again.</p>';
            console.error('Error:', error);
        });
}

//Close the messages modal
function closeModal() {
    const modal = document.getElementById('messagesModal');
    if (modal) modal.style.display = 'none';
}

//Close modal when clicking on the dark overlay (outside the content box)
window.addEventListener('click', function (event) {
    const modal = document.getElementById('messagesModal');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
});

//Close modal when pressing Escape
window.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});