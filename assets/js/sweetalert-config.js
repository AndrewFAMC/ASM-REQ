/**
 * SweetAlert2 Configuration and Helper Functions
 * HCC Asset Management System
 */

// Default SweetAlert2 configuration
const SwalConfig = {
    position: 'center',
    showConfirmButton: true,
    confirmButtonColor: '#2563eb', // Blue-600
    cancelButtonColor: '#dc2626', // Red-600
    timer: null,
    timerProgressBar: false,
    allowOutsideClick: true,
    allowEscapeKey: true,
    customClass: {
        popup: 'swal-responsive',
        confirmButton: 'swal-btn-confirm',
        cancelButton: 'swal-btn-cancel'
    }
};

// Success Alert
function showSuccessAlert(title, message, callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'success',
        title: title || 'Success!',
        text: message,
        confirmButtonText: 'OK',
        timer: 3000,
        timerProgressBar: true
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback(result);
        }
    });
}

// Error Alert
function showErrorAlert(title, message, callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'error',
        title: title || 'Error!',
        text: message,
        confirmButtonText: 'OK'
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback(result);
        }
    });
}

// Warning Alert
function showWarningAlert(title, message, callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: title || 'Warning!',
        text: message,
        confirmButtonText: 'OK'
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback(result);
        }
    });
}

// Info Alert
function showInfoAlert(title, message, callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'info',
        title: title || 'Information',
        text: message,
        confirmButtonText: 'OK'
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback(result);
        }
    });
}

// Confirmation Dialog
function showConfirmDialog(title, message, confirmText = 'Yes', cancelText = 'No', callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'question',
        title: title || 'Are you sure?',
        text: message,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback(result.isConfirmed);
        }
    });
}

// Delete Confirmation Dialog
function showDeleteConfirm(itemName, callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: 'Delete Confirmation',
        html: `Are you sure you want to delete <strong>${itemName}</strong>?<br><br>This action cannot be undone.`,
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        reverseButtons: true
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback(result.isConfirmed);
        }
    });
}

// Loading Alert
function showLoadingAlert(title = 'Processing...', message = 'Please wait') {
    Swal.fire({
        title: title,
        text: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Close Loading Alert
function closeLoadingAlert() {
    Swal.close();
}

// Toast Notification (small, non-intrusive)
function showToast(icon, title, position = 'top-end', timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: position,
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: icon,
        title: title
    });
}

// Form Validation Alert
function showValidationError(message) {
    showErrorAlert('Validation Error', message);
}

// Permission Denied Alert
function showPermissionDenied(message = 'You do not have permission to perform this action.') {
    showErrorAlert('Permission Denied', message);
}

// Session Expired Alert
function showSessionExpired(callback = null) {
    Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: 'Session Expired',
        text: 'Your session has expired. Please login again.',
        confirmButtonText: 'Login',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'login.php';
        }
        if (callback && typeof callback === 'function') {
            callback(result);
        }
    });
}

// Network Error Alert
function showNetworkError(message = 'Network error occurred. Please check your connection and try again.') {
    showErrorAlert('Network Error', message);
}

// Auto-dismiss Success Toast
function showSuccessToast(message) {
    showToast('success', message);
}

// Auto-dismiss Error Toast
function showErrorToast(message) {
    showToast('error', message);
}

// Auto-dismiss Info Toast
function showInfoToast(message) {
    showToast('info', message);
}

// Auto-dismiss Warning Toast
function showWarningToast(message) {
    showToast('warning', message);
}

// Custom HTML Alert
function showCustomAlert(options) {
    Swal.fire({
        ...SwalConfig,
        ...options
    });
}

// Responsive styles for mobile and tablet
const responsiveStyles = `
<style>
    @media (max-width: 768px) {
        .swal2-popup {
            width: 90% !important;
            padding: 1.5rem !important;
            font-size: 0.9rem !important;
        }
        .swal2-title {
            font-size: 1.5rem !important;
        }
        .swal2-html-container {
            font-size: 0.9rem !important;
        }
        .swal-btn-confirm, .swal-btn-cancel {
            padding: 0.6rem 1.2rem !important;
            font-size: 1rem !important;
            min-height: 44px !important;
        }
    }
    
    @media (min-width: 834px) and (max-width: 1366px) {
        /* iPad Pro optimizations */
        .swal2-popup {
            width: 80% !important;
            max-width: 600px !important;
            padding: 2rem !important;
        }
        .swal2-title {
            font-size: 2rem !important;
        }
        .swal2-html-container {
            font-size: 1.1rem !important;
        }
        .swal-btn-confirm, .swal-btn-cancel {
            padding: 1rem 2rem !important;
            font-size: 1.1rem !important;
            min-height: 48px !important;
            min-width: 120px !important;
        }
    }
    
    .swal-responsive {
        border-radius: 1rem !important;
    }
    
    .swal-btn-confirm, .swal-btn-cancel {
        border-radius: 0.5rem !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }
    
    .swal-btn-confirm:hover {
        transform: scale(1.05) !important;
    }
    
    .swal-btn-cancel:hover {
        transform: scale(1.05) !important;
    }
</style>
`;

// Inject responsive styles
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', responsiveStyles);
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showSuccessAlert,
        showErrorAlert,
        showWarningAlert,
        showInfoAlert,
        showConfirmDialog,
        showDeleteConfirm,
        showLoadingAlert,
        closeLoadingAlert,
        showToast,
        showValidationError,
        showPermissionDenied,
        showSessionExpired,
        showNetworkError,
        showSuccessToast,
        showErrorToast,
        showInfoToast,
        showWarningToast,
        showCustomAlert
    };
}
