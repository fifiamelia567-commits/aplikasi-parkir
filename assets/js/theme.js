(() => {
    const root = document.documentElement;
    const storageKey = 'aplikasi-parkir-theme';

    // localStorage bisa tidak tersedia (mode privat / cookie dinonaktifkan)
    const safeStorage = {
        get() {
            try {
                return localStorage.getItem(storageKey);
            } catch (e) {
                return null;
            }
        },
        set(value) {
            try {
                localStorage.setItem(storageKey, value);
            } catch (e) {
                // Abaikan: tema tetap berlaku untuk sesi ini
            }
        }
    };

    const savedTheme = safeStorage.get();

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

            safeStorage.set(isDark ? 'dark' : 'light');
            updateToggle();
        });
    });
})();
