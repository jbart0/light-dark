console.log("auth.js załadowany");

document.addEventListener('DOMContentLoaded', () => {
    const authForm = document.getElementById('authForm');
    const switchButton = document.getElementById('switchAuth');
    const nameGroup = document.getElementById('nameGroup');
    const errorMessage = document.getElementById('errorMessage');
    const recaptchaGroup = document.getElementById('recaptchaGroup');

    // Get return URL from query parameters if it exists
    const urlParams = new URLSearchParams(window.location.search);
    const returnUrl = urlParams.get('returnUrl') || 'index.html';

    switchButton.addEventListener('click', () => {
        const isHidden = nameGroup.classList.contains('hidden');

        if (isHidden) {
            nameGroup.classList.remove('hidden');
            recaptchaGroup.classList.remove('hidden');
        } else {
            nameGroup.classList.add('hidden');
            recaptchaGroup.classList.add('hidden');
        }

        document.querySelector('.auth-title').textContent = isHidden
            ? 'Stwórz swoje konto'
            : 'Witaj ponownie';

        document.querySelector('.auth-subtitle').textContent = isHidden
            ? 'Zacznij swoją przygodę z wypożyczalnią filmów wfo'
            : 'Zaloguj się do konta';

        document.querySelector('.auth-submit').textContent = isHidden
            ? 'Stwórz konto'
            : 'Zaloguj się';

        switchButton.textContent = isHidden
            ? 'Masz już konto? Zaloguj'
            : 'Nie masz konta? Zarejestruj się!';

        errorMessage.style.display = 'none';

        // Reset reCAPTCHA when switching forms
        if (typeof grecaptcha !== 'undefined') {
            grecaptcha.reset();

            // If switching to registration (showing reCAPTCHA), force re-render
            if (isHidden) {
                setTimeout(() => {
                    try {
                        console.log('Attempting to render reCAPTCHA...');
                        const recaptchaContainer = document.querySelector('.g-recaptcha');
                        if (recaptchaContainer && !recaptchaContainer.hasChildNodes()) {
                            grecaptcha.render(recaptchaContainer, {
                                'sitekey': '6Lejx1UrAAAAAM0_WVUuazT48lk_bpyICn68s94V',
                                'callback': 'onRecaptchaCallback',
                                'expired-callback': 'onRecaptchaExpired'
                            });
                            console.log('reCAPTCHA rendered successfully');
                        } else {
                            console.log('reCAPTCHA container already has content or not found');
                        }
                    } catch (error) {
                        console.error('Error rendering reCAPTCHA:', error);
                    }
                }, 100);
            }
        }
    });

    // Email validation function
    async function validateEmail(email) {
        try {
            const response = await fetch('validate_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ email })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Error validating email:', error);

            // Fallback to client-side validation if server validation fails
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                return { valid: false, error: 'Nieprawidłowy format adresu email' };
            }

            const domain = email.split('@')[1].toLowerCase();
            const disposableDomains = [
                'tempmail.com', 'temp-mail.org', 'guerrillamail.com', 'throwawaymail.com',
                'yopmail.com', 'mailinator.com', '10minutemail.com', 'trashmail.com',
                'sharklasers.com', 'guerrillamail.info', 'grr.la', 'maildrop.cc',
                'getairmail.com', 'getnada.com', 'emailondeck.com', 'tempmail.net',
                'dispostable.com', 'tempmailaddress.com', 'emailfake.com', 'fakeinbox.com'
            ];

            if (disposableDomains.includes(domain)) {
                return { valid: false, error: 'Tymczasowe adresy email nie są dozwolone' };
            }

            return { valid: true, error: '' };
        }
    }

    authForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorMessage.style.display = 'none';

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginInput = document.getElementById('login');
        const isSignUp = !nameGroup.classList.contains('hidden');

        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        let login = isSignUp ? loginInput.value.trim() : '';
        let recaptchaResponse = '';

        // Password validation for registration
        if (isSignUp) {
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            if (!passwordRegex.test(password)) {
                errorMessage.textContent = 'Hasło musi zawierać minimum 8 znaków, w tym jedną dużą literę, jedną małą literę oraz jedną cyfrę';
                errorMessage.style.display = 'block';
                return;
            }

            if (!login) {
                errorMessage.textContent = 'Login jest wymagany';
                errorMessage.style.display = 'block';
                return;
            }

            if (!email) {
                errorMessage.textContent = 'Email jest wymagany';
                errorMessage.style.display = 'block';
                return;
            }

            // Validate email for registration
            const emailValidation = await validateEmail(email);
            if (!emailValidation.valid) {
                errorMessage.textContent = emailValidation.error;
                errorMessage.style.display = 'block';
                return;
            }

            // Check if reCAPTCHA API is loaded
            if (typeof grecaptcha === 'undefined') {
                errorMessage.textContent = 'reCAPTCHA nie została załadowana. Odśwież stronę.';
                errorMessage.style.display = 'block';
                return;
            }

            // Debug reCAPTCHA widget state
            const recaptchaContainer = document.querySelector('.g-recaptcha');
            console.log('reCAPTCHA container found:', !!recaptchaContainer);
            console.log('reCAPTCHA container visible:', recaptchaContainer ? !recaptchaContainer.parentElement.classList.contains('hidden') : false);
            console.log('reCAPTCHA container has children:', recaptchaContainer ? recaptchaContainer.children.length : 0);

            // Check if reCAPTCHA is completed
            try {
                recaptchaResponse = grecaptcha.getResponse();
                console.log('Global reCAPTCHA response available:', window.lastRecaptchaResponse ? 'YES' : 'NO');

                // If grecaptcha.getResponse() is empty but we have a stored response, use it
                if (!recaptchaResponse && window.lastRecaptchaResponse) {
                    console.log('Using stored reCAPTCHA response');
                    recaptchaResponse = window.lastRecaptchaResponse;
                }
            } catch (error) {
                console.error('Error getting reCAPTCHA response:', error);
                recaptchaResponse = '';
            }

            console.log('reCAPTCHA API loaded:', typeof grecaptcha !== 'undefined');
            console.log('reCAPTCHA response length:', recaptchaResponse ? recaptchaResponse.length : 0);
            console.log('reCAPTCHA response preview:', recaptchaResponse ? recaptchaResponse.substring(0, 50) + '...' : 'EMPTY');

            if (!recaptchaResponse) {
                errorMessage.textContent = 'Potwierdź, że nie jesteś robotem.';
                errorMessage.style.display = 'block';
                return;
            }
        }

        console.log("Wysyłam dane logowania:", { email, password, login });

        const url = isSignUp ? 'register.php' : 'login.php';

        try {
            // Prepare form data
            const formData = { email, password };

            if (isSignUp) {
                formData.login = login;
                // Add reCAPTCHA response for registration
                formData.recaptcha_response = recaptchaResponse;
                console.log('Sending reCAPTCHA response:', recaptchaResponse ? 'YES (' + recaptchaResponse.length + ' chars)' : 'NO');
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(formData),
            });

            const result = await response.json();

            if (!response.ok || result.error) {
                throw new Error(result.error || 'Wystąpił błąd');
            }

            if (isSignUp) {
                const toast = document.getElementById('toast');
                toast.textContent = "Rejestracja zakończona sukcesem! Zaloguj się.";
                toast.style.display = "block";
                toast.classList.add('show');

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toast.style.display = "none";
                        // Reset reCAPTCHA before redirecting
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                        window.location.href = 'Logowanie.html';
                    }, 300);
                }, 2500);

                return;
            }

            // Login successful
            const toast = document.getElementById('toast');
            if (toast) {
                toast.textContent = "Jesteś zalogowany";
                toast.style.display = "block";
                toast.classList.add("show");

                setTimeout(() => {
                    toast.classList.remove("show");
                    setTimeout(() => {
                        toast.style.display = "none";

                        // Save session before redirecting
                        session.set(result.user);

                        // Redirect to return URL or home page
                        window.location.href = returnUrl;
                    }, 300);
                }, 2500);
            }

            // Update session data
            session.set(result.user);

        } catch (error) {
            console.error("Błąd logowania:", error);
            errorMessage.textContent = error.message;
            errorMessage.style.display = 'block';

            // Reset reCAPTCHA on error during registration
            if (isSignUp && typeof grecaptcha !== 'undefined') {
                grecaptcha.reset();
            }
        }
    });

    // Validate reCAPTCHA function
    async function validateRecaptcha(response) {
        try {
            const recaptchaResponse = await fetch('validate_recaptcha.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ recaptcha_response: response })
            });

            if (!recaptchaResponse.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await recaptchaResponse.json();
            return result;
        } catch (error) {
            console.error('Error validating reCAPTCHA:', error);
            return { success: false };
        }
    }

    // Check if user is already logged in
    const currentUser = session.get();
    const isOnLoginPage = window.location.pathname.includes('Logowanie.html');
    if (currentUser && isOnLoginPage) {
        window.location.href = returnUrl;
    }
});
