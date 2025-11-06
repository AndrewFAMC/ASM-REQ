<?php
require_once 'config.php';
// No redirection needed for logged-in users on the about page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>About - HCC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .font-heading { font-family: 'Playfair Display', serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        /* Fade-in animation on scroll */
        .fade-in-section {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .fade-in-section.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Header scroll effect */
        #main-header.scrolled {
            background-color: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px); /* For Safari */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="font-body">

    <div id="main-content">
        <!-- Header -->
        <header id="main-header" class="fixed top-0 left-0 right-0 z-30 p-6 md:p-8 transition-all duration-300">
            <div class="flex justify-between items-center max-w-7xl mx-auto">
                <a href="index.php" class="flex items-center space-x-3 z-10">
                    <img src="logo/1.png" alt="HCC Logo" class="h-10 w-10 filter brightness-0 invert">
                    <span class="font-body text-lg font-medium text-[var(--heading-color)] hidden sm:block">HCC Asset Management</span>
                </a>
                <a href="login.php" class="font-body text-base font-medium text-[var(--bg-color)] bg-[var(--heading-color)] px-6 py-2 rounded-full hover:opacity-80 transition-opacity duration-300">
                    Login
                </a>
            </div>
        </header>

        <main>
            <!-- About Section -->
            <section class="fade-in-section min-h-screen flex items-center justify-center py-32 px-6">
                <div class="max-w-4xl text-center">
                    <h1 class="font-heading text-4xl sm:text-5xl md:text-6xl text-[var(--heading-color)]">About The System</h1>
                    <h2 class="font-body text-xl md:text-2xl mt-8 leading-relaxed text-[var(--text-color)]">
                        A Web-based Asset Management System with Code-Scanning Technology and Digital Equipment Integration.
                    </h2>
                    <div class="w-24 h-px bg-[var(--secondary-color)] mx-auto my-10"></div>
                    <p class="font-body text-lg md:text-xl leading-relaxed text-[var(--text-color)]">
                        This innovative solution aims to modernize Holy Cross Colleges' asset management. Resources and equipment may be effectively tracked in real-time by scanning barcodes and QR codes. The system's integration with digital technology guarantees precise inventory management, minimizes losses, and gives managers instant access to analytics and data. It strengthens operational effectiveness, increases transparency, and advances the institutional and academic purpose of Holy Cross Colleges in Santa Rosa and Tarlac.
                    </p>
                    <a href="index.php" class="inline-block mt-12 font-body text-base font-medium border border-[var(--primary-color)] text-[var(--heading-color)] px-8 py-3 rounded-full hover:bg-[var(--primary-color)] hover:text-[var(--bg-color)] transition-colors duration-300">
                        Back to Home
                    </a>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="text-center py-8 px-6 font-body text-sm text-[var(--secondary-color)]">
            <p>&copy; <?= date('Y') ?> Holy Cross Colleges. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Simple fade-in for the main section on page load
        document.addEventListener('DOMContentLoaded', function () {
            const section = document.querySelector('.fade-in-section');
            if(section) {
                // Use a short timeout to ensure the transition is applied after the initial render
                setTimeout(() => {
                    section.classList.add('is-visible');
                }, 100);
            }
        });
    </script>

</body>
</html>
