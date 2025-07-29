@@ .. @@
// Initialize shared elements
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    lucide.createIcons();

+    // Initialize theme switcher
+    initializeThemeSwitcher();
+
    // Update auth button
    updateAuthUI();
@@ .. @@
    window.testCookieDecoding();
};
+
+// Theme Switcher Functions
+function initializeThemeSwitcher() {
+    const themeToggle = document.getElementById('theme-toggle');
+    const themeStylesheet = document.getElementById('theme-stylesheet');
+    
+    if (!themeToggle) return;
+    
+    // Get saved theme preference or default to dark
+    const savedTheme = localStorage.getItem('theme') || 'dark';
+    
+    // Apply saved theme
+    applyTheme(savedTheme);
+    
+    // Set toggle state based on saved theme
+    themeToggle.checked = savedTheme === 'light';
+    
+    // Add event listener for theme toggle
+    themeToggle.addEventListener('change', function() {
+        const newTheme = this.checked ? 'light' : 'dark';
+        applyTheme(newTheme);
+        localStorage.setItem('theme', newTheme);
+    });
+}
+
+function applyTheme(theme) {
+    const themeStylesheet = document.getElementById('theme-stylesheet');
+    
+    if (!themeStylesheet) {
+        // If no theme stylesheet link exists, create one
+        const link = document.createElement('link');
+        link.rel = 'stylesheet';
+        link.id = 'theme-stylesheet';
+        document.head.appendChild(link);
+    }
+    
+    const stylesheet = document.getElementById('theme-stylesheet');
+    
+    if (theme === 'light') {
+        stylesheet.href = 'styles_light.css';
+    } else {
+        stylesheet.href = 'style.css';
+    }
+    
+    // Update body class for additional styling if needed
+    document.body.className = document.body.className.replace(/theme-\w+/g, '');
+    document.body.classList.add(`theme-${theme}`);
+}
+
+// Make theme functions globally available
+window.initializeThemeSwitcher = initializeThemeSwitcher;
+window.applyTheme = applyTheme;