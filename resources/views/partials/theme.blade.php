<script>
    (function () {
        const migrateFluxAppearance = () => {
            if (localStorage.getItem('dark-theme') !== null) {
                return;
            }

            const fluxAppearance = localStorage.getItem('flux.appearance');

            if (fluxAppearance && ['light', 'dark', 'system'].includes(fluxAppearance)) {
                localStorage.setItem('dark-theme', fluxAppearance);
            }
        };

        const resolveMode = (storage) => {
            if (storage === 'true') {
                return 'dark';
            }

            if (storage === 'false') {
                return 'light';
            }

            if (['light', 'dark', 'system'].includes(storage)) {
                return storage;
            }

            return 'light';
        };

        const shouldUseDark = (mode) => {
            if (mode === 'dark') {
                return true;
            }

            if (mode === 'light') {
                return false;
            }

            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        };

        window.applyTallStackTheme = () => {
            migrateFluxAppearance();

            const mode = resolveMode(localStorage.getItem('dark-theme'));
            document.documentElement.classList.toggle('dark', shouldUseDark(mode));
        };

        window.applyTallStackTheme();

        document.addEventListener('livewire:navigating', (event) => {
            event.detail.onSwap(() => window.applyTallStackTheme());
        });
    })();
</script>
