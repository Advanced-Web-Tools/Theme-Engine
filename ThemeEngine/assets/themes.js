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
            if (previewSize) previewSize.textContent = `${frame.clientWidth} × ${frame.clientHeight}`;
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
                const label = button.getAttribute('aria-label') || `${button.dataset.previewDevice[0].toUpperCase()}${button.dataset.previewDevice.slice(1)} preview`;
                button.title = restrictedScreen.matches
                    ? `${label}: Preview follows your screen on tablets and phones.` : label;
            });
            applyDevice(restrictedScreen.matches ? 'desktop' : currentDevice);
        };
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                if (!restrictedScreen.matches) applyDevice(button.dataset.previewDevice);
            });
        });
        restrictedScreen.addEventListener('change', updateScreen);
        if (previewSize) {
            window.addEventListener('resize', updateSize);
            new ResizeObserver(updateSize).observe(frame);
        }
        updateScreen();
    }

    function initializePreviewSettings() {
        const popup = document.getElementById('theme-preview-settings-popup');
        const toggle = document.getElementById('theme-preview-settings-toggle');
        if (!popup || !toggle) return;

        const body = popup.querySelector('[data-preview-settings-body]');
        const status = popup.querySelector('[role="status"]');
        const frame = document.querySelector('.theme-preview-device iframe');
        const snapshots = new WeakMap();
        let activeTab = 'settings';
        let loaded = false;
        let busy = false;
        const snapshot = form => JSON.stringify([...new FormData(form).entries()]);
        const formKey = form => ['operation', 'page_key', 'preset_id']
            .map(name => form.elements.namedItem(name)?.value || '').join(':');
        const dirtyForms = () => [...body.querySelectorAll('form')]
            .filter(form => snapshots.has(form) && snapshot(form) !== snapshots.get(form));
        const selectTab = (tab, focus = false) => {
            activeTab = tab;
            body.querySelectorAll('[data-preview-tab]').forEach(button => {
                const selected = button.dataset.previewTab === tab;
                button.setAttribute('aria-selected', String(selected));
                button.classList.toggle('active', selected);
                button.tabIndex = selected ? 0 : -1;
                if (selected && focus) button.focus();
            });
            body.querySelectorAll('[data-preview-panel]').forEach(panel => {
                panel.hidden = panel.dataset.previewPanel !== tab;
            });
        };
        const syncCurrentPage = () => {
            const routes = body.querySelector('[data-preview-page-routes]');
            if (!routes) return;
            let pageKey = null;
            try {
                const url = new URL(frame.contentWindow.location.href);
                const previewUrl = new URL(frame.src, window.location.href);
                if (url.origin === previewUrl.origin && url.pathname === previewUrl.pathname) {
                    const route = url.searchParams.get('route') || '0';
                    pageKey = JSON.parse(routes.dataset.previewPageRoutes)[route] ?? null;
                }
            } catch (_) { /* Pages outside the theme have no page layout controls. */ }
            body.querySelectorAll('[data-preview-page-key]').forEach(form => {
                form.hidden = form.dataset.previewPageKey !== pageKey;
            });
            const note = body.querySelector('[data-preview-page-note]');
            if (note) note.hidden = pageKey !== null;
        };
        frame.addEventListener('load', syncCurrentPage);
        const install = (html, preserved = []) => {
            const template = document.createElement('template');
            template.innerHTML = html;
            if (!template.content.querySelector('[data-preview-panel="settings"]')) {
                throw new Error('Unable to load theme settings. Reload the preview and sign in if needed.');
            }
            // Saving one form must not discard edits in another tab or page override.
            template.content.querySelectorAll('form').forEach(form => {
                const previous = preserved.find(candidate => formKey(candidate) === formKey(form));
                if (previous) form.replaceWith(previous);
                else snapshots.set(form, snapshot(form));
            });
            body.replaceChildren(template.content);
            selectTab(activeTab);
            syncCurrentPage();
            loaded = true;
        };
        const request = async options => {
            const response = await fetch(popup.dataset.settingsUrl + (options ? '/save' : ''), {
                credentials: 'same-origin', ...options,
            });
            if (response.redirected) throw new Error('Reload the preview and sign in to edit theme settings.');
            const html = await response.text();
            if (!response.ok) throw new Error(html || 'Unable to save theme settings.');
            return html;
        };
        const setOpen = async open => {
            popup.classList.toggle('is-open', open);
            popup.inert = !open;
            toggle.setAttribute('aria-expanded', String(open));
            if (!open) {
                if (popup.contains(document.activeElement)) toggle.focus();
                return;
            }
            if (loaded) {
                selectTab(activeTab, true);
                return;
            }
            if (busy) return;
            busy = true;
            popup.setAttribute('aria-busy', 'true');
            status.textContent = 'Loading theme settings…';
            try {
                install(await request());
                status.textContent = '';
                if (toggle.getAttribute('aria-expanded') === 'true') selectTab(activeTab, true);
            } catch (error) {
                status.textContent = error.message;
            } finally {
                busy = false;
                popup.removeAttribute('aria-busy');
            }
        };
        toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
        popup.querySelector('[data-preview-settings-close]').addEventListener('click', () => setOpen(false));
        document.addEventListener('click', event => {
            if (!popup.contains(event.target) && !toggle.contains(event.target)) setOpen(false);
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });
        body.addEventListener('click', event => {
            const tab = event.target.closest('[data-preview-tab]');
            if (tab) {
                event.preventDefault();
                selectTab(tab.dataset.previewTab);
            }
        });
        body.addEventListener('keydown', event => {
            if (!event.target.matches('[data-preview-tab]')) return;
            const tabs = [...body.querySelectorAll('[data-preview-tab]')];
            const index = tabs.indexOf(event.target);
            const next = {ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length,
                Home: 0, End: tabs.length - 1}[event.key];
            if (next === undefined) return;
            event.preventDefault();
            selectTab(tabs[next].dataset.previewTab, true);
        });
        const updateDirty = event => {
            const form = event.target.closest('form');
            if (form) form.classList.toggle('has-unsaved-changes', snapshot(form) !== snapshots.get(form));
            if (!busy) status.textContent = dirtyForms().length
                ? 'Unsaved changes' : '';
        };
        body.addEventListener('input', updateDirty);
        body.addEventListener('change', updateDirty);
        body.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy) return;
            const form = event.target;
            const operation = form.elements.namedItem('operation').value;
            const replaces = candidate => operation === 'preset_apply'
                || (operation === 'reset_settings' && candidate.elements.namedItem('operation')?.value === 'settings');
            if (dirtyForms().some(replaces) && !window.confirm('This will replace your unsaved changes. Continue?')) return;
            const submitted = snapshot(form);
            const data = new URLSearchParams(new FormData(form));
            busy = true;
            body.inert = true;
            popup.setAttribute('aria-busy', 'true');
            status.textContent = 'Saving…';
            const buttons = [...body.querySelectorAll('button[type="submit"]')];
            buttons.forEach(button => { button.disabled = true; });
            try {
                const html = await request({method: 'POST', body: data});
                const preserved = dirtyForms().filter(candidate => !replaces(candidate)
                    && (candidate !== form || snapshot(candidate) !== submitted));
                install(html, preserved);
                body.inert = false;
                body.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = false; });
                if (['settings', 'reset_settings', 'layout', 'page', 'widgets', 'page_widgets', 'preset_apply'].includes(operation)) {
                    status.textContent = 'Clearing cache…';
                    const clearData = new URLSearchParams({operation: 'cache_clear', _token: data.get('_token')});
                    try {
                        await request({method: 'POST', body: clearData});
                    } catch (error) {
                        throw new Error(`Saved, but cache could not be cleared: ${error.message}`);
                    }
                    // Navigate to a fresh URL so the restored sections appear immediately.
                    // Keep the current page, route parameters, and fragment after preview navigation.
                    let currentUrl = frame.src;
                    try { currentUrl = frame.contentWindow.location.href; }
                    catch (_) { /* An external page cannot expose its current location. */ }
                    const refreshUrl = new URL(currentUrl, window.location.href);
                    refreshUrl.searchParams.set('_preview_refresh', String(Date.now()));
                    frame.src = refreshUrl.href;
                }
                status.textContent = dirtyForms().length ? 'Saved. Other changes are still unsaved.' : 'Saved.';
                const savedForm = [...body.querySelectorAll('form')].find(candidate => formKey(candidate) === formKey(form));
                if (toggle.getAttribute('aria-expanded') === 'true') {
                    (savedForm?.querySelector('button[type="submit"]') || body.querySelector('[aria-selected="true"]'))?.focus();
                }
            } catch (error) {
                status.textContent = error.message;
            } finally {
                buttons.forEach(button => { button.disabled = false; });
                // Preserved forms may carry disabled buttons into the refreshed content.
                body.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = false; });
                busy = false;
                body.inert = false;
                popup.removeAttribute('aria-busy');
            }
        });
        window.addEventListener('beforeunload', event => {
            if (!dirtyForms().length) return;
            event.preventDefault();
            event.returnValue = '';
        });
    }

    function initializeSidebarWidgets() {
        // Delegation also covers forms loaded or replaced inside the preview editor.
        document.addEventListener('change', event => {
            if (!event.target.matches('[data-widget-mode]')) return;
            const list = event.target.closest('[data-widget-sidebar]').querySelector('[data-widget-list]');
            list.hidden = list.disabled = event.target.value === 'inherit';
            event.target.closest('form').dispatchEvent(new Event('change', {bubbles: true}));
        });
        document.addEventListener('click', event => {
            const button = event.target.closest('[data-widget-add], [data-widget-remove], [data-widget-move]');
            if (!button) return;
            const sidebar = button.closest('[data-widget-sidebar]');
            const rows = sidebar.querySelector('[data-widget-rows]');
            const form = button.closest('form');
            if (button.hasAttribute('data-widget-add')) {
                rows.append(sidebar.querySelector('[data-widget-row-template]').content.cloneNode(true));
                rows.lastElementChild.querySelector('select').focus();
            } else {
                const row = button.closest('[data-widget-row]');
                const previous = row.previousElementSibling;
                const next = row.nextElementSibling;
                if (button.hasAttribute('data-widget-remove')) {
                    row.remove();
                    (next?.querySelector('select') || previous?.querySelector('select') || sidebar.querySelector('[data-widget-add]')).focus();
                } else if (button.dataset.widgetMove === 'up' && previous) {
                    rows.insertBefore(row, previous);
                    button.focus();
                } else if (button.dataset.widgetMove === 'down' && next) {
                    rows.insertBefore(next, row);
                    button.focus();
                }
            }
            form.dispatchEvent(new Event('change', {bubbles: true}));
        });
    }

    function initializeUnsavedChanges() {
        const indicator = document.getElementById('theme-unsaved-indicator');
        const actions = indicator?.querySelector('[data-unsaved-actions]');
        const trackedOperations = new Set(['settings', 'layout', 'page', 'widgets', 'page_widgets', 'preset_save']);
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
                    button.textContent = ['page', 'page_widgets'].includes(operation)
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
    initializePreviewSettings();
    initializeSidebarWidgets();
    initializeUnsavedChanges();
})();
