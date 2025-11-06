<?php
// footer.php
?>
<footer class="bg-blue-900 text-blue-100 mt-20">
  <div class="bg-blue-800 text-center py-4 text-sm">© 2025 Holy Cross College - Asset Management System. All rights reserved.</div>
</footer>

<!-- Scroll-to-top -->
<button id="scrollTopBtn" class="hidden fixed bottom-6 right-6 bg-blue-900 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition transform hover:scale-110 z-50">↑</button>
<script>
  const scrollTopBtn = document.getElementById("scrollTopBtn");
  window.addEventListener("scroll", () => {
    if (window.scrollY > 200) scrollTopBtn.classList.remove("hidden");
    else scrollTopBtn.classList.add("hidden");
  });
  scrollTopBtn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
</script>

</body>
</html>
