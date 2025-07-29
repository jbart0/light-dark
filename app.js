@@ .. @@
    // Populate movie rows with proper positioning of information
    document.addEventListener('DOMContentLoaded', async () => {
      // Load movies from database first
      await loadMoviesFromDatabase();
      
+      // Load and display dynamic categories
+      await loadDynamicCategories();
+      
      // Initialize featured movie
      await initializeFeaturedMovie();

      const recommendedMovies = document.getElementById('recommended-movies');
-      const dramaMovies = document.getElementById('drama-movies');
-      const fantasyMovies = document.getElementById('fantasy-movies');
-      const crimeMovies = document.getElementById('crime-movies');

      if (recommendedMovies) {
        if (movies.length > 0) {
          recommendedMovies.innerHTML = movies.map((movie, index) => createMovieCard(movie, index)).join('');
        } else {
          recommendedMovies.innerHTML = '<div class="empty-section">Brak dostępnych filmów</div>';
        }
      }

-      if (dramaMovies) {
-        const dramaList = getMoviesByCategory('Dramat');
-        if (dramaList.length > 0) {
-          dramaMovies.innerHTML = dramaList.map((movie, index) => {
-            const originalIndex = movies.findIndex(m => m.title === movie.title);
-            return createMovieCard(movie, originalIndex);
-          }).join('');
-        } else {
-          dramaMovies.innerHTML = '<div class="empty-section">Brak filmów w tej kategorii</div>';
-        }
-      }
-
-      if (fantasyMovies) {
-        const fantasyList = getMoviesByCategory('Fantasy');
-        if (fantasyList.length > 0) {
-          fantasyMovies.innerHTML = fantasyList.map((movie, index) => {
-            const originalIndex = movies.findIndex(m => m.title === movie.title);
-            return createMovieCard(movie, originalIndex);
-          }).join('');
-        } else {
-          fantasyMovies.innerHTML = '<div class="empty-section">Brak filmów w tej kategorii</div>';
-        }
-      }
-
-      if (crimeMovies) {
-        const crimeList = getMoviesByCategory('Kryminał');
-        if (crimeList.length > 0) {
-          crimeMovies.innerHTML = crimeList.map((movie, index) => {
-            const originalIndex = movies.findIndex(m => m.title === movie.title);
-            return createMovieCard(movie, originalIndex);
-          }).join('');
-        } else {
-          crimeMovies.innerHTML = '<div class="empty-section">Brak filmów w tej kategorii</div>';
-        }
-      }
-
      // Set up movie overlay for all newly created cards on main page
      setupMovieOverlay();
@@ .. @@
      });
    });

+    // Function to load dynamic categories from database
+    async function loadDynamicCategories() {
+      try {
+        console.log('🎬 Loading categories from database...');
+        const response = await fetch('get_categories.php');
+        
+        if (!response.ok) {
+          throw new Error(`HTTP error! status: ${response.status}`);
+        }
+        
+        const data = await response.json();
+        
+        if (data.success && data.categories) {
+          console.log(`✅ Loaded ${data.categories.length} categories from database`);
+          
+          // Find the categories container
+          const categoriesContainer = document.querySelector('.categories');
+          if (!categoriesContainer) return;
+          
+          // Find existing category rows (skip watchlist and recommended)
+          const existingRows = categoriesContainer.querySelectorAll('.category-row');
+          const dynamicRows = Array.from(existingRows).slice(2); // Skip first 2 (watchlist and recommended)
+          
+          // Remove existing dynamic category rows
+          dynamicRows.forEach(row => row.remove());
+          
+          // Add new category rows for each category from database
+          for (const category of data.categories) {
+            await addCategoryRow(category, categoriesContainer);
+          }
+          
+        } else {
+          throw new Error(data.error || 'Failed to load categories');
+        }
+      } catch (error) {
+        console.error('❌ Error loading categories from database:', error);
+        showErrorMessage('Nie udało się załadować kategorii z bazy danych.');
+      }
+    }
+
+    // Function to add a category row
+    async function addCategoryRow(category, container) {
+      try {
+        // Load movies for this category
+        const response = await fetch(`get_movies_by_category.php?category_id=${category.id}`);
+        const data = await response.json();
+        
+        if (data.success && data.movies.length > 0) {
+          // Create category row HTML
+          const categoryRow = document.createElement('div');
+          categoryRow.className = 'category-row';
+          categoryRow.innerHTML = `
+            <div class="category-header">
+              <h2>${category.name}</h2>
+              <a href="Kategorie.html?category=${encodeURIComponent(category.name)}" class="view-all">Zobacz wszystkie</a>
+            </div>
+            <div class="movie-row" id="${category.name.toLowerCase().replace(/\s+/g, '-')}-movies">
+              ${data.movies.map((movie, index) => {
+                const originalIndex = movies.findIndex(m => m.title === movie.title);
+                return createMovieCard(movie, originalIndex >= 0 ? originalIndex : movies.length + index);
+              }).join('')}
+            </div>
+          `;
+          
+          container.appendChild(categoryRow);
+          
+          // Add movies to global movies array if they're not already there
+          data.movies.forEach(movie => {
+            const existingIndex = movies.findIndex(m => m.title === movie.title);
+            if (existingIndex === -1) {
+              movies.push(movie);
+            }
+          });
+          
+          console.log(`✅ Added category row for: ${category.name} (${data.movies.length} movies)`);
+        }
+      } catch (error) {
+        console.error(`❌ Error loading movies for category ${category.name}:`, error);
+      }
+    }
+
    // Global variable for overlay management
    let currentOverlay = null;
@@ .. @@