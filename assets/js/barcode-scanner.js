/**
 * Barcode Scanner Integration Module
 * Detects USB and Bluetooth barcode scanner connections
 * Displays connection status via modal
 */

class BarcodeScannerManager {
    constructor() {
        this.scannerConnected = false;
        this.connectionType = null; // 'usb', 'bluetooth', or null
        this.scanBuffer = '';
        this.scanTimeout = null;
        this.lastInputTime = 0;
        this.scannerDevices = [];
        this.bluetoothDevice = null;
        this.usbDevice = null;
        
        // Configuration
        this.config = {
            scanTimeThreshold: 100, // ms - time between keystrokes to detect scanner
            minBarcodeLength: 4,
            maxBarcodeLength: 50,
            scanEndChar: 'Enter', // Most scanners send Enter after scan
            checkInterval: 5000 // Check connection status every 5 seconds
        };

        this.init();
    }

    init() {
        console.log('Initializing Barcode Scanner Manager...');
        this.createModal();
        this.setupKeyboardListener();
        this.checkUSBDevices();
        this.setupPeriodicCheck();
        this.setupBluetoothButton();
        
        // Check for Web USB API support
        if ('usb' in navigator) {
            this.setupUSBListener();
        }

        // Check for Web Bluetooth API support
        if ('bluetooth' in navigator) {
            console.log('Web Bluetooth API is supported');
        }
    }

    /**
     * Create the connection status modal - Using SweetAlert2
     */
    createModal() {
        // No need to create HTML modal - using SweetAlert2
        console.log('Scanner modal will use SweetAlert2');
    }

    /**
     * Bind modal event listeners - Not needed with SweetAlert2
     */
    bindModalEvents() {
        // Not needed - SweetAlert2 handles its own events
    }

    /**
     * Setup keyboard listener to detect scanner input
     */
    setupKeyboardListener() {
        document.addEventListener('keydown', (e) => {
            const currentTime = Date.now();
            const timeDiff = currentTime - this.lastInputTime;

            // Detect rapid input (typical of barcode scanners)
            if (timeDiff < this.config.scanTimeThreshold) {
                if (e.key === this.config.scanEndChar) {
                    this.processScan(this.scanBuffer);
                    this.scanBuffer = '';
                } else if (e.key.length === 1) {
                    this.scanBuffer += e.key;
                }
            } else {
                // Reset buffer if too much time has passed
                this.scanBuffer = e.key.length === 1 ? e.key : '';
            }

            this.lastInputTime = currentTime;

            // Clear buffer after timeout
            clearTimeout(this.scanTimeout);
            this.scanTimeout = setTimeout(() => {
                this.scanBuffer = '';
            }, this.config.scanTimeThreshold * 2);
        });
    }

    /**
     * Process scanned barcode
     */
    processScan(barcode) {
        if (barcode.length >= this.config.minBarcodeLength && 
            barcode.length <= this.config.maxBarcodeLength) {
            
            console.log('Barcode scanned:', barcode);
            
            // Mark scanner as connected if we receive valid input
            if (!this.scannerConnected) {
                this.scannerConnected = true;
                this.connectionType = this.connectionType || 'usb'; // Default to USB if not set
                this.showConnectionStatus(true);
            }

            // Dispatch custom event for other parts of the application
            const event = new CustomEvent('barcodeScanned', {
                detail: { barcode: barcode, connectionType: this.connectionType }
            });
            document.dispatchEvent(event);
        }
    }

    /**
     * Setup USB device listener
     */
    setupUSBListener() {
        if (!('usb' in navigator)) {
            console.log('Web USB API not supported');
            return;
        }

        // Listen for USB device connection
        navigator.usb.addEventListener('connect', (event) => {
            console.log('USB device connected:', event.device);
            this.handleUSBConnection(event.device);
        });

        // Listen for USB device disconnection
        navigator.usb.addEventListener('disconnect', (event) => {
            console.log('USB device disconnected:', event.device);
            this.handleUSBDisconnection(event.device);
        });
    }

    /**
     * Check for USB devices
     */
    async checkUSBDevices() {
        if (!('usb' in navigator)) {
            console.log('Web USB API not supported - will use keyboard detection');
            return;
        }

        try {
            const devices = await navigator.usb.getDevices();
            console.log('USB devices found:', devices.length);
            
            if (devices.length > 0) {
                // Check if any device looks like a barcode scanner
                // Most USB barcode scanners identify as HID devices
                const scannerDevice = devices.find(device => 
                    device.productName && 
                    (device.productName.toLowerCase().includes('scanner') ||
                     device.productName.toLowerCase().includes('barcode') ||
                     device.productName.toLowerCase().includes('reader'))
                );

                if (scannerDevice) {
                    this.usbDevice = scannerDevice;
                    this.scannerConnected = true;
                    this.connectionType = 'usb';
                    console.log('Barcode scanner detected:', scannerDevice.productName);
                    // Show connection status
                    this.showConnectionStatus(true);
                }
            }
        } catch (error) {
            console.error('Error checking USB devices:', error);
        }
    }

    /**
     * Handle USB device connection
     */
    handleUSBConnection(device) {
        // Check if it's a potential scanner device
        if (device.productName && 
            (device.productName.toLowerCase().includes('scanner') ||
             device.productName.toLowerCase().includes('barcode') ||
             device.productName.toLowerCase().includes('reader'))) {
            
            this.usbDevice = device;
            this.scannerConnected = true;
            this.connectionType = 'usb';
            this.showConnectionStatus(true);
        }
    }

