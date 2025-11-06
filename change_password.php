<?php
require_once 'config.php';

// If user is not logged in or not forced to change password, redirect
if (!isLoggedIn() || !isset($_SESSION['force_password_change']) || !$_SESSION['force_password_change']) {
    if (isLoggedIn()) {
        $role = strtolower($_SESSION['role'] ?? '');
        $redirects = [
            'admin' => 'dashboard.php',
            'employee' => 'employee_dashboard.php',
            'custodian' => 'custodian_dashboard.php',
            'office' => 'office_dashboard.php'
        ];
        header('Location: ' . ($redirects[$role] ?? 'login.php'));
    } else {
        header('Location: login.php');
    }
    exit;
}

$error = '';
$changeSuccess = false;
$redirectUrl = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        try {
            $userId = $_SESSION['user_id'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password and remove force_password_change flag
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            // Unset the session variable
            unset($_SESSION['force_password_change']);

            $changeSuccess = true;

            // Determine redirect URL
            $role = strtolower($_SESSION['role'] ?? '');
            $redirects = [
                'admin' => 'dashboard.php',
                'employee' => 'employee_dashboard.php',
                'custodian' => 'custodian_dashboard.php',
                'office' => 'office_dashboard.php'
            ];
            $redirectUrl = $redirects[$role] ?? 'login.php';

        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Change Password - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/sweetalert-config.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0a0a0a;
            --text-color: #e0e0e0;
            --heading-color: #ffffff;
            --primary-color: #ffffff;
            --secondary-color: #a0a0a0;
            --error-color: #f87171;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }
        .font-heading { font-family: 'Playfair Display', serif; }
        .video-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .video-overlay {
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }
        .form-input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-color);
        }
        .form-input:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
            outline: none;
        }
        .swal2-popup {
            background: #1f2937 !important;
            color: #e5e7eb !important;
        }
        .swal2-title {
            color: #ffffff !important;
        }
        .swal2-html-container {
            color: #d1d5db !important;
        }
        .swal2-container {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
    </style>
</head>
<body>
    <video autoplay muted loop playsinline class="video-bg">
        <source src="videos/sample1.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="absolute inset-0 video-overlay"></div>

    <div class="relative min-h-screen flex flex-col items-center justify-center p-4">

        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img src="logo/1.png" alt="HCC Logo" class="h-16 w-16 mx-auto mb-4">
                <h1 class="font-heading text-4xl text-[var(--heading-color)]">Create New Password</h1>
                <p class="text-base text-[var(--secondary-color)] mt-2">For security, you must create a new password.</p>
            </div>

            <div class="bg-black/20 backdrop-blur-xl p-8 rounded-2xl border border-white/10">
                <form method="POST" action="">
                    <div class="space-y-6">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-[var(--secondary-color)] mb-2">
                                New Password
                            </label>
                            <input id="new_password" name="new_password" type="password" required minlength="8"
                                   class="form-input block w-full px-4 py-3 rounded-lg transition-all duration-200"
                                   placeholder="Enter your new password">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-[var(--secondary-color)] mb-2">
                                Confirm New Password
                            </label>
                            <input id="confirm_password" name="confirm_password" type="password" required
                                   class="form-input block w-full px-4 py-3 rounded-lg transition-all duration-200"
                                   placeholder="Confirm your new password">
                        </div>
                    </div>

                    <p id="password-match-error" class="mt-4 text-sm text-[var(--error-color)] text-center hidden"></p>

                    <?php if ($error): ?>
                        <p class="mt-4 text-sm text-[var(--error-color)] text-center"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <div class="mt-10">
                        <button type="submit" id="submit-button"
                                class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-semibold rounded-lg text-[var(--bg-color)] bg-[var(--primary-color)] hover:opacity-80 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            Set New Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="absolute bottom-0 left-0 right-0 text-center py-6 text-sm text-[var(--secondary-color)]">
            <p>&copy; <?= date('Y') ?> Holy Cross Colleges. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Show success message and redirect
        <?php if ($changeSuccess): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Changed!',
                    html: 'Your password has been updated successfully.<br>Redirecting to your dashboard...',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    willClose: () => {
                        window.location.href = '<?= $redirectUrl ?>';
                    }
                }).then(() => {
                    window.location.href = '<?= $redirectUrl ?>';
                });
            });
        <?php endif; ?>

        // Show error alert if there's an error
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showErrorAlert('Password Change Failed', '<?= addslashes($error) ?>');
            });
        <?php endif; ?>

        // Real-time password validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const submitButton = document.getElementById('submit-button');
            const errorElement = document.getElementById('password-match-error');

            function validatePasswords() {
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                let errorMessage = '';

                const fieldsHaveInput = newPassword.length > 0 && confirmPassword.length > 0;

                if (fieldsHaveInput) {
                    if (newPassword.length < 8) {
                        errorMessage = 'Password must be at least 8 characters long.';
                    } else if (newPassword !== confirmPassword) {
                        errorMessage = 'Passwords do not match.';
                    }
                }

                if (errorMessage) {
                    errorElement.textContent = errorMessage;
                    errorElement.classList.remove('hidden');
                    newPasswordInput.classList.add('border-[var(--error-color)]');
                    confirmPasswordInput.classList.add('border-[var(--error-color)]');
                    submitButton.disabled = true;
                } else {
                    errorElement.classList.add('hidden');
                    newPasswordInput.classList.remove('border-[var(--error-color)]');
                    confirmPasswordInput.classList.remove('border-[var(--error-color)]');
                    // Enable button only if all conditions are met
                    submitButton.disabled = !(newPassword.length >= 8 && newPassword === confirmPassword);
                }
            }

            newPasswordInput.addEventListener('input', validatePasswords);
            confirmPasswordInput.addEventListener('input', validatePasswords);
        });
    </script>
</body>
</html>