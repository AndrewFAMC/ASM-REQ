<?php
// Include database configuration
require_once 'config.php';



// If user is already logged in, redirect to the appropriate dashboard by role
if (isLoggedIn() && validateSession($pdo)) {
    $role = strtolower($_SESSION['role'] ?? '');
    if ($role === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif ($role === 'employee') {
        header('Location: employee/dashboard.php');
    } elseif ($role === 'custodian') {
        header('Location: custodian/dashboard.php');
    } elseif ($role === 'office') {
        header('Location: office/office_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$logoutSuccess = false;

// Check for logout success message
if (isset($_SESSION['logout_success'])) {
    $logoutSuccess = true;
    unset($_SESSION['logout_success']);
}

$loginSuccess = false;
$redirectUrl = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $result = authenticateUser($pdo, $username, $password);
        if ($result['success']) {
            // Set login success flag
            $loginSuccess = true;

            // Check if password change is required
            if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change']) {
                $redirectUrl = 'change_password.php';
            } else {
                // Determine redirect URL based on role
                $role = strtolower($_SESSION['role'] ?? '');
                if ($role === 'admin') {
                    $redirectUrl = 'admin/admin_dashboard.php';
                } elseif ($role === 'employee') {
                    $redirectUrl = 'employee/dashboard.php';
                } elseif ($role === 'custodian') {
                    $redirectUrl = 'custodian/dashboard.php';
                } elseif ($role === 'office') {
                    $redirectUrl = 'office/office_dashboard.php';
                } else {
                    $redirectUrl = 'dashboard.php';
                }
            }
            // Don't redirect immediately - let JavaScript handle it
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HCC Asset Management</title>
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
        
        <a href="index.php" class="absolute top-6 left-6 flex items-center space-x-2 text-sm font-medium text-[var(--secondary-color)] hover:text-[var(--primary-color)] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            <span>Back to Home</span>
        </a>

        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img src="logo/1.png" alt="HCC Logo" class="h-16 w-16 mx-auto mb-4">
                <h1 class="font-heading text-4xl text-[var(--heading-color)]">Account Login</h1>
                <p class="text-base text-[var(--secondary-color)] mt-2">Sign in to access the Asset Management System.</p>
            </div>

            <div class="bg-black/20 backdrop-blur-xl p-8 rounded-2xl border border-white/10">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">

                    <div class="space-y-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-[var(--secondary-color)] mb-2">
                                Username or Email
                            </label>
                            <input id="username" name="username" type="text" required
                                   class="form-input block w-full px-4 py-3 rounded-lg transition-all duration-200"
                                   placeholder="Enter your username or email"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-[var(--secondary-color)] mb-2">
                                Password
                            </label>
                            <input id="password" name="password" type="password" required
                                   class="form-input block w-full px-4 py-3 rounded-lg transition-all duration-200"
                                   placeholder="Enter your password">
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <p class="mt-4 text-sm text-[var(--error-color)] text-center"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <div class="mt-10">
                        <button type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-semibold rounded-lg text-[var(--bg-color)] bg-[var(--primary-color)] hover:opacity-80 transition-opacity duration-300">
                            Sign In
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
        // Show login success message and redirect
        <?php if ($loginSuccess): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful!',
                    html: 'Welcome back, <strong><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong>!<br>Redirecting to your dashboard...',
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

        // Show logout success message
        <?php if ($logoutSuccess): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showSuccessAlert('Logged Out', 'You have been successfully logged out.');
            });
        <?php endif; ?>

        // Show error alert if there's an error
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showErrorAlert('Login Failed', '<?= addslashes($error) ?>');
            });
        <?php endif; ?>
    </script>
</body>
</html>
