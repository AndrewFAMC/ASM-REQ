# Notification Center Integration Guide

This guide shows how to integrate the notification center bell icon into your existing dashboards and pages.

## Prerequisites

1. Database migration completed (notifications table created)
2. Notification API endpoint active (`/api/notifications.php`)
3. Config.php updated with notification helper functions

## Quick Integration

### For Dashboard Pages

Add this to your `<head>` section if not already present:

```php
<meta name="csrf-token" content="<?= generateCSRFToken() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
```

Add the notification center to your header navigation:

```php
<header class="bg-white shadow-sm border-b">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard Title</h1>

            <!-- Right side with notification bell and user menu -->
            <div class="flex items-center space-x-4">
                <!-- Include notification center -->
                <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                <!-- User dropdown or other items -->
                <div class="flex items-center">
                    <span class="text-sm text-gray-700"><?= htmlspecialchars($user['full_name']) ?></span>
                </div>
            </div>
        </div>
    </div>
</header>
```

## Example Integration for Different Dashboards

### Admin Dashboard (admin/admin_dashboard_view.php)

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header with Notification Bell -->
            <header class="bg-white shadow-sm border-b">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>

                    <div class="flex items-center space-x-4">
                        <!-- Notification Center -->
                        <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                        <!-- User Info -->
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-700"><?= htmlspecialchars($user['full_name']) ?></span>
                            <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Your dashboard content here -->
            </main>
        </div>
    </div>
</body>
</html>
```

### Staff Dashboard

```php
<header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">My Assets</h1>

            <div class="flex items-center space-x-4">
                <!-- Notification Bell -->
                <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                <!-- User Menu -->
                <span class="text-sm"><?= htmlspecialchars($user['full_name']) ?></span>
            </div>
        </div>
    </div>
</header>
```

### Custodian Dashboard

```php
<header class="bg-white border-b">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Custodian Dashboard</h1>
                <p class="text-sm text-gray-600">Campus: <?= htmlspecialchars($user['campus_name']) ?></p>
            </div>

            <div class="flex items-center space-x-6">
                <!-- Notification Bell -->
                <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                <!-- Quick Actions -->
                <button class="btn-primary">Add Asset</button>
            </div>
        </div>
    </div>
</header>
```

## Creating Notifications Programmatically

### When Creating/Updating Borrowings

```php
// When a borrowing is approved
$borrowingId = 123;
$userId = 45;
$assetName = "Laptop - Dell XPS 15";
$dueDate = "2025-11-15";

createNotification(
    $pdo,
    $userId,
    NOTIFICATION_RETURN_REMINDER,
    "Asset Borrowing Approved",
    "Your request to borrow {$assetName} has been approved. Please return it by {$dueDate}.",
    [
        'related_type' => 'borrowing',
        'related_id' => $borrowingId,
        'priority' => 'medium',
        'action_url' => '/AMS-REQ/staff/borrowings.php?id=' . $borrowingId
    ]
);
```

### When Approval is Required

```php
// Notify custodian when new request is submitted
$requestId = 456;
$custodianId = 10;
$requesterName = "John Doe";
$assetName = "Projector - Epson";

createNotification(
    $pdo,
    $custodianId,
    NOTIFICATION_APPROVAL_REQUEST,
    "New Asset Request Pending",
    "{$requesterName} has requested to borrow {$assetName}. Please review and approve.",
    [
        'related_type' => 'request',
        'related_id' => $requestId,
        'priority' => 'high',
        'action_url' => '/AMS-REQ/custodian/requests.php?id=' . $requestId
    ]
);
```

### When Asset is Overdue

```php
// Notify user about overdue asset
$borrowingId = 789;
$userId = 45;
$assetName = "Camera - Canon EOS";
$daysOverdue = 5;

createNotification(
    $pdo,
    $userId,
    NOTIFICATION_OVERDUE_ALERT,
    "Overdue Asset Alert!",
    "Your borrowed {$assetName} is {$daysOverdue} days overdue. Please return it immediately.",
    [
        'related_type' => 'borrowing',
        'related_id' => $borrowingId,
        'priority' => 'urgent',
        'action_url' => '/AMS-REQ/staff/borrowings.php?id=' . $borrowingId,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ]
);
```

### When Asset Goes Missing

```php
// Notify admin about missing asset
$assetId = 234;
$adminId = 1;
$assetName = "Tablet - iPad Pro";
$borrowerName = "Jane Smith";

