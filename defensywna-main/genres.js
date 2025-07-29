// Genres page functionality
document.addEventListener('DOMContentLoaded', () => {
    const genresGrid = document.querySelector('.genres-grid');

    // Get unique genres and count movies in each
    const genreCounts = movies.reduce((acc, movie) => {
        movie.genres.forEach(genre => {
            acc[genre] = (acc[genre] || 0) + 1;
        });
        return acc;
    }, {});

    // Create genre cards
    genresGrid.innerHTML = Object.entries(genreCounts)
        .map(([genre, count]) => `
            <a href="/pages/movies.html?genre=${genre.toLowerCase()}" 
               class="genre-card">
                <div class="genre-card-content">
                    <h3>${genre}</h3>
                    <p>${count} ${count === 1 ? 'movie' : 'movies'}</p>
                </div>
            </a>
        `)
        .join('');
});