    /**
     * Handle USB device disconnection
     */
    handleUSBDisconnection(device) {
        if (this.usbDevice && this.usbDevice.serialNumber === device.serialNumber) {
            this.scannerConnected = false;
            this.connectionType = null;
            this.usbDevice = null;
            this.showConnectionStatus(false);
        }
    }

    /**
     * Setup Bluetooth connection button
     */
    setupBluetoothButton() {
        // Floating button removed - scanner check can be accessed through other means
    }

    /**
     * Connect to Bluetooth scanner
     */
    async connectBluetooth() {
        if (!('bluetooth' in navigator)) {
            this.showError('Bluetooth not supported', 'Your browser does not support Web Bluetooth API');
            return;
        }

        try {
            console.log('Requesting Bluetooth device...');
            
            const device = await navigator.bluetooth.requestDevice({
                filters: [
                    { services: ['battery_service'] },
                    { name: 'Scanner' }
                ],
                optionalServices: ['battery_service', 'device_information']
            });

            console.log('Bluetooth device selected:', device.name);
            this.bluetoothDevice = device;
            this.scannerConnected = true;
            this.connectionType = 'bluetooth';
            
            // Listen for disconnection
            device.addEventListener('gattserverdisconnected', () => {
                console.log('Bluetooth device disconnected');
                this.scannerConnected = false;
                this.connectionType = null;
                this.bluetoothDevice = null;
                this.showConnectionStatus(false);
            });

            this.showConnectionStatus(true);
        } catch (error) {
            console.error('Bluetooth connection error:', error);
            this.showError('Connection Failed', error.message);
        }
    }

    /**
     * Connect to USB scanner
     */
    async connectUSB() {
        if (!('usb' in navigator)) {
            this.showError('USB not supported', 'Your browser does not support Web USB API');
            return;
        }

        try {
            console.log('Requesting USB device...');
            
            const device = await navigator.usb.requestDevice({
                filters: [
                    { classCode: 0x03 } // HID class
                ]
            });

            console.log('USB device selected:', device.productName);
            this.usbDevice = device;
            this.scannerConnected = true;
            this.connectionType = 'usb';
            
            this.showConnectionStatus(true);
        } catch (error) {
            console.error('USB connection error:', error);
            this.showError('Connection Failed', error.message);
        }
    }

    /**
     * Check current connection status
     */
    async checkConnection() {
        console.log('Checking scanner connection...');
        
        // Check USB devices
        await this.checkUSBDevices();
        
        // Check Bluetooth
        if (this.bluetoothDevice && this.bluetoothDevice.gatt.connected) {
            this.scannerConnected = true;
            this.connectionType = 'bluetooth';
        }

        // Show status
        this.showConnectionStatus(this.scannerConnected);
    }

    /**
     * Setup periodic connection check
     */
    setupPeriodicCheck() {
        setInterval(() => {
            if (this.scannerConnected) {
                // Verify connection is still active
                if (this.connectionType === 'bluetooth' && this.bluetoothDevice) {
                    if (!this.bluetoothDevice.gatt.connected) {
                        this.scannerConnected = false;
                        this.connectionType = null;
                    }
                }
            }
        }, this.config.checkInterval);
    }

    /**
     * Show connection status modal - Using SweetAlert2
     */
    showConnectionStatus(connected) {
        // Use SweetAlert2 for notifications (matching Staff Dashboard)
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 not loaded');
            return;
        }

        if (connected) {
            // Scanner Connected
            Swal.fire({
                title: 'Scanner Connected!',
                html: `
                    <div class="text-center">
                        <div class="mb-4">
                            <svg class="w-20 h-20 mx-auto text-green-500"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold mb-2">${this.connectionType ? this.connectionType.toUpperCase() : 'USB'} Barcode Scanner</p>
                        <p class="text-gray-600">
                            Your scanner is ready to use. Navigate to the Scanner page and scan any barcode to search for assets.
                        </p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981',
                timer: 5000,
                timerProgressBar: true
            });
        } else {
            // Scanner Disconnected
            Swal.fire({
                title: 'Scanner Disconnected',
                html: `
                    <div class="text-center">
                        <div class="mb-4">
                            <svg class="w-20 h-20 mx-auto text-red-500"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold mb-2">USB Barcode Scanner</p>
                        <p class="text-gray-600">
                            Scanner has been disconnected.
                        </p>
                    </div>
                `,
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#f59e0b'
            });
        }
    }

    /**
     * Get device name
     */
    getDeviceName() {
        if (this.connectionType === 'usb' && this.usbDevice) {
            return this.usbDevice.productName || 'USB Scanner';
        } else if (this.connectionType === 'bluetooth' && this.bluetoothDevice) {
            return this.bluetoothDevice.name || 'Bluetooth Scanner';
        }
        return 'Unknown Device';
    }

    /**
     * Show error message
     */
    showError(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: title,
                text: message
            });
        } else {
            alert(`${title}: ${message}`);
        }
    }

    /**
     * Hide modal
     */
    hideModal() {
        const modal = document.getElementById('scannerStatusModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Get scanner status
     */
    getStatus() {
        return {
            connected: this.scannerConnected,
            connectionType: this.connectionType,
            deviceName: this.getDeviceName()
        };
    }
}

// Initialize the barcode scanner manager when DOM is ready
let barcodeScannerManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        barcodeScannerManager = new BarcodeScannerManager();
    });
} else {
    barcodeScannerManager = new BarcodeScannerManager();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BarcodeScannerManager;
}
