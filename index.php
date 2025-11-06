<?php
require_once 'config.php';

// Redirect logged-in users to their respective dashboards
if (isLoggedIn() && validateSession($pdo)) {
    $role = strtolower($_SESSION['role'] ?? '');
    $redirects = [
        'admin' => 'dashboard.php',
        'employee' => 'employee_dashboard.php',
        'custodian' => 'custodian_dashboard.php',
        'office' => 'office_dashboard.php'
    ];
    header('Location: ' . ($redirects[$role] ?? 'login.php'));
    exit;
}

// Check if the user is coming from the login page to skip the intro
$skipIntro = false;
if (isset($_SERVER['HTTP_REFERER'])) {
    $refererPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    // If the basename of the referer path is login.php, we skip the intro.
    if (basename($refererPath) === 'login.php') {
        $skipIntro = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>HCC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Great+Vibes&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- 
        CUSTOMIZATION INSTRUCTIONS:
        - Colors: Change CSS variables like --bg-color, --text-color, --primary-color.
        - Typography: Change font families in the `font-heading` and `font-body` classes.
        - Videos: Update the `src` attribute in the <video> tags below.
    -->
    <style>
        :root {
            --bg-color: #ffffff; /* White */
            --text-color: #1d1d1f; /* Apple's primary text color */
            --heading-color: #000000; /* Black */
            --primary-color: #0071e3; /* Apple Blue */
            --secondary-color: #6e6e73; /* Apple's secondary text color */
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-color);
            color: var(--secondary-color);
        }

        .font-heading { font-family: 'Cormorant Garamond', serif; }
        .font-signature { font-family: 'Great Vibes', cursive; }
        .font-body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }

        .hero-video-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: blur(5px);
            transform: scale(1.05); /* Scale up to hide blurry edges */
            z-index: -1;
        }

        .hero-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px); /* For Safari */
        }

        .scroll-indicator {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-15px); }
            60% { transform: translateY(-7px); }
        }

        /* --- Scroll Animation System --- */
        .animate-on-scroll {
            opacity: 0;
            transition: opacity 1.5s cubic-bezier(0.165, 0.84, 0.44, 1), transform 1.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .animate-fade-in-up {
            transform: translateY(50px);
        }
        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: none;
        }
        /* --- End Scroll Animation System --- */

        .fade-in-section { /* Keep for compatibility if needed, but new system is preferred */
        }

        /* Header scroll effect */
        #main-header {
            opacity: 0;
            transform: translateY(-100%);
            transition: opacity 0.4s ease-out, transform 0.4s ease-out, background-color 0.3s ease-in-out;
        }
        #main-header.scrolled {
            background-color: rgba(255, 255, 255, 0.8); /* White with opacity */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px); /* For Safari */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            opacity: 1;
            transform: translateY(0);
        }

        /* Mobile Orientation Lock */
        #orientation-lock {
            display: none;
            position: fixed;
            inset: 0;
            background-color: var(--bg-color);
            color: var(--heading-color);
            z-index: 100;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        /* Ensure videos cover their containers on all screen sizes, especially mobile */
        #intro-video, .hero-video-bg {
            min-width: 100%;
            min-height: 100%;
        }

        /* New Intro Animation Styles */
        #intro-container .intro-content {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInDistant 2s 0.5s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }
        #intro-container .intro-logo {
            animation: logo-glow 3s 1s infinite alternate;
        }

        @keyframes fadeInScale {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        @keyframes logo-glow {
            from { filter: drop-shadow(0 0 8px rgba(0, 113, 227, 0.3)); }
            to { filter: drop-shadow(0 0 25px rgba(0, 113, 227, 0.6)); }
        }
        @keyframes fadeInDistant {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .apple-link {
            color: var(--primary-color);
            text-decoration: none;
            background-image: linear-gradient(var(--primary-color), var(--primary-color));
            background-position: 0% 100%;
            background-repeat: no-repeat;
            background-size: 0% 1px;
            transition: background-size .3s;
        }
        .apple-link:hover {
            background-size: 100% 1px;
        }
    </style>
</head>
<body class="font-body" style="overflow: hidden;">

    <div id="main-content" class="opacity-0 transition-opacity duration-500">

        <!-- Mobile Orientation Lock Screen (Now Disabled) -->
        <div id="orientation-lock" class="flex-col p-4 hidden">
            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <p class="font-heading text-2xl">Please rotate your device</p>
            <p class="mt-2 text-sm text-[var(--secondary-color)]">This experience is best viewed in landscape mode.</p>
        </div>
        <!-- Header -->
        <header id="main-header" class="fixed top-0 left-0 right-0 z-30 p-4 md:p-8">
            <div class="flex justify-between items-center max-w-7xl mx-auto gap-4">
                <a href="index.php" class="flex items-center z-10">
                    <img src="logo/1.png" alt="HCC Logo" class="h-6 w-6 sm:h-7 sm:w-7">
                </a>
                <a href="login.php" class="font-body text-xs sm:text-sm font-medium bg-gray-100 text-gray-800 px-4 py-2 rounded-full hover:bg-gray-200 transition-colors duration-300 flex-shrink-0">
                    Login
                </a>
            </div>
        </header>

        <main>
            <!-- Section 1: Hero -->
            <section class="min-h-screen w-full flex flex-col justify-center items-center relative text-center overflow-hidden pt-20 pb-10">
                <!-- Content -->
                <div class="relative z-10 px-6 max-w-4xl mx-auto animate-on-scroll animate-fade-in-up">
                    <h2 class="font-body text-xl md:text-2xl font-semibold text-[var(--text-color)] mb-4">Asset Management</h2>
                    <h1 class="font-signature font-bold text-4xl sm:text-5xl md:text-6xl lg:text-7xl text-[var(--heading-color)] tracking-wide">
                        Clarity in Control.
                    </h1>
                    <div class="flex justify-center items-center gap-8 md:gap-12 my-12 md:my-20">
                        <img src="logo/1.png" alt="HCC Logo" class="h-40 w-40 md:h-48 md:w-48 intro-logo">
                        <img src="logo/2.png" alt="Holy Cross College" class="h-48 md:h-56 w-auto">
                    </div>
                    <div class="font-body text-lg md:text-xl max-w-3xl mx-auto leading-relaxed text-[var(--text-color)] text-center" style="transition-delay: 0.2s;">
                        <p>
                            The HCC Asset Management System is the definitive platform for managing institutional resources across all Holy Cross Colleges campuses. It introduces powerful QR-based tracking, provides insightful analytics, and streamlines workflows — so you can manage assets with clarity and control. When it comes to efficiency, it’s the most advanced system for our institution.
                        </p>
                        <a href="about.php" class="inline-block mt-4 font-medium apple-link">Learn more about the system &rarr;</a>
                    </div>
                </div>
            </section>

            <!-- Section 2: About -->
            <section class="flex items-center justify-center py-24 px-6">
                <div class="max-w-sm landscape:max-w-3xl">
                    <h2 class="font-heading text-5xl md:text-6xl text-[var(--heading-color)] animate-on-scroll animate-fade-in-up text-center">Efficiency, Redefined.</h2>
                    <p class="font-body text-lg md:text-xl mt-8 leading-relaxed text-[var(--secondary-color)] animate-on-scroll animate-fade-in-up text-justify" style="transition-delay: 0.2s;">
                        Our system revolutionizes asset management at Holy Cross Colleges. Using advanced QR technology, we provide real-time tracking, reduce manual errors, and offer data-driven insights to enhance operational efficiency across both campuses.
                    </p>
                    <div class="text-center">
                        <a href="about.php" class="inline-block mt-10 font-body text-lg font-medium apple-link animate-on-scroll animate-fade-in-up" style="transition-delay: 0.4s;">
                        Learn more &rarr;
                        </a>
                    </div>
                </div>
            </section>

            <!-- Section 3: Features -->
            <section class="flex items-center justify-center py-24 px-6 bg-gray-50">
                <div class="max-w-sm landscape:max-w-5xl w-full animate-on-scroll animate-fade-in-up">
                    <h2 class="font-heading text-5xl md:text-6xl text-center text-[var(--heading-color)] mb-20">Powerful Features, Simplified.</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-16 text-center">
                        <div class="animate-on-scroll animate-fade-in-up text-center" style="transition-delay: 0.2s;">
                            <h3 class="font-heading text-3xl text-[var(--heading-color)]">Real-Time Tracking</h3>
                            <p class="mt-4 text-[var(--secondary-color)] leading-relaxed text-lg text-justify">Instantly locate and verify assets with QR and barcode scanning, providing a live view of your inventory.</p>
                        </div>
                        <div class="animate-on-scroll animate-fade-in-up text-center" style="transition-delay: 0.4s;">
                            <h3 class="font-heading text-3xl text-[var(--heading-color)]">Insightful Analytics</h3>
                            <p class="mt-4 text-[var(--secondary-color)] leading-relaxed text-lg text-justify">Generate comprehensive reports on asset value, depreciation, and distribution to optimize resource allocation.</p>
                        </div>
                        <div class="animate-on-scroll animate-fade-in-up text-center" style="transition-delay: 0.6s;">
                            <h3 class="font-heading text-3xl text-[var(--heading-color)]">Role-Based Access</h3>
                            <p class="mt-4 text-[var(--secondary-color)] leading-relaxed text-lg text-justify">Securely manage permissions for admins, staff, and custodians, ensuring data integrity and accountability.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 4: Media Showcase -->
            <section class="relative w-full flex items-center justify-center py-24 px-6 text-white">
                <video autoplay muted loop playsinline poster="videos/intro_poster.jpg" class="absolute top-0 left-0 w-full h-full object-cover z-0" style="filter: brightness(0.7);">
                    <source src="videos/intro.mp4" type="video/mp4">
                </video>
                <div class="absolute inset-0 bg-black/40"></div>
                <div class="relative z-10 h-full flex flex-col justify-center items-center text-center p-6 max-w-sm landscape:max-w-4xl">
                    <h2 class="font-heading text-5xl md:text-6xl animate-on-scroll animate-fade-in-up [text-shadow:2px_2px_10px_rgba(0,0,0,0.5)] text-center">Streamline Your Workflow.</h2>
                    <p class="font-body text-lg md:text-xl mt-6 max-w-2xl animate-on-scroll animate-fade-in-up [text-shadow:1px_1px_8px_rgba(0,0,0,0.5)] text-justify" style="transition-delay: 0.2s;">
                        From acquisition to retirement, our system provides a clear, auditable trail for every asset.
                    </p>
                </div>
            </section>

            <!-- Section 5: Contact Us -->
            <section class="flex items-center justify-center py-24 px-6 bg-gray-50">
                <div class="relative z-10 max-w-sm landscape:max-w-4xl text-center">
                    <h2 class="font-heading text-5xl md:text-6xl animate-on-scroll animate-fade-in-up text-[var(--heading-color)] text-center">Get in Touch</h2>
                    <p class="font-body text-lg md:text-xl mt-8 leading-relaxed animate-on-scroll animate-fade-in-up text-[var(--secondary-color)] text-justify" style="transition-delay: 0.2s;">
                        For inquiries, support, or feedback regarding the Asset Management System, please reach out to the appropriate department.
                    </p>
                    <div class="mt-20 grid grid-cols-1 md:grid-cols-2 gap-16 text-left">
                        <!-- Campus 1 Info -->
                        <div class="border-l-2 border-gray-200 pl-8">
                            <h3 class="font-heading text-3xl text-[var(--heading-color)]">Sta. Rosa Campus</h3>
                            <p class="mt-4 text-[var(--secondary-color)] text-lg">Poblacion, Santa Rosa, Nueva Ecija</p>
                            <p class="mt-2 text-[var(--secondary-color)] text-lg">Phone: (123) 456-7890</p>
                            <a href="mailto:support.starosa@hcc.edu.ph" class="mt-2 inline-block text-lg apple-link">support.starosa@hcc.edu.ph</a>
                        </div>
                        <!-- Campus 2 Info -->
                        <div class="border-l-2 border-gray-200 pl-8">
                            <h3 class="font-heading text-3xl text-[var(--heading-color)]">Concepcion Campus</h3>
                            <p class="mt-4 text-[var(--secondary-color)] text-lg">Concepcion, Tarlac</p>
                            <p class="mt-2 text-[var(--secondary-color)] text-lg">Phone: (098) 765-4321</p>
                            <a href="mailto:support.tarlac@hcc.edu.ph" class="mt-2 inline-block text-lg apple-link">support.tarlac@hcc.edu.ph</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 6: CTA -->
            <section class="flex flex-col items-center justify-center text-center py-24 px-6">
                <img src="logo/1.png" alt="HCC Logo" class="h-24 w-24 mb-10 animate-on-scroll animate-fade-in-up">
                <h2 class="font-heading text-5xl md:text-6xl text-[var(--heading-color)] animate-on-scroll animate-fade-in-up text-center max-w-sm landscape:max-w-2xl">Ready to Take Control?</h2>
                <p class="font-body text-lg md:text-xl mt-8 max-w-sm landscape:max-w-2xl leading-relaxed text-[var(--secondary-color)] animate-on-scroll animate-fade-in-up text-justify" style="transition-delay: 0.2s;">
                    Access your dashboard to begin managing your assets with efficiency and precision. For inquiries, please contact the administration.
                </p>
                <a href="login.php" class="inline-block mt-12 font-body text-lg font-medium bg-[var(--primary-color)] text-white px-8 py-3 rounded-full hover:bg-blue-700 transition-colors duration-300 animate-on-scroll animate-fade-in-up" style="transition-delay: 0.3s;">
                    Proceed to Login
                </a>
            </section>
        </main>

        <!-- Footer -->
        <footer class="text-center py-12 px-6 font-body text-sm text-[var(--secondary-color)] bg-gray-50 border-t border-gray-200">
            <p>&copy; <?= date('Y') ?> Holy Cross Colleges. All rights reserved.</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mainContent = document.getElementById('main-content');

            // Check the PHP flag to see if we should skip the intro
            const shouldSkipIntro = <?= json_encode($skipIntro) ?>;

            function startMainContent() {
                mainContent.style.opacity = '1';
                document.body.style.overflow = 'auto';
            }

            // If we should skip, call startMainContent immediately.
            if (shouldSkipIntro) {
                startMainContent();
            } else {
                // Fade in the main content after a short delay for a smooth entry
                setTimeout(startMainContent, 100);
            }

            const sections = document.querySelectorAll('.animate-on-scroll');
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                rootMargin: '0px',
                threshold: 0.15
            });

            sections.forEach(section => {
                observer.observe(section);
            });

            // --- Header scroll effect ---
            const header = document.getElementById('main-header');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });

            // Trigger the first animation check on load for elements already in view
            setTimeout(() => {
                const visibleElements = document.querySelectorAll('.animate-on-scroll');
                visibleElements.forEach(el => observer.observe(el));
            }, 150);
        });
    </script>
</body>
</html>