// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all interactive elements
    initializeSearchFunctionality();
    initializeFormValidation();
    initializeDeleteConfirmations();
    initializeImagePreviews();
    initializeDropdowns();
});

// Search functionality
function initializeSearchFunctionality() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.trim();
            const searchResults = document.querySelector('.search-results');
            if (searchTerm.length > 2 && searchResults) {
                // Update search results
                updateSearchResults(searchTerm);
            }
        }, 300));
    }
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

// Delete confirmations
function initializeDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = this.href;
            }
        });
    });
}

// Image preview functionality
function initializeImagePreviews() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept^="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const preview = document.querySelector(`#${this.dataset.preview}`);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

// Dropdown functionality
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function() {
            const menu = this.nextElementSibling;
            menu.classList.toggle('hidden');
        });
    });
}

// Form validation helper
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            showError(field, 'This field is required');
        } else {
            clearError(field);
        }

        // Email validation
        if (field.type === 'email' && field.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value.trim())) {
                isValid = false;
                showError(field, 'Please enter a valid email address');
            }
        }

        // ISBN validation
        if (field.name === 'isbn' && field.value.trim()) {
            const isbnRegex = /^(?:\d{10}|\d{13})$/;
            if (!isbnRegex.test(field.value.trim())) {
                isValid = false;
                showError(field, 'Please enter a valid 10 or 13 digit ISBN');
            }
        }
    });

    return isValid;
}

// Show error message
function showError(field, message) {
    clearError(field);
    field.classList.add('border-red-500');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

// Clear error message
function clearError(field) {
    field.classList.remove('border-red-500');
    const errorDiv = field.parentNode.querySelector('.text-red-500');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Debounce helper function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Update search results
async function updateSearchResults(searchTerm) {
    try {
        const response = await fetch(`search.php?term=${encodeURIComponent(searchTerm)}`);
        const data = await response.json();
        displaySearchResults(data);
    } catch (error) {
        console.error('Error fetching search results:', error);
    }
}

// Display search results
function displaySearchResults(results) {
    const searchResults = document.querySelector('.search-results');
    if (!searchResults) return;

    searchResults.innerHTML = '';
    if (results.length === 0) {
        searchResults.innerHTML = '<div class="p-4 text-gray-500">No results found</div>';
        return;
    }

    results.forEach(result => {
        const div = document.createElement('div');
        div.className = 'p-2 hover:bg-gray-100 cursor-pointer';
        div.innerHTML = `
            <div class="flex items-center">
                <img src="${result.cover_image || 'default-cover.jpg'}" 
                     alt="${result.title}" 
                     class="w-12 h-16 object-cover rounded mr-3">
                <div>
                    <div class="font-medium">${result.title}</div>
                    <div class="text-sm text-gray-600">${result.author}</div>
                </div>
            </div>
        `;
        div.addEventListener('click', () => {
            window.location.href = `book_detail.php?id=${result.id}`;
        });
        searchResults.appendChild(div);
    });
}

// Handle modal functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Handle borrowing functionality
async function borrowBook(bookId) {
    try {
        const response = await fetch('borrow_book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ book_id: bookId })
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('Book borrowed successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to borrow book', 'error');
        }
    } catch (error) {
        console.error('Error borrowing book:', error);
        showAlert('An error occurred while borrowing the book', 'error');
    }
}

// Handle return functionality
async function returnBook(borrowId) {
    try {
        const response = await fetch('return_book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ borrow_id: borrowId })
        });
        const data = await response.json();
        
        if (data.success) {
            showAlert('Book returned successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to return book', 'error');
        }
    } catch (error) {
        console.error('Error returning book:', error);
        showAlert('An error occurred while returning the book', 'error');
    }
}

// Show alert message
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white shadow-lg transition-opacity duration-500`;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 500);
    }, 3000);
}
