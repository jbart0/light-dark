document.body.addEventListener('keydown', function(e) {
    const forbiddenKeys = [
        { ctrl: true, shift: true, keyCode: 73 },  // Ctrl+Shift+I
        { ctrl: true, shift: true, keyCode: 75 },  // Ctrl+Shift+K
        { ctrl: true, shift: true, keyCode: 67 },  // Ctrl+Shift+C
        { ctrl: true, shift: true, keyCode: 74 },  // Ctrl+Shift+J
    ];

    if (e.which === 123) { // F12
        e.preventDefault();
    }

    for (let combo of forbiddenKeys) {
        if (e.ctrlKey === combo.ctrl && e.shiftKey === combo.shift && e.which === combo.keyCode) {
            e.preventDefault();
            break;
        }
    }
});

// Funkcja wykrywająca otwarcie DevTools i przekierowująca
(function detectDevTools(allow = 100) {
    function check() {
        const start = performance.now();
        debugger;
        const end = performance.now();

        if (isNaN(start) || isNaN(end) || end - start > allow) {
            // Wykryto DevTools — przekieruj
            window.location.href = "https://www.youtube.com/watch?v=xvFZjo5PgG0&ab_channel=Duran";
        }
    }

    function attachEvents() {
        window.addEventListener('resize', check);
        window.addEventListener('mousemove', check);
        window.addEventListener('focus', check);
        window.addEventListener('blur', check);
    }

    if (document.readyState === "complete" || document.readyState === "interactive") {
        check();
        attachEvents();
    } else {
        window.addEventListener('load', () => {
            check();
            attachEvents();
        });
    }
})();
