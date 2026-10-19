(() => {
    const root = document.documentElement;
    const storageKey = 'aplikasi-parkir-theme';
    const savedTheme = localStorage.getItem(storageKey);

    if (savedTheme === 'dark') {
        root.dataset.theme = 'dark';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.querySelector('.theme-toggle');

        if (!toggle) {
            return;
        }

        const updateToggle = () => {
            const isDark = root.dataset.theme === 'dark';
            toggle.textContent = isDark ? 'Mode terang' : 'Mode malam';
            toggle.setAttribute('aria-pressed', String(isDark));
        };

        updateToggle();
        toggle.addEventListener('click', () => {
            const isDark = root.dataset.theme !== 'dark';

            if (isDark) {
                root.dataset.theme = 'dark';
            } else {
                delete root.dataset.theme;
            }

            localStorage.setItem(storageKey, isDark ? 'dark' : 'light');
            updateToggle();
        });
    });
})();
