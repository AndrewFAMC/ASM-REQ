<?php
// head.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Holy Cross College - AMS</title>

  <!-- Tailwind + SweetAlert + font -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/sweetalert-config.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&display=swap" rel="stylesheet">

  <style>
    /* Smoke effect */
    .smoke-layer {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 600px;
      height: 100%;
      z-index: 0;
      opacity: 0.45;
      pointer-events: none;
      background: radial-gradient(circle at center,
                  rgba(59,130,246,0.25) 0%,
                  rgba(147,197,253,0.15) 40%,
                  rgba(255,255,255,0) 80%);
      filter: blur(120px);
      animation: floatSmoke 14s ease-in-out infinite alternate;
    }
    .smoke-left { left: -250px; animation-delay: 0s; }
    .smoke-right { right: -250px; animation-delay: 6s; }
    @keyframes floatSmoke {
      0% { transform: translateY(0px) scale(1); opacity: 0.4; }
      50% { transform: translateY(-40px) scale(1.05); opacity: 0.55; }
      100% { transform: translateY(0px) scale(1); opacity: 0.4; }
    }

    /* General Mobile Optimizations (up to 767px) */
    @media (max-width: 767px) {
      /* Reduce font sizes for smaller screens */
      body {
        font-size: 16px;
        line-height: 1.5;
      }
      h1 { font-size: 2rem; }
      h2 { font-size: 1.75rem; }
      h3 { font-size: 1.5rem; }
      h4 { font-size: 1.25rem; }

      /* Adjust button padding and font size for mobile */
      button, .btn, input[type="submit"], input[type="button"] {
        padding: 10px 16px;
        font-size: 16px;
        min-height: 40px;
        min-width: 40px;
      }

      /* Adjust container padding */
      .container, .max-w-7xl, .max-w-6xl, .max-w-5xl, .max-w-4xl {
        padding-left: 1rem;
        padding-right: 1rem;
      }
    }
    /* iPad Pro Optimizations */
    @media (min-width: 834px) and (max-width: 1366px) {
      /* Increase touch targets for buttons and interactive elements */
      button, .btn, input[type="submit"], input[type="button"] {
        min-height: 44px;
        min-width: 44px;
        padding: 12px 24px;
        font-size: 18px;
      }

      /* Improve typography for tablet readability */
      body {
        font-size: 18px;
        line-height: 1.6;
      }

      h1 { font-size: 2.5rem; }
      h2 { font-size: 2rem; }
      h3 { font-size: 1.75rem; }
      h4 { font-size: 1.5rem; }

      /* Better spacing for tablet layout */
      .container, .max-w-6xl, .max-w-5xl {
        padding-left: 2rem;
        padding-right: 2rem;
      }

      /* Navigation improvements */
      nav a, nav button {
        padding: 12px 16px;
        font-size: 18px;
      }

      /* Form elements */
      input, select, textarea {
        font-size: 18px;
        padding: 12px;
        min-height: 44px;
      }

      /* Card layouts */
      .card, .bg-white.rounded-xl {
        padding: 2rem;
      }

      /* Sidebar adjustments for tablet */
      .sidebar {
        width: 280px;
      }

      /* Modal improvements */
      .modal-content {
        max-width: 90vw;
        margin: 2rem auto;
      }

      /* Table improvements */
      table th, table td {
        padding: 12px;
        font-size: 16px;
      }

      /* Touch-friendly hover states */
      .hover\:scale-105:hover {
        transform: scale(1.02);
      }

      /* Smooth scrolling */
      html {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
      }

      /* Prevent zoom on input focus */
      input[type="text"], input[type="email"], input[type="password"], textarea, select {
        font-size: 16px !important;
      }
    }

    /* Specific iPad Pro 12.9" adjustments */
    @media (min-width: 1024px) and (max-width: 1366px) and (orientation: landscape) {
      .hero-section {
        padding-top: 8rem;
      }

      .service-cards {
        grid-template-columns: repeat(3, 1fr);
        gap: 3rem;
      }

      .dashboard-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    /* iPad Pro portrait mode */
    @media (min-width: 834px) and (max-width: 1366px) and (orientation: portrait) {
      .hero-section h1 {
        font-size: 2rem;
      }

      .service-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
      }

      .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  </style>
</head>
<body class="bg-white font-sans text-blue-900">
