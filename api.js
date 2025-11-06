/**
 * Centralized API Request Function
 *
 * @param {string} url The endpoint URL to send the request to.
 * @param {string} action The specific action to be performed by the backend.
 * @param {object} data An object containing additional data to send.
 * @returns {Promise<object>} A promise that resolves with the JSON response from the server.
 */
async function apiRequest(url, action, data = {}) {
    // These constants are expected to be defined in the global scope of the page using this function.
    const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const campusId = window.campusId;

    const formData = new FormData();
    formData.append('action', action);
    if (csrfToken) formData.append('csrf_token', csrfToken);
    if (campusId) formData.append('campus_id', campusId);

    for (const key in data) {
        formData.append(key, data[key]);
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(`Server returned non-JSON response: ${text.substring(0, 200)}...`);
        }

        const result = await response.json();

        if (result && result.session_expired) {
            Swal.fire('Session Expired', 'Please refresh the page to log in again.', 'warning')
                .then(() => window.location.reload());
        }
        return result;
    } catch (error) {
        console.error('API Request Error:', error);
        Swal.fire('Error', `A network error occurred: ${error.message}. Please try again.`, 'error');
        return { success: false, message: 'Network error' };
    }
}