createNotification(
    $pdo,
    $adminId,
    NOTIFICATION_MISSING_REPORT,
    "Asset Marked as Missing",
    "{$assetName} borrowed by {$borrowerName} has been marked as missing after 60 days overdue.",
    [
        'related_type' => 'asset',
        'related_id' => $assetId,
        'priority' => 'urgent',
        'action_url' => '/AMS-REQ/admin/assets.php?id=' . $assetId
    ]
);
```

## Notification Types Available

The system supports these notification types (defined in config.php):

1. **NOTIFICATION_RETURN_REMINDER** - Reminders before due date
2. **NOTIFICATION_OVERDUE_ALERT** - Alerts for overdue items
3. **NOTIFICATION_APPROVAL_REQUEST** - Requests needing approval
4. **NOTIFICATION_APPROVAL_RESPONSE** - Approval decision notifications
5. **NOTIFICATION_MISSING_REPORT** - Missing asset notifications
6. **NOTIFICATION_SYSTEM_ALERT** - General system notifications

## Priority Levels

Set priority based on urgency:

- **urgent** - Red badge, requires immediate attention
- **high** - Orange badge, important but not critical
- **medium** - Blue badge, normal priority (default)
- **low** - Gray badge, informational

## Styling Customization

The notification center uses Tailwind CSS classes. To customize colors or styling, edit:

- [includes/notification_center.php](../includes/notification_center.php) - Main component styles
- Modify CSS variables in the `<style>` section

## Testing the Notification Center

### Create Test Notification

```php
// Add to any page temporarily for testing
if (isLoggedIn()) {
    createNotification(
        $pdo,
        $_SESSION['user_id'],
        NOTIFICATION_SYSTEM_ALERT,
        "Test Notification",
        "This is a test notification to verify the system is working correctly.",
        [
            'priority' => 'medium'
        ]
    );
}
```

### Check if Notifications Work

1. Log in to any dashboard
2. Look for the bell icon in the header
3. Click the bell icon
4. You should see any unread notifications
5. Click on a notification to mark it as read

## Troubleshooting

### Bell icon not showing
- Ensure Font Awesome is loaded
- Check if `notification_center.php` is included
- Verify user is logged in (session active)

### No notifications appearing
- Check if notifications exist in database: `SELECT * FROM notifications WHERE user_id = YOUR_USER_ID`
- Verify API endpoint is accessible: Visit `/AMS-REQ/api/notifications.php?action=get_count`
- Check browser console for JavaScript errors

### Badge count not updating
- Ensure CSRF token meta tag is present
- Check API endpoint returns correct JSON
- Verify polling is working (check Network tab in browser dev tools)

### Notifications not being created
- Check if notification helper functions are defined in config.php
- Verify PDO connection is active
- Check error logs for any SQL errors

## Advanced: Email/SMS Integration

To send email or SMS in addition to in-app notifications, modify the `createNotification()` function in config.php:

```php
function createNotification($pdo, $userId, $type, $title, $message, $options = []) {
    // ... existing code to create in-app notification ...

    // Get user's email/phone preferences
    $stmt = $pdo->prepare("SELECT email, phone, email_notifications_enabled, sms_notifications_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Send email if enabled
    if ($user['email_notifications_enabled'] && $user['email']) {
        sendNotificationEmail($user['email'], $title, $message);
    }

    // Send SMS if enabled
    if ($user['sms_notifications_enabled'] && $user['phone']) {
        sendNotificationSMS($user['phone'], $title, $message);
    }

    return $notificationId;
}
```

## Support

For issues or questions:
1. Check the log files in `/logs`
2. Verify database tables are created correctly
3. Check API endpoint directly in browser
4. Review browser console for JavaScript errors

---

**Last Updated:** 2025-11-06
**Version:** 1.0
