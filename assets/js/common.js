/**
 * Common JavaScript Functions
 * Shared utilities across the AMS application
 */

// Barcode Scanner Helper Functions
const BarcodeHelper = {
    /**
     * Initialize barcode scanner on a specific input field
     * @param {string} inputId - ID of the input field
     * @param {function} callback - Callback function when barcode is scanned
     */
    initializeInput: function(inputId, callback) {
        const input = document.getElementById(inputId);
        if (!input) {
            console.error(`Input field with ID '${inputId}' not found`);
            return;
        }

        // Listen for barcode scanned event
        document.addEventListener('barcodeScanned', (e) => {
            input.value = e.detail.barcode;
            if (typeof callback === 'function') {
                callback(e.detail.barcode, e.detail.connectionType);
            }
        });

        // Also handle manual enter key press
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = input.value.trim();
                if (barcode && typeof callback === 'function') {
                    callback(barcode, 'manual');
                }
            }
        });
    },

    /**
     * Check if scanner is connected
     * @returns {boolean}
     */
    isConnected: function() {
        if (typeof barcodeScannerManager !== 'undefined') {
            return barcodeScannerManager.getStatus().connected;
        }
        return false;
    },

    /**
     * Show scanner connection modal
     */
    showConnectionModal: function() {
        if (typeof barcodeScannerManager !== 'undefined') {
            barcodeScannerManager.checkConnection();
        } else {
            console.error('Barcode scanner manager not initialized');
        }
    },

    /**
     * Get scanner status
     * @returns {object|null}
     */
    getStatus: function() {
        if (typeof barcodeScannerManager !== 'undefined') {
            return barcodeScannerManager.getStatus();
        }
        return null;
    }
};

// Form Validation Helpers
const FormHelper = {
    /**
     * Validate required fields
     * @param {string} formId - ID of the form
     * @returns {boolean}
     */
    validateRequired: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });

        return isValid;
    },

    /**
     * Clear form fields
     * @param {string} formId - ID of the form
     */
    clearForm: function(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            // Remove validation classes
            form.querySelectorAll('.border-red-500').forEach(field => {
                field.classList.remove('border-red-500');
            });
        }
    },

    /**
     * Serialize form data to object
     * @param {string} formId - ID of the form
     * @returns {object}
     */
    serializeForm: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return {};

        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }
};

// Alert/Notification Helpers
const AlertHelper = {
    /**
     * Show success message
     * @param {string} title
     * @param {string} message
     */
    success: function(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: title,
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(`${title}: ${message}`);
        }
    },

    /**
     * Show error message
     * @param {string} title
     * @param {string} message
     */
    error: function(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: title,
                text: message
            });
        } else {
            alert(`${title}: ${message}`);
        }
    },

    /**
     * Show warning message
     * @param {string} title
     * @param {string} message
     */
    warning: function(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: title,
                text: message
            });
        } else {
            alert(`${title}: ${message}`);
        }
    },

    /**
     * Show confirmation dialog
     * @param {string} title
     * @param {string} message
     * @param {function} onConfirm
     */
    confirm: function(title, message, onConfirm) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed && typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        } else {
            if (confirm(`${title}\n${message}`)) {
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            }
        }
    },

    /**
     * Show toast notification
     * @param {string} message
     * @param {string} type - success, error, warning, info
     */
    toast: function(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(message);
        }
    }
};

// AJAX Helper
const AjaxHelper = {
    /**
     * Make POST request
     * @param {string} url
     * @param {object} data
     * @returns {Promise}
     */
    post: async function(url, data) {
        try {
            const formData = new FormData();
            Object.entries(data).forEach(([key, value]) => {
                formData.append(key, value);
            });

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AJAX request failed:', error);
            throw error;
        }
    },

    /**
     * Make GET request
     * @param {string} url
     * @returns {Promise}
     */
    get: async function(url) {
        try {
            const response = await fetch(url, {
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AJAX request failed:', error);
            throw error;
        }
    }
};

// Utility Functions
const Utils = {
    /**
     * Format date to readable string
     * @param {string|Date} date
     * @returns {string}
     */
    formatDate: function(date) {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    /**
     * Format currency
     * @param {number} amount
     * @returns {string}
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    },

    /**
     * Debounce function
     * @param {function} func
     * @param {number} wait
     * @returns {function}
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Copy text to clipboard
     * @param {string} text
     */
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                AlertHelper.toast('Copied to clipboard', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            AlertHelper.toast('Copied to clipboard', 'success');
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        BarcodeHelper,
        FormHelper,
        AlertHelper,
        AjaxHelper,
        Utils
    };
}
