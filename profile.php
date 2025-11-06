<?php
// profile.php - User Profile page
// Requires authentication and displays current user's profile information

require_once 'config.php';

// Enforce authentication and session validity
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}

$user = getUserInfo();
$userId = (int)($user['id'] ?? 0);

// Fetch latest user info from database (more complete than session)
$profile = fetchOne($pdo, "SELECT id, username, full_name, email, role, campus_id, profile_picture, created_at, last_login FROM users WHERE id = ?", [$userId]);

if (!$profile) {
    header('Location: login.php');
    exit;
}

// Handle profile update
$message = '';
$messageType = 'success'; // success or error
if (isset($_GET['updated'])) {
    $message = 'Profile updated successfully.';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = 'Failed to update profile. Please try again.';
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $remove_picture = isset($_POST['remove_picture']);

        // Basic validation
        if (empty($full_name) || empty($email)) {
            $message = 'Full name and email are required.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
            $messageType = 'error';
        } elseif (!empty($new_password) && strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters long.';
            $messageType = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New password and confirmation do not match.';
            $messageType = 'error';
        } elseif (!empty($new_password) && empty($current_password)) {
            $message = 'Current password is required to change password.';
            $messageType = 'error';
        } else {
            try {
                // Verify current password if changing password
                if (!empty($new_password)) {
                    $userData = fetchOne($pdo, "SELECT password_hash FROM users WHERE id = ?", [$userId]);
                    if (!$userData || !password_verify($current_password, $userData['password_hash'])) {
                        $message = 'Current password is incorrect.';
                        $messageType = 'error';
                    }
                }

                if (empty($message)) {
                    // Update database for text fields
                    $updateFields = [];
                    $updateParams = [];
                    if ($full_name !== $profile['full_name']) {
                        $updateFields[] = 'full_name = ?';
                        $updateParams[] = $full_name;
                    }
                    if ($email !== $profile['email']) {
                        $updateFields[] = 'email = ?';
                        $updateParams[] = $email;
                    }
                    if (!empty($new_password)) {
                        $updateFields[] = 'password_hash = ?';
                        $updateParams[] = password_hash($new_password, PASSWORD_DEFAULT);
                    }

                    if (!empty($updateFields)) {
                        $updateParams[] = $userId;
                        executeQuery($pdo, "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?", $updateParams);
                    }

                    // Handle profile picture
                    $pictureUpdated = false;
                    if ($remove_picture && !empty($profile['profile_picture'])) {
                        // Remove current picture
                        $oldPath = 'uploads/profile_pictures/' . $profile['profile_picture'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                        executeQuery($pdo, "UPDATE users SET profile_picture = NULL WHERE id = ?", [$userId]);
                        $_SESSION['profile_picture'] = null;
                        $pictureUpdated = true;
                    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['profile_picture'];
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                        if (!in_array($file['type'], $allowedTypes)) {
                            $message = 'Invalid file type. Only JPG, PNG, GIF allowed.';
                            $messageType = 'error';
                        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
                            $message = 'File too large. Maximum 2MB allowed.';
                            $messageType = 'error';
                        } else {
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $newName = time() . '_' . $userId . '.' . $ext;
                            $path = 'uploads/profile_pictures/' . $newName;

                            if (move_uploaded_file($file['tmp_name'], $path)) {
                                // Delete old profile picture if exists
                                if (!empty($profile['profile_picture'])) {
                                    $oldPath = 'uploads/profile_pictures/' . $profile['profile_picture'];
                                    if (file_exists($oldPath)) {
                                        unlink($oldPath);
                                    }
                                }

                                // Update database with new picture
                                executeQuery($pdo, "UPDATE users SET profile_picture = ? WHERE id = ?", [$newName, $userId]);
                                $_SESSION['profile_picture'] = $newName;
                                $pictureUpdated = true;
                            } else {
                                $message = 'Failed to upload file.';
                                $messageType = 'error';
                            }
                        }
                    }

                    if (empty($message)) {
                        // Update session with new details
                        if ($full_name !== $profile['full_name']) {
                            $_SESSION['full_name'] = $full_name;
                        }
                        if ($email !== $profile['email']) {
                            $_SESSION['email'] = $email;
                        }

                        // Redirect to show success message
                        header('Location: profile.php?updated=1');
                        exit;
                    } else {
                        // On error, redirect with error
                        header('Location: profile.php?error=1');
                        exit;
                    }
                } else {
                    header('Location: profile.php?error=1');
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $message = 'An error occurred while updating your profile. Please try again.';
                $messageType = 'error';
                header('Location: profile.php?error=1');
                exit;
            }
        }
    }
}

// Fetch campus info using hardcoded mapping from config.php
$campusInfo = null;
if (!empty($profile['campus_id']) && isset($campusNames[$profile['campus_id']])) {
    $campusInfo = ['campus_name' => $campusNames[$profile['campus_id']]];
}

// Determine appropriate dashboard URL based on role
$role = strtolower($profile['role'] ?? '');
$dashboardUrl = 'dashboard.php';
if ($role === 'employee') {
    $dashboardUrl = 'employee_dashboard.php';
} elseif ($role === 'custodian') {
    $dashboardUrl = 'custodian_dashboard.php';
} elseif ($role === 'office') {
    $dashboardUrl = 'office_dashboard.php';
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Holy Cross Colleges Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {}
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-16 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="<?= h($dashboardUrl) ?>" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Dashboard
                    </a>
                    <span class="hidden sm:inline text-gray-300">|</span>
                    <span class="text-sm text-gray-500">My Profile</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">Welcome, <?= h($user['full_name'] ?: ($user['username'] ?? 'User')) ?></span>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 rounded-lg <?php if ($messageType === 'success'): ?>bg-green-100 border border-green-400 text-green-700<?php else: ?>bg-red-100 border border-red-400 text-red-700<?php endif; ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 <?php if ($messageType === 'success'): ?>text-green-500<?php else: ?>text-red-500<?php endif; ?>" fill="currentColor" viewBox="0 0 20 20">
                        <?php if ($messageType === 'success'): ?>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        <?php else: ?>
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        <?php endif; ?>
                    </svg>
                    <?= h($message) ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-6 bg-gradient-to-r from-blue-600 to-indigo-600">
                <div class="flex items-center">
                    <div class="w-16 h-16 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-4 overflow-hidden">
                        <?php if (!empty($profile['profile_picture']) && file_exists('uploads/profile_pictures/' . $profile['profile_picture'])): ?>
                            <img src="uploads/profile_pictures/<?= h($profile['profile_picture']) ?>" alt="Profile Picture" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-white text-2xl font-bold">
                                <?= strtoupper(substr(($profile['full_name'] ?: ($profile['username'] ?? 'U')), 0, 1)) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-white">
                        <h1 class="text-2xl font-bold leading-tight"><?= h($profile['full_name'] ?: ($profile['username'] ?? 'User')) ?></h1>
                        <p class="text-blue-100 text-sm"><?= h(ucfirst($profile['role'] ?? 'user')) ?><?= isset($campusInfo['campus_name']) ? ' • ' . h($campusInfo['campus_name']) : '' ?></p>
                    </div>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Account Information</h2>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= h($profile['full_name'] ?: '-') ?></dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Username</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= h($profile['username'] ?: '-') ?></dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= h($profile['email'] ?: '-') ?></dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Role</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= h(ucfirst($profile['role'] ?? 'user')) ?></dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Campus</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= isset($campusInfo['campus_name']) ? h($campusInfo['campus_name']) : '—' ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Security & Activity</h2>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">User ID</dt>
                                <dd class="text-sm text-gray-900 col-span-2">#<?= h($profile['id']) ?></dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= $profile['last_login'] ? h(date('M d, Y h:i A', strtotime($profile['last_login']))) : '—' ?></dd>
                            </div>
                            <div class="py-3 grid grid-cols-3 gap-4">
                                <dt class="text-sm font-medium text-gray-500">Account Created</dt>
                                <dd class="text-sm text-gray-900 col-span-2"><?= $profile['created_at'] ? h(date('M d, Y h:i A', strtotime($profile['created_at']))) : '—' ?></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Quick Actions</h3>
                        <div class="flex flex-wrap gap-3">
                            <a href="<?= h($dashboardUrl) ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Back to Dashboard
                            </a>
                            <button onclick="openEditModal()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Profile
                            </button>
                            <a href="logout.php" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-white bg-opacity-20">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-bold text-white">Edit Profile</h3>
                                <p class="text-blue-100 text-sm">Update your account information</p>
                            </div>
                        </div>
                        <button onclick="closeEditModal()" class="text-white hover:text-blue-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <form id="editForm" method="post" enctype="multipart/form-data" class="bg-white">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="px-6 py-6 space-y-6">
                        <!-- Personal Information -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h3 class="text-md font-medium text-gray-900 mb-4">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="modal_full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" id="modal_full_name" name="full_name" value="<?= h($profile['full_name']) ?>" required
                                           class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                <div>
                                    <label for="modal_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <input type="email" id="modal_email" name="email" value="<?= h($profile['email']) ?>" required
                                           class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h3 class="text-md font-medium text-gray-900 mb-4">Change Password <span class="text-sm font-normal text-gray-500">(optional)</span></h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="modal_current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" id="modal_current_password" name="current_password"
                                           class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                <div>
                                    <label for="modal_new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" id="modal_new_password" name="new_password" minlength="8"
                                           class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                <div>
                                    <label for="modal_confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <input type="password" id="modal_confirm_password" name="confirm_password" minlength="8"
                                           class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Leave blank to keep current password. Minimum 8 characters.</p>
                        </div>

                        <!-- Profile Picture -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h3 class="text-md font-medium text-gray-900 mb-4">Profile Picture</h3>

                            <!-- Current Picture Preview -->
                            <?php if (!empty($profile['profile_picture']) && file_exists('uploads/profile_pictures/' . $profile['profile_picture'])): ?>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Profile Picture</label>
                                    <div class="flex items-center space-x-4">
                                        <img src="uploads/profile_pictures/<?= h($profile['profile_picture']) ?>" alt="Current Profile Picture"
                                             class="w-16 h-16 rounded-full object-cover border-2 border-gray-300">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="modal_remove_picture" name="remove_picture" value="1"
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="modal_remove_picture" class="ml-2 block text-sm text-gray-700">
                                                Remove current picture
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label for="modal_profile_picture" class="block text-sm font-medium text-gray-700">Upload New Picture</label>
                                <input type="file" id="modal_profile_picture" name="profile_picture" accept="image/*"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-sm text-gray-500 mt-1">Max 2MB. JPG, PNG, GIF only. Leave empty to keep current picture.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse space-x-3 space-x-reverse">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            Update Profile
                        </button>
                        <button type="button" onclick="closeEditModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-xl font-semibold transition-all duration-200">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>

    <script>
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('modalOverlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('modalOverlay').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking overlay
        document.getElementById('modalOverlay').addEventListener('click', closeEditModal);

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>

</body>
</html>
