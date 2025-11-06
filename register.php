<?php
// Include database configuration
require_once 'includes/db_connect.php';


// If user is already logged in, redirect to the appropriate dashboard by role
if (isLoggedIn() && validateSession($pdo)) {
    $role = strtolower($_SESSION['role'] ?? '');
    if ($role === 'admin') {
        header('Location: dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: staff_dashboard.php');
    } elseif ($role === 'custodian') {
        header('Location: custodian_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $fullName = trim($_POST['reg_full_name'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirmPassword = $_POST['reg_confirm_password'] ?? '';
    $campusId = $_POST['reg_campus_id'] ?? '';
        // Role from form with server-side validation; restrict self-registration to non-admin roles
    $role = strtolower(trim($_POST['reg_role'] ?? 'staff'));
    $allowedRoles = ['admin', 'staff', 'custodian'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'staff';
    }

        // Validation
        if (empty($username) || empty($email) || empty($fullName) || empty($password) || empty($campusId)) {
            $error = 'All fields are required.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        }

        if (empty($error)) {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = 'Username or email already exists.';
                } else {
                    // Hash password
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Store registration data in session
                    $_SESSION['reg_data'] = [
                        'username' => $username,
                        'email' => $email,
                        'full_name' => $fullName,
                        'password_hash' => $passwordHash,
                        'role' => $role,
                        'campus_id' => $campusId
                    ];

                    // Generate verification code
                    $code = generateVerificationCode($pdo, $email);
                    if ($code === false) {
                        $error = 'Failed to generate verification code. Please try again.';
                    } else {
                        // Redirect to verification page
                        header('Location: verify_registration.php');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = 'Registration failed. Please try again.';
            }
        }
}

// Get campuses for registration form
try {
    $campuses = fetchAll($pdo, "SELECT * FROM campuses ORDER BY campus_name");
} catch (Exception $e) {
    $campuses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HCC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'bounce-in': 'bounceIn 0.6s ease-out'
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        .bg-pattern {
            background-image:
                radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 bg-pattern">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 animate-fade-in">
            <!-- Header -->
            <div class="text-center animate-bounce-in">
                <div class="mx-auto h-20 w-20 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <span class="text-white text-2xl font-bold">HCC</span>
                </div>
                <h2 class="mt-6 text-2xl sm:text-3xl font-extrabold text-gray-900">
                    Create Account
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Holy Cross Colleges - Asset Management System
                </p>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 animate-slide-up">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 animate-slide-up">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800"><?= $success ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 animate-slide-up">
                <form method="POST" action="">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="reg_full_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Full Name *
                                </label>
                                <input id="reg_full_name" name="reg_full_name" type="text" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                       placeholder="Enter your full name"
                                       value="<?= htmlspecialchars($_POST['reg_full_name'] ?? '') ?>">
                            </div>

                            <div>
                                <label for="reg_username" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Username *
                                </label>
                                <input id="reg_username" name="reg_username" type="text" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                       placeholder="Choose a username"
                                       value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>">
                            </div>

                            <div class="md:col-span-2">
                                <label for="reg_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Email Address *
                                </label>
                                <input id="reg_email" name="reg_email" type="email" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                       placeholder="Enter your email address"
                                       value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
                            </div>

                            <div>
                                <label for="reg_campus_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Assigned Campus *
                                </label>
                                <select id="reg_campus_id" name="reg_campus_id" required
                                        class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 appearance-none bg-white">
                                    <option value="">Select your campus</option>
                                    <?php foreach ($campuses as $campus): ?>
                                        <option value="<?= $campus['id'] ?>" <?= ($_POST['reg_campus_id'] ?? '') == $campus['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($campus['campus_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="reg_role" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Role *
                                </label>
                                <select id="reg_role" name="reg_role" required
                                        class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 appearance-none bg-white">
                                    <?php $selRole = strtolower($_POST['reg_role'] ?? 'staff'); ?>
                                    <option value="admin" <?= $selRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="staff" <?= $selRole === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="custodian" <?= $selRole === 'custodian' ? 'selected' : '' ?>>Custodian</option>
                                </select>
                            </div>

                            <div>
                                <label for="reg_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Password *
                                </label>
                                <input id="reg_password" name="reg_password" type="password" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                       placeholder="Create a password (min. 8 characters)">
                            </div>

                            <div>
                                <label for="reg_confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Confirm Password *
                                </label>
                                <input id="reg_confirm_password" name="reg_confirm_password" type="password" required
                                       class="block w-full px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                       placeholder="Confirm your password">
                            </div>
                        </div>

                        <div class="mt-8">
                            <button type="submit"
                                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-purple-300 group-hover:text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                </span>
                                Create Account
                            </button>
                        </div>

                        <div class="mt-6 text-center">
                            <a href="login.php" class="text-sm font-medium text-purple-600 hover:text-purple-500 transition-colors duration-200">
                                Already have an account? Sign in
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="fixed bottom-0 left-0 right-0 text-center text-sm text-gray-500 animate-fade-in bg-white bg-opacity-80 backdrop-blur-sm py-4">
                <p>&copy; <?= date('Y') ?> Holy Cross Colleges. All rights reserved.</p>
                <p class="mt-1">Asset Management System v2.0</p>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('reg_confirm_password').addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-50, .bg-green-50');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
