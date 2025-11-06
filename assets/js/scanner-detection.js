/**
 * ====================================================================
 * GLOBAL BARCODE SCANNER DETECTION MODULE
 * ====================================================================
 * This script listens for input from a hardware barcode scanner anywhere on the page.
 * When a complete barcode is detected (a rapid sequence of characters followed by 'Enter'),
 * it dispatches a custom 'barcodeScanned' event on the document.
 *
 * Individual pages can then listen for this event to process the barcode.
 *
 * Usage:
 * document.addEventListener('barcodeScanned', (event) => {
 *     const barcode = event.detail.barcode;
 *     // Your custom logic here...
 * });
 */

document.addEventListener('DOMContentLoaded', () => {
    let scanBuffer = '';
    let lastKeyTime = 0;
    let scanTimeout = null;
    const SCAN_INTERVAL_MS = 100; // Max time between characters for a scan

    console.log("Initializing global barcode scanner detection...");

    document.addEventListener('keydown', (e) => {
        const currentTime = Date.now();
        const timeDiff = currentTime - lastKeyTime;
        lastKeyTime = currentTime;

        // Ignore modifier keys
        if (['Control', 'Alt', 'Shift', 'Meta'].includes(e.key)) {
            return;
        }

        // If input is too slow, reset the buffer.
        // This check happens before processing the current key.
        if (timeDiff > SCAN_INTERVAL_MS) {
            scanBuffer = '';
        }

        // If the 'Enter' key is pressed, a scan is complete.
        if (e.key === 'Enter') {
            // A valid barcode is typically more than a few characters long.
            if (scanBuffer.length > 3) {
                // Prevent the default 'Enter' action (like form submission)
                // if the input is identified as a scan.
                e.preventDefault();

                console.log('Barcode detected, calling global handler:', scanBuffer);

                // Call a global handler function if it exists.
                if (typeof window.handleBarcodeScan === 'function') {
                    window.handleBarcodeScan(scanBuffer);
                }
            }
            // Always reset the buffer after 'Enter'.
            scanBuffer = '';
        } else {
            // Add the character to the buffer if it's a single character.
            if (e.key.length === 1) {
                scanBuffer += e.key;
            }
        }

        // Fallback timeout to clear the buffer if 'Enter' is not received.
        clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => {
            if (scanBuffer.length > 0) {
                console.log('Scan buffer timed out, resetting.');
                scanBuffer = '';
            }
        }, SCAN_INTERVAL_MS + 50);
    });
});