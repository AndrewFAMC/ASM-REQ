<?php
// header.php
?>
<nav class="bg-white shadow-md fixed w-full top-0 left-0 z-50">
  <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
    <!-- Logo -->
    <div class="flex items-center space-x-4">
      <img src="logo/1.png" alt="HCC Crest" class="h-12 w-12 object-contain" />
      <img src="logo/2.png" alt="Holy Cross College" class="h-10 object-contain" />
    </div>

    <!-- Desktop/Tablet Menu -->
    <div class="hidden md:flex items-center space-x-6 tablet-nav">
      <a href="index.php" class="text-blue-900 font-semibold hover:text-blue-700 transition duration-200 px-3 py-2 rounded-md">Home</a>

      <a href="about.php" class="text-blue-900 font-semibold hover:text-blue-700 transition duration-200 px-3 py-2 rounded-md">About</a>

      <a href="it_support/index.php" class="text-blue-900 font-semibold hover:text-blue-700 transition duration-200 px-3 py-2 rounded-md">Contact / Support</a>
    </div>

    <!-- Mobile Menu Button (for smaller screens) -->
    <div class="md:hidden">
      <button id="mobile-menu-btn" class="text-blue-900 p-2 touch-target">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>
  </div>
</nav>

<script>
  // Mobile menu toggle
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const tabletNav = document.querySelector('.tablet-nav');

  mobileMenuBtn.addEventListener('click', () => {
    tabletNav.classList.toggle('hidden');
  });

  const lb = document.getElementById('loginBtn');
  if(lb){
    lb.addEventListener('click', () => {
      Swal.fire({ title: 'Redirecting...', icon: 'info', showConfirmButton:false, timer:1200 })
        .then(()=> window.location.href = 'login.php');
    });
  }
</script>
