<?php
/**
 * Asset Lookup Page
 * Public page for viewing asset details via QR code scan
 */

require_once 'config.php';

// Get tag number from URL
$tagNumber = $_GET['tag'] ?? '';

if (empty($tagNumber)) {
    die('Invalid tag number');
}

// Fetch asset details by tag number
try {
    $stmt = $pdo->prepare("
        SELECT
            it.*,
            a.id as asset_id,
            a.asset_name,
            a.barcode,
            a.serial_number,
            a.status as asset_status,
            a.description,
            a.purchase_date,
            a.supplier,
            o.office_name,
            o.floor,
            o.section_code,
            cam.campus_name,
            c.category_name,
            br.name as brand_name
        FROM inventory_tags it
        JOIN assets a ON it.asset_id = a.id
        LEFT JOIN offices o ON it.office_id = o.id
        LEFT JOIN campuses cam ON a.campus_id = cam.id
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN brands br ON a.brand_id = br.id
        WHERE it.tag_number = ?
        LIMIT 1
    ");

    $stmt->execute([$tagNumber]);
    $tag = $stmt->fetch();

    if (!$tag) {
        die('Tag not found');
    }

} catch (Exception $e) {
    error_log("Error in asset lookup: " . $e->getMessage());
    die('Error loading asset details');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Lookup - <?= htmlspecialchars($tag['tag_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="p-4">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-t-xl shadow-lg p-6 text-center">
            <img src="logo/1.png" alt="HCC Logo" class="h-16 w-16 mx-auto mb-3">
            <h1 class="text-2xl font-bold text-gray-800">Asset Information</h1>
            <p class="text-gray-600 text-sm mt-1">Holycross College of Calinan</p>
        </div>

        <!-- Tag Number -->
        <div class="bg-blue-600 text-white p-4 text-center">
            <p class="text-sm uppercase tracking-wide">Inventory Tag Number</p>
            <p class="text-2xl font-bold mt-1"><?= htmlspecialchars($tag['tag_number']) ?></p>
        </div>

        <!-- Asset Details -->
        <div class="bg-white shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-box text-blue-600 mr-2"></i>Asset Details
            </h2>

            <div class="space-y-3">
                <!-- Asset Name -->
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Asset Name:</span>
                    <span class="text-gray-900 font-semibold text-right"><?= htmlspecialchars($tag['asset_name']) ?></span>
                </div>

                <!-- Category -->
                <?php if ($tag['category_name']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Category:</span>
                    <span class="text-gray-900 text-right"><?= htmlspecialchars($tag['category_name']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Brand -->
                <?php if ($tag['brand_name']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Brand:</span>
                    <span class="text-gray-900 text-right"><?= htmlspecialchars($tag['brand_name']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Serial Number -->
                <?php if ($tag['serial_number']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Serial Number:</span>
                    <span class="text-gray-900 text-right font-mono"><?= htmlspecialchars($tag['serial_number']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Status -->
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Status:</span>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $tag['asset_status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                        <?= htmlspecialchars($tag['asset_status']) ?>
                    </span>
                </div>

                <!-- Quantity -->
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Quantity:</span>
                    <span class="text-gray-900 font-semibold"><?= htmlspecialchars($tag['quantity'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- Location Details -->
        <div class="bg-white shadow-lg p-6 mt-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>Location
            </h2>

            <div class="space-y-3">
                <!-- Office -->
                <?php if ($tag['office_name']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Office:</span>
                    <span class="text-gray-900 font-semibold text-right"><?= htmlspecialchars($tag['office_name']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Floor & Section -->
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Floor:</span>
                    <span class="text-gray-900"><?= htmlspecialchars($tag['floor'] ?? $tag['location_floor'] ?? 'N/A') ?></span>
                </div>

                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Section:</span>
                    <span class="text-gray-900"><?= htmlspecialchars($tag['section_code'] ?? $tag['location_section'] ?? 'N/A') ?></span>
                </div>

                <!-- Campus -->
                <?php if ($tag['campus_name']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Campus:</span>
                    <span class="text-gray-900 text-right"><?= htmlspecialchars($tag['campus_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="bg-white shadow-lg p-6 mt-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Additional Information
            </h2>

            <div class="space-y-3">
                <!-- Inventory Date -->
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Inventory Date:</span>
                    <span class="text-gray-900"><?= htmlspecialchars(date('F d, Y', strtotime($tag['inventory_date']))) ?></span>
                </div>

                <!-- Counted By -->
                <?php if ($tag['counted_by']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Counted By:</span>
                    <span class="text-gray-900 text-right"><?= htmlspecialchars($tag['counted_by']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Checked By -->
                <?php if ($tag['checked_by']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Checked By:</span>
                    <span class="text-gray-900 text-right"><?= htmlspecialchars($tag['checked_by']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Supplier -->
                <?php if ($tag['supplier']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Supplier:</span>
                    <span class="text-gray-900 text-right"><?= htmlspecialchars($tag['supplier']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Unit Price -->
                <?php if ($tag['unit_price']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Unit Price:</span>
                    <span class="text-gray-900 font-semibold">₱<?= number_format($tag['unit_price'], 2) ?></span>
                </div>
                <?php endif; ?>

                <!-- Total Value -->
                <?php if ($tag['total_value']): ?>
                <div class="flex justify-between items-start border-b pb-2">
                    <span class="text-gray-600 font-medium">Total Value:</span>
                    <span class="text-green-600 font-bold text-lg">₱<?= number_format($tag['total_value'], 2) ?></span>
                </div>
                <?php endif; ?>

                <!-- Remarks -->
                <?php if ($tag['remarks']): ?>
                <div class="border-b pb-2">
                    <span class="text-gray-600 font-medium block mb-1">Remarks:</span>
                    <span class="text-gray-900"><?= nl2br(htmlspecialchars($tag['remarks'])) ?></span>
                </div>
                <?php endif; ?>

                <!-- Last Updated -->
                <div class="flex justify-between items-start">
                    <span class="text-gray-600 font-medium">Last Updated:</span>
                    <span class="text-gray-500 text-sm"><?= htmlspecialchars(date('F d, Y g:i A', strtotime($tag['updated_at']))) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-white rounded-b-xl shadow-lg p-6 mt-4 text-center">
            <p class="text-gray-600 text-sm">
                <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
                This asset is property of Holycross College of Calinan
            </p>
            <p class="text-gray-500 text-xs mt-2">
                For inquiries, please contact the Asset Management Office
            </p>
        </div>

        <!-- Back Button -->
        <div class="mt-6 text-center">
            <button onclick="window.history.back()" class="bg-white text-gray-700 px-6 py-3 rounded-lg shadow-lg font-semibold hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left mr-2"></i>Go Back
            </button>
        </div>
    </div>
</body>
</html>
