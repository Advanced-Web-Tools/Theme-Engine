(() => {
    function initializeAppearanceSync() {
        if (!document.body.classList.contains('theme-preview-window')) return;

        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
        const applyPreviewTheme = () => {
            let preference = null;
            try { preference = localStorage.getItem('dashboard-theme'); } catch (_) {}
            document.documentElement.dataset.theme = preference === 'dark' || preference === 'light'
                ? preference : (systemTheme.matches ? 'dark' : 'light');
        };
        window.addEventListener('storage', event => {
            if (event.key === 'dashboard-theme' || event.key === null) applyPreviewTheme();
        });
        systemTheme.addEventListener('change', applyPreviewTheme);
        applyPreviewTheme();
    }

    function initializeImageFallbacks() {
        const fallbackImage = image => {
            if (!(image instanceof HTMLImageElement) || !image.hasAttribute('data-preview-fallback')) return;
            const fallback = new URL(image.dataset.previewFallback, document.baseURI).href;
            if (image.dataset.fallbackApplied === 'true' || image.src === fallback) {
                image.hidden = true;
                return;
            }
            image.dataset.fallbackApplied = 'true';
            image.src = fallback;
        };
        document.addEventListener('error', event => fallbackImage(event.target), true);
        document.querySelectorAll('img[data-preview-fallback]').forEach(image => {
            // An image may have failed before this deferred script loaded.
            if (image.complete && image.naturalWidth === 0) fallbackImage(image);
        });
    }

    function initializePreview() {
        const previewDevice = document.getElementById('theme-preview-device');
        const openLink = document.getElementById('theme-preview-open');
        let currentDevice = previewDevice?.dataset.initialDevice || 'desktop';

        if (openLink) {
            document.querySelectorAll('form.theme-route').forEach(form => {
                form.addEventListener('submit', () => {
                    const url = new URL(form.action, window.location.href);
                    url.search = new URLSearchParams(new FormData(form)).toString();
                    url.searchParams.set('window', '1');
                    url.searchParams.set('device', currentDevice);
                    openLink.href = url.href;
                    openLink.removeAttribute('aria-disabled');
                    openLink.removeAttribute('tabindex');
                });
            });
        }
        if (!previewDevice) return;

        const previewSize = document.getElementById('theme-preview-size');
        const devices = {desktop: ['100%', '100%'], tablet: ['768px', '1024px'], mobile: ['390px', '844px']};
        const frame = previewDevice.querySelector('iframe');
        const buttons = [...document.querySelectorAll('[data-preview-device]')];
        const restrictedScreen = window.matchMedia('(max-width: 1024px), (pointer: coarse)');

        const updateSize = () => {
            previewSize.textContent = `${frame.clientWidth} × ${frame.clientHeight}`;
        };
        const updateOpenLink = () => {
            if (!openLink || openLink.getAttribute('aria-disabled') === 'true') return;
            const url = new URL(openLink.href, window.location.href);
            url.searchParams.set('window', '1');
            url.searchParams.set('device', currentDevice);
            openLink.href = url.href;
        };
        const applyDevice = device => {
            currentDevice = device;
            const [width, height] = devices[currentDevice];
            previewDevice.style.setProperty('--preview-width', width);
            previewDevice.style.setProperty('--preview-height', height);
            updateOpenLink();
            buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.previewDevice === currentDevice)));
            updateSize();
        };
        const updateScreen = () => {
            buttons.forEach(button => {
                button.disabled = restrictedScreen.matches;
                button.title = restrictedScreen.matches ? 'Preview follows your screen on tablets and phones.' : '';
            });
            applyDevice(restrictedScreen.matches ? 'desktop' : currentDevice);
        };
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                if (!restrictedScreen.matches) applyDevice(button.dataset.previewDevice);
            });
        });
        restrictedScreen.addEventListener('change', updateScreen);
        window.addEventListener('resize', updateSize);
        new ResizeObserver(updateSize).observe(frame);
        updateScreen();
    }

    function initializeUnsavedChanges() {
        const indicator = document.getElementById('theme-unsaved-indicator');
        const actions = indicator?.querySelector('[data-unsaved-actions]');
        const trackedOperations = new Set(['settings', 'layout', 'page', 'preset_save']);
        const snapshots = new Map();
        const dirtyForms = new Set();
        const saveButtons = new Map();
        let submittingForm = null;

        const snapshot = form => JSON.stringify([...new FormData(form).entries()]);
        const updateForm = form => {
            if (snapshot(form) === snapshots.get(form)) dirtyForms.delete(form);
            else dirtyForms.add(form);
            form.classList.toggle('has-unsaved-changes', dirtyForms.has(form));
            const saveButton = saveButtons.get(form);
            if (saveButton) saveButton.hidden = !dirtyForms.has(form);
            if (indicator) indicator.hidden = dirtyForms.size === 0;
        };
        document.querySelectorAll('form[method="post"]').forEach(form => {
            const operation = form.querySelector('input[name="operation"]')?.value;
            if (trackedOperations.has(operation)) {
                snapshots.set(form, snapshot(form));
                const submitter = form.querySelector('button[type="submit"], input[type="submit"]');
                if (actions && submitter) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-primary';
                    button.hidden = true;
                    button.textContent = operation === 'page'
                        ? `Save ${form.querySelector('h2')?.textContent.trim() || 'page layout'}`
                        : (submitter.textContent.trim() || submitter.value || 'Save');
                    // Submit the original form so validation, CSRF, and theme context are preserved.
                    button.addEventListener('click', () => form.requestSubmit(submitter));
                    actions.append(button);
                    saveButtons.set(form, button);
                }
                form.addEventListener('input', () => updateForm(form));
                form.addEventListener('change', () => updateForm(form));
                form.addEventListener('reset', () => queueMicrotask(() => updateForm(form)));
            }
            form.addEventListener('submit', event => {
                queueMicrotask(() => {
                    if (!event.defaultPrevented) submittingForm = form;
                });
            });
        });
        window.addEventListener('beforeunload', event => {
            // Saving one form must still protect changes pending in the other forms.
            if (![...dirtyForms].some(form => form !== submittingForm)) return;
            event.preventDefault();
            event.returnValue = '';
        });
        window.addEventListener('pageshow', () => {
            submittingForm = null;
            snapshots.forEach((_, form) => updateForm(form));
        });
    }

    initializeAppearanceSync();
    initializeImageFallbacks();
    initializePreview();
    initializeUnsavedChanges();
})();
