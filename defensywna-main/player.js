document.addEventListener('DOMContentLoaded', () => {
      console.log('=== PLAYER.JS STARTING ===');

      // Initialize everything once Plyr is loaded
      function initializePlayer() {
        if (typeof Plyr === 'undefined') {
          console.log('Waiting for Plyr to load...');
          setTimeout(initializePlayer, 100);
          return;
        }

        console.log('Plyr loaded, starting player initialization...');

        // Function to check user authentication with multiple methods
        function getCurrentUser() {
          console.log('🔍 Checking user authentication...');

          // Method 1: Use session object from shared.js
          if (
            typeof session !== 'undefined' &&
            typeof session.get === 'function'
          ) {
            const sessionUser = session.get();
            console.log('📋 Method 1 - session.get():', sessionUser);
            if (sessionUser && sessionUser.id) {
              console.log('✅ User found via session.get()');
              return sessionUser;
            }
          } else {
            console.log('❌ session object not available');
          }

          // Method 2: Direct cookie parsing
          console.log('🔍 Trying direct cookie parsing...');
          const cookies = document.cookie.split(';');
          console.log('🍪 All cookies:', cookies);

          let wfoSessionCookie = null;
          for (let cookie of cookies) {
            const trimmed = cookie.trim();
            if (trimmed.startsWith('wfo_session=')) {
              wfoSessionCookie = trimmed.substring('wfo_session='.length);
              break;
            }
          }

          if (wfoSessionCookie) {
            try {
              const user = JSON.parse(
                decodeURIComponent(
                  decodeURIComponent(decodeURIComponent(wfoSessionCookie))
                )
              );
              console.log('✅ User found via cookie parsing:', user);
              if (user && user.id) {
                return user;
              }
            } catch (e) {
              console.log('❌ Cookie parsing failed:', e.message);
            }
          }

          console.log('❌ No valid user found');
          return null;
        }

        // Get current user
        const currentUser = getCurrentUser();
        console.log('🎯 FINAL RESULT - currentUser:', currentUser);

        // Initialize Plyr with comprehensive configuration
        const player = new Plyr('#moviePlayer', {
          // Control bar settings
          controls: [
            'play-large', // Large play button in center
            'restart', // Restart playback
            'rewind', // Rewind by seekTime (default 10 seconds)
            'play', // Play/pause playback
            'fast-forward', // Fast forward by seekTime (default 10 seconds)
            'progress', // Progress bar
            'current-time', // Current time
            'duration', // Duration
            'mute', // Toggle mute
            'volume', // Volume control
            'captions', // Toggle captions
            'settings', // Settings menu
            'pip', // Picture-in-picture
            'airplay', // Airplay (Safari only)
            'fullscreen', // Toggle fullscreen
          ],

          // Accessibility settings
          i18n: {
            restart: 'Odtwórz od początku',
            rewind: 'Przewiń do tyłu {seektime}s',
            play: 'Odtwórz',
            pause: 'Pauza',
            fastForward: 'Przewiń do przodu {seektime}s',
            seek: 'Przewiń do {seektime}',
            seekLabel: '{currentTime} z {duration}',
            played: 'Odtworzono',
            buffered: 'Buforowano',
            currentTime: 'Aktualny czas',
            duration: 'Długość',
            volume: 'Głośność',
            mute: 'Wycisz',
            unmute: 'Włącz dźwięk',
            enableCaptions: 'Włącz napisy',
            disableCaptions: 'Wyłącz napisy',
            download: 'Pobierz',
            enterFullscreen: 'Pełny ekran',
            exitFullscreen: 'Wyjdź z pełnego ekranu',
            frameTitle: 'Odtwarzacz dla {title}',
            captions: 'Napisy',
            settings: 'Ustawienia',
            pip: 'Obraz w obrazie',
            menuBack: 'Wróć do poprzedniego menu',
            speed: 'Prędkość',
            normal: 'Normalna',
            quality: 'Jakość',
            loop: 'Zapętlenie',
            start: 'Start',
            end: 'Koniec',
            all: 'Wszystkie',
            reset: 'Zresetuj',
            disabled: 'Wyłączone',
            enabled: 'Włączone',
          },

          // Keyboard shortcuts
          keyboard: {
            focused: true, // Only when player is focused
            global: false, // Disable global shortcuts for better UX
          },

          // Tooltips
          tooltips: {
            controls: true, // Show control tooltips
            seek: true, // Show seek tooltip
          },

          // Quality settings - Plyr automatically handles multiple sources
          quality: {
            default: 1080,
            options: [1080, 720, 480, 360],
            forced: false,
            onChange: (quality) => {
              console.log(`🎥 Jakość zmieniona na: ${quality}p`);

              // Save quality preference
              if (typeof localStorage !== 'undefined') {
                localStorage.setItem('preferred_quality', quality);
              }

              // Show quality change notification
              const notification = document.createElement('div');
              notification.style.cssText = `
                        position: fixed;
                        top: 80px;
                        right: 20px;
                        background: rgba(0, 0, 0, 0.8);
                        color: white;
                        padding: 10px 15px;
                        border-radius: 5px;
                        z-index: 1000;
                        font-size: 14px;
                        border-left: 3px solid #4CAF50;
                    `;
              notification.textContent = `Jakość: ${quality}p`;
              document.body.appendChild(notification);

              setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
              }, 2000);
            },
          },

          // Speed settings
          speed: {
            selected: 1,
            options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2],
          },

          // Auto play settings (disabled for better UX)
          autoplay: false,

          // Click to play/pause
          clickToPlay: true,

          // Disable right-click context menu
          disableContextMenu: true,

          // Loading class
          loadSprite: true,

          // Seek time for rewind/fast-forward
          seekTime: 10,

          // Volume settings
          volume: 1,
          muted: false,

          // Duration display
          displayDuration: true,

          // Invert time display
          invertTime: true,

          // Toggle invert time on click
          toggleInvert: true,

          // Ratio
          ratio: '16:9',

          // Storage for user settings
          storage: {
            enabled: true,
            key: 'plyr',
          },
        });

        // Add Plyr event listeners for better control and debugging
        player.on('ready', () => {
          console.log('🎬 Plyr player is ready');

          // Set preferred quality if saved
          const savedQuality = localStorage.getItem('preferred_quality');
          if (savedQuality && player.quality) {
            setTimeout(() => {
              try {
                player.quality = parseInt(savedQuality);
                console.log(`🎥 Zastosowano zapisaną jakość: ${savedQuality}p`);
              } catch (e) {
                console.log('Could not set saved quality:', e);
              }
            }, 500);
          }
        });

        player.on('loadstart', () => {
          console.log('🔄 Video loading started');
        });

        player.on('loadeddata', () => {
          console.log('✅ Video data loaded');
        });

        player.on('canplay', () => {
          console.log('▶️ Video can start playing');
        });

        player.on('play', () => {
          console.log('🎵 Video started playing');
        });

        player.on('pause', () => {
          console.log('⏸️ Video paused');
        });

        player.on('ended', () => {
          console.log('🏁 Video ended');
        });

        player.on('error', (event) => {
          console.error('❌ Plyr error:', event);
        });

        player.on('statechange', (event) => {
          console.log('🔄 Player state changed:', event.detail.code);
        });

        // Get movie ID from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const movieId = urlParams.get('id');
        console.log('🎬 Movie ID from URL:', movieId);

        // Make sure we have access to movies array
        const moviesArray = window.movies || [];
        console.log('🎬 Available movies:', moviesArray);

        // Find movie in our movies array
        const movie = moviesArray[movieId];
        console.log('🎬 Movie details:', movie);

        if (movie) {
          // Update page content with movie details
          document.title = `${movie.title} - WFO`;
          document.getElementById('movieTitle').textContent = movie.title;
          document.getElementById('movieRating').textContent = movie.rating;
          document.getElementById('movieYear').textContent = movie.year;
          document.getElementById('movieGenre').textContent = movie.genre;
          document.getElementById('movieDescription').textContent =
            movie.description;

          console.log('🔐 Checking authentication for movie access...');
          console.log(
            '👤 Current user state:',
            currentUser ? 'LOGGED IN' : 'NOT LOGGED IN'
          );

          // Check if user is logged in first
          if (!currentUser || !currentUser.id) {
            console.log(
              '❌ User not authenticated - showing login required message'
            );
            const videoWrapper = document.querySelector('.video-player');
            videoWrapper.innerHTML = `
                    <div class="video-unavailable" style="text-align: center; padding: 60px 20px; color: #fff; background: #1a1a1a; border-radius: 10px;">
                        <div style="margin-bottom: 20px;">
                            <i data-lucide="user-x" style="width: 64px; height: 64px; color: #ff6b6b; margin-bottom: 20px;"></i>
                        </div>
                        <h3 style="margin-bottom: 15px; color: #ff6b6b;">Wymagane logowanie</h3>
                        <p style="margin-bottom: 20px; color: #ccc;">Aby obejrzeć ten film, musisz się zalogować.</p>
                        <div class="auth-buttons" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                            <a href="Logowanie.html" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #e50914; color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">
                                <i data-lucide="log-in" style="width: 20px; height: 20px;"></i>
                                <span>Zaloguj się</span>
                            </a>
                            <a href="index.html" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #333; color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">
                                <i data-lucide="home" style="width: 20px; height: 20px;"></i>
                                <span>Powrót do strony głównej</span>
                            </a>
                        </div>
                    </div>
                `;
            lucide.createIcons();
            return;
          }

          console.log('✅ User is authenticated, checking license...');
          // Check if user has license for this movie
          const userId = currentUser.id;
          console.log('👤 User ID for license check:', userId);

          // Function to check movie license using PHP
          async function checkMovieLicense(userId, movieId) {
            try {
              console.log(
                `📞 Calling license check API for user ${userId}, movie ${movieId}`
              );
              const response = await fetch(`check_license.php?id=${movieId}`);
              const data = await response.json();
              console.log('📋 License check response:', data);
              return data.hasLicense;
            } catch (error) {
              console.error('❌ Error checking movie license:', error);
              // If there's an error checking license, deny access
              return false;
            }
          }

          // Check movie license
          const licenseCheckPromise = checkMovieLicense(userId, movieId);

          licenseCheckPromise.then((hasLicense) => {
            console.log(
              '🎫 License check result:',
              hasLicense ? 'HAS LICENSE' : 'NO LICENSE'
            );

            if (hasLicense) {
              console.log('✅ User has license - setting up player');
              // Set video source using advanced Plyr API
              if (movie.videoUrl) {
                console.log('🎥 Setting up video source:', movie.videoUrl);

                // Configure comprehensive Plyr source with multiple quality options
                const sources = [];

                // Use videoSources if available, otherwise fallback to videoUrl
                if (movie.videoSources) {
                  // Add sources from videoSources object
                  if (movie.videoSources['1080p']) {
                    sources.push({
                      src: movie.videoSources['1080p'],
                      type: 'video/mp4',
                      size: 1080,
                      label: '1080p HD',
                    });
                  }
                  if (movie.videoSources['720p']) {
                    sources.push({
                      src: movie.videoSources['720p'],
                      type: 'video/mp4',
                      size: 720,
                      label: '720p',
                    });
                  }
                  if (movie.videoSources['480p']) {
                    sources.push({
                      src: movie.videoSources['480p'],
                      type: 'video/mp4',
                      size: 480,
                      label: '480p',
                    });
                  }
                  if (movie.videoSources['360p']) {
                    sources.push({
                      src: movie.videoSources['360p'],
                      type: 'video/mp4',
                      size: 360,
                      label: '360p',
                    });
                  }
                }

                // Fallback to single source if no videoSources
                if (sources.length === 0 && movie.videoUrl) {
                  sources.push({
                    src: movie.videoUrl,
                    type: 'video/mp4',
                    size: 1080,
                    label: '1080p HD',
                  });
                }

                const videoSource = {
                  type: 'video',
                  title: movie.title,
                  poster: movie.imageUrl, // Use movie poster as video poster
                  sources: sources,
                  tracks: [
                    // You can add subtitle tracks here if available
                    // {
                    //     kind: 'captions',
                    //     label: 'Polski',
                    //     srclang: 'pl',
                    //     src: 'path/to/captions.vtt',
                    //     default: true
                    // }
                  ],
                };

                console.log(
                  '🎥 Configured video sources:',
                  sources.length,
                  'quality options'
                );

                // Set the source using Plyr API
                player.source = videoSource;

                console.log('✅ Video source configured successfully');

                // Update quality indicator when quality changes
                const qualityIndicator = document.getElementById(
                  'qualityIndicator'
                );
                if (qualityIndicator && sources.length > 1) {
                  qualityIndicator.style.display = 'block';
                  qualityIndicator.textContent = player.quality + 'p';
                }

                // Listen for quality changes to update indicator
                player.on('qualitychange', (event) => {
                  console.log('Quality changed to:', event.detail.quality);
                  if (qualityIndicator) {
                    qualityIndicator.textContent = event.detail.quality + 'p';
                  }
                });

                // Log player state for debugging after a short delay
                setTimeout(() => {
                  console.log(
                    '🎬 Player ready state:',
                    player.media?.readyState || 'Not ready'
                  );
                  console.log('🎬 Player current source:', player.source);
                  console.log('🎬 Player duration:', player.duration || 'Unknown');
                  console.log('🎬 Player type:', player.type);
                }, 1000);
              } else {
                console.log('❌ No video URL available for this movie');
                const videoWrapper = document.querySelector('.video-player');
                videoWrapper.innerHTML = `
                            <div class="video-unavailable" style="text-align: center; padding: 60px 20px; color: #fff; background: #1a1a1a; border-radius: 10px;">
                                <h3 style="margin-bottom: 15px; color: #ff6b6b;">Film niedostępny</h3>
                                <p style="margin-bottom: 10px; color: #ccc;">Brak URL wideo dla filmu "${movie.title}"</p>
                                <p style="color: #888; font-size: 14px;">Sprawdź konfigurację źródła wideo.</p>
                            </div>
                        `;
              }
            } else {
              console.log(
                '❌ User needs to purchase license - showing payment options'
              );
              // Show payment required message and button
              const videoWrapper = document.querySelector('.video-player');
              videoWrapper.innerHTML = `
                        <div class="video-unavailable" style="text-align: center; padding: 60px 20px; color: #fff; background: #1a1a1a; border-radius: 10px;">
                            <div style="margin-bottom: 20px;">
                                <i data-lucide="lock" style="width: 64px; height: 64px; color: #ffa500; margin-bottom: 20px;"></i>
                            </div>
                            <h3 style="margin-bottom: 15px; color: #ffa500;">Brak licencji</h3>
                            <p style="margin-bottom: 10px; color: #ccc;">Aby obejrzeć film "<strong>${
                              movie.title
                            }</strong>", musisz wykupić dostęp.</p>
                            <p style="margin-bottom: 20px; color: #888; font-size: 14px;">Film jest dostępny w ramach płatnej subskrypcji.</p>
                            <div class="payment-options" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                                <button class="btn btn-primary" id="purchaseButton" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #ffa500; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;">
                                    <i data-lucide="credit-card" style="width: 20px; height: 20px;"></i>
                                    <span>Wykup dostęp</span>
                                </button>
                                <button class="btn btn-secondary" onclick="window.location.href='Filmy.html'" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #333; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;">
                                    <i data-lucide="arrow-left" style="width: 20px; height: 20px;"></i>
                                    <span>Powrót do filmów</span>
                                </button>
                                <a href="biuro_licencje.html" class="btn btn-primary">Zgłoś się po licencję</a>
                            </div>
                        </div>
                    `;

              // Add event listener to the purchase button
              const purchaseButton = document.getElementById('purchaseButton');
              if (purchaseButton) {
                purchaseButton.addEventListener('click', () => {
                  console.log('💸 Purchase button clicked');
                  // Create form and submit to startPaymentExample.php
                  const form = document.createElement('form');
                  form.action = 'startPaymentExample.php';
                  form.method = 'POST';
                  form.style.display = 'none';

                  const movieIdInput = document.createElement('input');
                  movieIdInput.type = 'hidden';
                  movieIdInput.name = 'movieId';
                  movieIdInput.value = movieId;
                  form.appendChild(movieIdInput);

                  document.body.appendChild(form);
                  form.submit();
                });
              }

              console.log('💳 Payment options displayed');
            }
            lucide.createIcons();
          });
        } else {
          // Show error message if movie not found
          console.log('❌ Movie not found');
          const videoWrapper = document.querySelector('.video-player');
          videoWrapper.innerHTML = `
                <div class="video-unavailable">
                    <p>Film nie został znaleziony</p>
                    <p>ID filmu: ${movieId}</p>
                    <a href="index.html" class="btn btn-primary">Powrót do strony głównej</a>
                </div>
            `;
        }

        // Initialize Lucide icons
        lucide.createIcons();

        // Add global access to player for debugging
        window.debugPlayer = player;
        window.testPlayer = () => {
          console.log('=== PLAYER DEBUG INFO ===');
          console.log('Player object:', player);
          console.log('Player type:', player.type);
          console.log('Player source:', player.source);
          console.log('Player duration:', player.duration);
          console.log('Player current time:', player.currentTime);
          console.log('Player ready state:', player.media?.readyState);
          console.log('Player paused:', player.paused);
          console.log('Player muted:', player.muted);
          console.log('Player volume:', player.volume);
          console.log('========================');
          return player;
        };

        console.log(
          '🎬 Player initialization complete! Use window.testPlayer() for debugging.'
        );
      }

      // Start the initialization
      initializePlayer();
    });
