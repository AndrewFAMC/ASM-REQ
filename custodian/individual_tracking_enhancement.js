/**
 * Individual Asset Tracking Enhancement
 * Adds unit selection capability to tag generation
 */

// Global variable to hold available units for selection
let availableUnits = [];
let selectedUnits = [];

// Store original function reference (will be set when page loads)
let originalOpenGenerateTagModal = null;

// Wait for the page to fully load before overriding
window.addEventListener('load', function() {
    // Store the original function
    originalOpenGenerateTagModal = window.openGenerateTagModal;

    // Override with enhanced version
    window.openGenerateTagModal = async function(office, asset) {
        // Call the original function first
        if (originalOpenGenerateTagModal) {
            await originalOpenGenerateTagModal(office, asset);
        }

        // Check if this asset has individual tracking enabled
        await checkAndLoadUnits(asset.id);
    };
});

async function checkAndLoadUnits(assetId) {
    try {
        const response = await fetch(`../api/asset_units.php?action=get_available_units&asset_id=${assetId}`);
        const data = await response.json();

        if (data.success && data.units && data.units.length > 0) {
            availableUnits = data.units;
            showUnitSelection();
        } else {
            // No units available, check if tracking is enabled
            const assetResponse = await fetch(`../api/asset_units.php?action=get_units_for_asset&asset_id=${assetId}`);
            const assetData = await assetResponse.json();

            if (assetData.success && assetData.asset.track_individually) {
                // Tracking enabled but no available units
                showNoUnitsMessage();
            } else {
                // Offer to enable individual tracking
                showEnableTrackingOption(assetId);
            }
        }
    } catch (error) {
        console.error('Error checking units:', error);
    }
}

function showUnitSelection() {
    const quantityInput = document.getElementById('gt_quantity');
    const quantityValue = parseInt(quantityInput.value) || 1;

    // Remove existing hidden input if any
    const existingHiddenInput = document.getElementById('selected_unit_ids');
    if (existingHiddenInput) {
        existingHiddenInput.remove();
    }

    // AUTO-SELECT first N units based on quantity (silent - no UI)
    autoSelectUnits(quantityValue);

    // Get the units that will be assigned
    const unitsToAssign = availableUnits.slice(0, quantityValue);

    // Add hidden input with selected unit IDs (no visible UI)
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.id = 'selected_unit_ids';
    hiddenInput.name = 'selected_unit_ids';
    hiddenInput.value = unitsToAssign.map(u => u.id).join(',');

    // Append to form
    const form = document.getElementById('generateTagForm');
    if (form) {
        form.appendChild(hiddenInput);
    }

    // Update when quantity changes
    quantityInput.addEventListener('change', function() {
        showUnitSelection(); // Refresh the hidden input
    });
}

// Auto-select first N units
function autoSelectUnits(n) {
    selectedUnits = availableUnits.slice(0, n).map(u => u.id);
}

function showEnableTrackingOption(assetId) {
    const modalContent = document.getElementById('generateTagModal');
    const quantityInput = document.getElementById('gt_quantity');
    const quantityDiv = quantityInput.closest('div');

    // Remove existing section if any
    const existingSection = document.getElementById('unit-selection-section');
    if (existingSection) {
        existingSection.remove();
    }

    const enableTrackingHTML = `
        <div id="unit-selection-section" class="col-span-full bg-yellow-50 border border-yellow-300 rounded-lg p-4 mt-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-yellow-600 text-2xl mt-1"></i>
                <div class="flex-1">
                    <h4 class="font-semibold text-yellow-900 mb-2">Enable Individual Unit Tracking?</h4>
                    <p class="text-sm text-yellow-800 mb-3">
                        This asset can be tracked individually. Each unit will have its own serial number and status tracking.
                    </p>
                    <button type="button" onclick="enableIndividualTracking(${assetId})"
                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold">
                        <i class="fas fa-toggle-on mr-2"></i>Enable Individual Tracking
                    </button>
                </div>
            </div>
        </div>
    `;

    const inventorySection = document.querySelector('#generateTagModal .bg-blue-50');
    if (inventorySection) {
        inventorySection.insertAdjacentHTML('afterend', enableTrackingHTML);
    }
}

function showNoUnitsMessage() {
    const existingSection = document.getElementById('unit-selection-section');
    if (existingSection) {
        existingSection.remove();
    }

    const noUnitsHTML = `
        <div id="unit-selection-section" class="col-span-full bg-red-50 border border-red-300 rounded-lg p-4 mt-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl mt-1"></i>
                <div class="flex-1">
                    <h4 class="font-semibold text-red-900 mb-2">No Available Units</h4>
                    <p class="text-sm text-red-800">
                        Individual tracking is enabled, but all units are already assigned. Please add more units or return existing ones.
                    </p>
                </div>
            </div>
        </div>
    `;

    const inventorySection = document.querySelector('#generateTagModal .bg-blue-50');
    if (inventorySection) {
        inventorySection.insertAdjacentHTML('afterend', noUnitsHTML);
    }
}

// Manual selection functions removed - now fully automatic

window.enableIndividualTracking = async function(assetId) {
    const result = await Swal.fire({
        title: 'Enable Individual Tracking',
        html: `
            <div class="text-left space-y-4">
                <p>This will enable detailed tracking for each unit of this asset.</p>
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="auto-create-units" checked>
                    <span>Automatically create unit records for current quantity</span>
                </label>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Enable Tracking',
        confirmButtonColor: '#2563eb'
    });

    if (result.isConfirmed) {
        const autoCreate = document.getElementById('auto-create-units').checked;

        try {
            const formData = new FormData();
            formData.append('action', 'enable_tracking');
            formData.append('asset_id', assetId);
            formData.append('auto_create', autoCreate ? '1' : '0');

            const response = await fetch('../api/asset_units.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire('Success!', data.message, 'success');
                // Reload the modal
                await checkAndLoadUnits(assetId);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire('Error!', error.message, 'error');
        }
    }
};

// Enhance the form submission to include selected units
const originalFormSubmit = document.getElementById('generateTagForm');
if (originalFormSubmit) {
    originalFormSubmit.addEventListener('submit', function(e) {
        // Add selected unit IDs to form data if they exist
        const selectedUnitsInput = document.getElementById('selected_unit_ids');
        if (selectedUnitsInput && selectedUnitsInput.value) {
            console.log('Submitting with units:', selectedUnitsInput.value);
        }
    });
}

console.log('Individual Tracking Enhancement loaded successfully');
