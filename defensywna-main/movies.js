// Movies page functionality
document.addEventListener('DOMContentLoaded', () => {
    const moviesGrid = document.querySelector('.movies-grid');
    const genreFilter = document.querySelector('.genre-filter');
    const sortSelect = document.querySelector('.sort-select');

    let currentGenre = null;
    let currentSort = 'popularity';

    // Initialize genre filters
    const genres = [...new Set(movies.flatMap(movie => movie.genres))];
    genreFilter.innerHTML = `
        <button class="genre-tag active" data-genre="all">All</button>
        ${genres.map(genre => `
            <button class="genre-tag" data-genre="${genre}">${genre}</button>
        `).join('')}
    `;

    // Filter and sort movies
    function updateMovies() {
        let filtered = [...movies];
        
        // Apply genre filter
        if (currentGenre && currentGenre !== 'all') {
            filtered = filtered.filter(movie => movie.genres.includes(currentGenre));
        }

        // Apply sorting
        filtered.sort((a, b) => {
            switch (currentSort) {
                case 'rating':
                    return b.rating - a.rating;
                case 'title':
                    return a.title.localeCompare(b.title);
                case 'newest':
                    return b.releaseYear - a.releaseYear;
                case 'oldest':
                    return a.releaseYear - b.releaseYear;
                default:
                    return b.rating - a.rating;
            }
        });

        // Update grid
        moviesGrid.innerHTML = filtered.map(movie => `
            <div class="movie-card">
                <img src="${movie.imageUrl}" alt="${movie.title}">
                <div class="movie-card-content">
                    <h3 class="movie-card-title">${movie.title}</h3>
                    <div class="movie-card-meta">
                        <span>${movie.releaseYear}</span>
                        <span>•</span>
                        <span>$${(movie.rentalPrice / 100).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Event listeners
    genreFilter.addEventListener('click', (e) => {
        if (e.target.classList.contains('genre-tag')) {
            document.querySelectorAll('.genre-tag').forEach(tag => 
                tag.classList.remove('active'));
            e.target.classList.add('active');
            currentGenre = e.target.dataset.genre;
            updateMovies();
        }
    });

    sortSelect.addEventListener('change', (e) => {
        currentSort = e.target.value;
        updateMovies();
    });

    // Initial render
    updateMovies();
});
