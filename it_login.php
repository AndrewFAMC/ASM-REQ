<?php
require_once 'config.php';

// If IT admin is already logged in, redirect to the IT dashboard
if (isset($_SESSION['it_admin_logged_in']) && $_SESSION['it_admin_logged_in'] === true) {
    header('Location: it_dashboard.php');
    exit;
}

$error = '';
$loginSuccess = false;
$redirectUrl = 'it_dashboard.php';

// Hardcoded IT admin credentials
define('IT_ADMIN_EMAIL', 'hccsuperadmin@gmail.com');
define('IT_ADMIN_PASSWORD_HASH', password_hash('superadmin123', PASSWORD_DEFAULT));

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Verify credentials
        if ($email === IT_ADMIN_EMAIL && password_verify($password, IT_ADMIN_PASSWORD_HASH)) {
            // Set session variables for IT admin
            $_SESSION['it_admin_logged_in'] = true;
            $_SESSION['it_admin_email'] = $email;
            $_SESSION['it_admin_login_time'] = time();

            $loginSuccess = true;
            // JavaScript will handle the redirection after showing a success alert
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Admin Login - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #111827; /* Dark Blue/Gray */
            --text-color: #e0e0e0;
            --heading-color: #ffffff;
            --primary-color: #3b82f6; /* Blue */
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
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: -1; filter: brightness(0.5);
        }
        .video-overlay { background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(8px); }
        .form-input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-color);
        }
        .form-input:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
            outline: none; ring: 2px; ring-color: var(--primary-color);
        }
        .swal2-popup { background: #1f2937 !important; color: #e5e7eb !important; }
        .swal2-title { color: #ffffff !important; }
        .swal2-html-container { color: #d1d5db !important; }
    </style>
</head>
<body>
    <video autoplay muted loop playsinline class="video-bg">
        <source src="videos/sample1.mp4" type="video/mp4">
    </video>
    <div class="absolute inset-0 video-overlay"></div>

    <div class="relative min-h-screen flex flex-col items-center justify-center p-4">
        
        <a href="login.php" class="absolute top-6 left-6 flex items-center space-x-2 text-sm font-medium text-[var(--secondary-color)] hover:text-[var(--primary-color)] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            <span>Back to Main Login</span>
        </a>

        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-blue-600/20 border border-blue-500 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <h1 class="font-heading text-4xl text-[var(--heading-color)]">IT Admin Portal</h1>
                <p class="text-base text-[var(--secondary-color)] mt-2">Restricted Access</p>
            </div>

            <div class="bg-black/20 backdrop-blur-xl p-8 rounded-2xl border border-white/10">
                <form method="POST" action="it_login.php">
                    <div class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-[var(--secondary-color)] mb-2">Email</label>
                            <input id="email" name="email" type="email" required
                                   class="form-input block w-full px-4 py-3 rounded-lg transition-all duration-200"
                                   placeholder="Enter admin email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-[var(--secondary-color)] mb-2">Password</label>
                            <input id="password" name="password" type="password" required
                                   class="form-input block w-full px-4 py-3 rounded-lg transition-all duration-200"
                                   placeholder="Enter password">
                        </div>
                    </div>

                    <div class="mt-10">
                        <button type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-semibold rounded-lg text-white bg-[var(--primary-color)] hover:bg-blue-600 transition-colors duration-300">
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
        <?php if ($loginSuccess): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful!',
                    html: 'Redirecting to the IT Dashboard...',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    willClose: () => { window.location.href = '<?= $redirectUrl ?>'; }
                }).then(() => { window.location.href = '<?= $redirectUrl ?>'; });
            });
        <?php elseif ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'error', title: 'Login Failed', text: '<?= addslashes($error) ?>' });
            });
        <?php endif; ?>
    </script>
</body>
</html>