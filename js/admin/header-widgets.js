(function () {
    'use strict';

    const SEARCH_MIN_CHARS = 2;
    const SEARCH_DEBOUNCE_MS = 280;
    const NOTIFICATION_REFRESH_MS = 60 * 1000;

    class AdminHeaderWidgets {
        constructor() {
            this.header = document.querySelector('.admin-header');
            if (!this.header) {
                return;
            }

            this.baseUrl = this.header.dataset.baseUrl || this.getMeta('base-url') || window.location.origin;
            this.endpoints = {
                search: this.header.dataset.searchEndpoint || `${this.baseUrl}/admin/api/dashboard/global_search.php`,
                notifications: this.header.dataset.notificationsEndpoint || `${this.baseUrl}/admin/api/dashboard/notifications.php`
            };

            this.quickActions = this.parseQuickActions();
            this.state = {
                searchTerm: '',
                searchTimer: null,
                searchController: null,
                notifications: [],
                notificationsTimer: null,
                notificationsLoading: false
            };

            this.formatters = {
                currency: new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }),
                number: new Intl.NumberFormat('es-CO'),
                relative: typeof Intl.RelativeTimeFormat === 'function'
                    ? new Intl.RelativeTimeFormat('es', { numeric: 'auto' })
                    : null
            };

            this.elements = {
                searchInput: document.getElementById('admin-search-input'),
                searchButton: document.getElementById('admin-search-btn'),
                searchPanel: document.getElementById('admin-search-results'),
                notificationBtn: document.getElementById('admin-notification-btn'),
                notificationPanel: document.getElementById('admin-notifications-panel'),
                notificationBadge: this.header.querySelector('[data-notification-count]'),
                notificationLabel: this.header.querySelector('[data-notification-label]'),
                notificationsContainer: this.header.querySelector('[data-notifications-container]'),
                quickActionBtn: document.getElementById('admin-quick-action-btn'),
                quickActionsPanel: document.getElementById('admin-quick-actions-panel'),
                quickActionsFilter: document.getElementById('quick-actions-filter'),
                quickActionsContainer: this.header.querySelector('[data-quick-actions-container]')
            };

            this.bindEvents();
            this.renderQuickActionsList(this.quickActions);
            this.prefetchNotifications();
        }

        getMeta(name) {
            const meta = document.querySelector(`meta[name="${name}"]`);
            return meta ? meta.content : '';
        }

        parseQuickActions() {
            const script = document.getElementById('admin-header-quick-actions-data');
            if (!script) {
                return [];
            }
            try {
                const data = JSON.parse(script.textContent || '[]');
                return Array.isArray(data) ? data : [];
            } catch (error) {
                console.warn('Quick actions parse error', error);
                return [];
            }
        }

        bindEvents() {
            this.elements.searchInput?.addEventListener('input', (event) => this.handleSearchInput(event));
            this.elements.searchInput?.addEventListener('keydown', (event) => this.handleSearchKeydown(event));
            this.elements.searchButton?.addEventListener('click', () => this.triggerSearch());

            this.elements.notificationBtn?.addEventListener('click', () => this.toggleNotificationsPanel());
            this.elements.quickActionBtn?.addEventListener('click', () => this.toggleQuickActionsPanel());

            this.elements.quickActionsFilter?.addEventListener('input', (event) => {
                const term = (event.target.value || '').trim().toLowerCase();
                this.filterQuickActions(term);
            });

            this.elements.notificationsContainer?.addEventListener('click', (event) => this.handleNotificationClick(event));
            this.elements.notificationPanel?.addEventListener('click', (event) => {
                if (event.target.closest('[data-notification-refresh]')) {
                    event.preventDefault();
                    this.fetchNotifications(true);
                }
                if (event.target.closest('[data-notification-mark-all]')) {
                    event.preventDefault();
                    this.markAllNotifications();
                }
            });

            this.elements.quickActionsPanel?.addEventListener('click', (event) => {
                if (event.target.closest('[data-quick-actions-close]')) {
                    this.toggleQuickActionsPanel(false);
                }
            });

            document.addEventListener('click', (event) => this.handleDocumentClick(event));
            document.addEventListener('keydown', (event) => this.handleGlobalKeydown(event));
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.fetchNotifications(false);
                }
            });
        }

        handleSearchInput(event) {
            const term = (event.target.value || '').trim();
            this.state.searchTerm = term;

            if (term.length < SEARCH_MIN_CHARS) {
                this.hideSearchPanel();
                if (this.state.searchController) {
                    this.state.searchController.abort();
                }
                return;
            }

            clearTimeout(this.state.searchTimer);
            this.state.searchTimer = setTimeout(() => this.runSearch(term), SEARCH_DEBOUNCE_MS);
        }

        handleSearchKeydown(event) {
            if (event.key === 'Escape') {
                this.hideSearchPanel(true);
                return;
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                this.triggerSearch();
            }
        }

        triggerSearch() {
            const term = (this.elements.searchInput?.value || '').trim();
            if (term.length >= SEARCH_MIN_CHARS) {
                this.runSearch(term);
            }
        }

        async runSearch(term) {
            if (!this.endpoints.search) {
                return;
            }

            if (this.state.searchController) {
                this.state.searchController.abort();
            }
            this.state.searchController = new AbortController();

            this.showSearchPanel();
            this.setSearchMessage('Buscando...');

            try {
                const url = new URL(this.endpoints.search, window.location.origin);
                url.searchParams.set('q', term);
                url.searchParams.set('limit', '5');

                const response = await fetch(url.toString(), {
                    credentials: 'same-origin',
                    signal: this.state.searchController.signal
                });

                if (!response.ok) {
                    // Try to surface a meaningful message from the API json body
                    let remoteMessage = `Error ${response.status}`;
                    try {
                        const body = await response.json();
                        if (body && body.message) {
                            remoteMessage = body.message;
                        }
                    } catch (e) {
                        // ignore parse errors
                    }

                    // If we got an unauthorized error, try to redirect to login
                    if (response.status === 401) {
                        window.location.href = this.baseUrl + '/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                        return;
                    }

                    throw new Error(remoteMessage);
                }

                const payload = await response.json();
                if (!payload.success) {
                    throw new Error(payload.message || 'No se pudo ejecutar la busqueda');
                }
                this.renderSearchResults(payload.results || {});
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                console.error('Search error', error);
                this.setSearchMessage('No pudimos completar la busqueda.');
            }
        }

        setSearchMessage(message) {
            if (this.elements.searchPanel) {
                this.elements.searchPanel.innerHTML = `<p class="dropdown-empty">${message}</p>`;
            }
        }

        renderSearchResults(results) {
            const panel = this.elements.searchPanel;
            if (!panel) {
                return;
            }

            panel.innerHTML = '';

            const sections = [
                { key: 'orders', label: 'Ordenes', icon: 'fa-receipt' },
                { key: 'products', label: 'Productos', icon: 'fa-box' },
                { key: 'customers', label: 'Clientes', icon: 'fa-user' },
                { key: 'shortcuts', label: 'Accesos directos', icon: 'fa-bolt' }
            ];

            let hasResults = false;
            sections.forEach((section) => {
                const items = Array.isArray(results[section.key]) ? results[section.key] : [];
                if (!items.length) {
                    return;
                }
                hasResults = true;
                panel.appendChild(this.buildSearchSection(section, items));
            });

            if (!hasResults) {
                panel.innerHTML = '<p class="dropdown-empty">Sin coincidencias para la busqueda.</p>';
            }
        }

        buildSearchSection(section, items) {
            const wrapper = document.createElement('div');
            wrapper.className = 'search-result-group';

            const title = document.createElement('p');
            title.className = 'search-result-title';
            title.textContent = section.label;
            wrapper.appendChild(title);

            const list = document.createElement('ul');
            list.className = 'search-result-list';

            items.forEach((item) => {
                const li = document.createElement('li');
                li.className = 'search-result-item';
                const link = document.createElement('a');
                link.href = item.url || '#';
                link.tabIndex = 0;

                const icon = document.createElement('span');
                icon.className = 'search-result-icon';
                icon.innerHTML = `<i class="fas ${section.icon}"></i>`;
                link.appendChild(icon);

                const content = document.createElement('div');
                content.className = 'search-result-content';

                const titleNode = document.createElement('strong');
                titleNode.textContent = item.title || item.label || 'Sin titulo';
                content.appendChild(titleNode);

                if (item.subtitle || item.description) {
                    const sub = document.createElement('span');
                    sub.textContent = item.subtitle || item.description;
                    content.appendChild(sub);
                }

                link.appendChild(content);
                li.appendChild(link);
                list.appendChild(li);
            });

            wrapper.appendChild(list);
            return wrapper;
        }

        showSearchPanel() {
            if (this.elements.searchPanel) {
                this.elements.searchPanel.hidden = false;
                this.elements.searchPanel.classList.add('is-open');
            }
        }

        hideSearchPanel(blurInput = false) {
            if (this.elements.searchPanel) {
                this.elements.searchPanel.hidden = true;
                this.elements.searchPanel.classList.remove('is-open');
            }
            if (blurInput) {
                this.elements.searchInput?.blur();
            }
        }

        handleDocumentClick(event) {
            if (!this.header.contains(event.target)) {
                this.hideSearchPanel();
                this.toggleNotificationsPanel(false);
                this.toggleQuickActionsPanel(false);
                return;
            }

            if (!event.target.closest('#admin-search')) {
                this.hideSearchPanel();
            }
        }

        handleGlobalKeydown(event) {
            const key = event.key.toLowerCase();
            const isInput = ['input', 'textarea'].includes(event.target.tagName.toLowerCase());

            if (key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey && !isInput) {
                event.preventDefault();
                this.elements.searchInput?.focus();
                return;
            }

            if ((event.ctrlKey || event.metaKey) && key === 'k') {
                event.preventDefault();
                this.toggleQuickActionsPanel(true);
                return;
            }

            if (event.shiftKey && key === 'n') {
                event.preventDefault();
                this.toggleNotificationsPanel(true);
            }

            if (key === 'escape') {
                this.hideSearchPanel();
                this.toggleQuickActionsPanel(false);
                this.toggleNotificationsPanel(false);
            }
        }

        toggleNotificationsPanel(force) {
            const panel = this.elements.notificationPanel;
            if (!panel) {
                return;
            }

            const shouldOpen = typeof force === 'boolean' ? force : panel.hidden;
            if (shouldOpen) {
                panel.hidden = false;
                panel.classList.add('is-open');
                this.elements.notificationBtn?.setAttribute('aria-expanded', 'true');
                this.renderNotifications();
                this.fetchNotifications(false);
            } else {
                panel.hidden = true;
                panel.classList.remove('is-open');
                this.elements.notificationBtn?.setAttribute('aria-expanded', 'false');
            }
        }

        toggleQuickActionsPanel(force) {
            const panel = this.elements.quickActionsPanel;
            if (!panel) {
                return;
            }

            const shouldOpen = typeof force === 'boolean' ? force : panel.hidden;
            if (shouldOpen) {
                panel.hidden = false;
                panel.classList.add('is-open');
                this.elements.quickActionBtn?.setAttribute('aria-expanded', 'true');
                this.elements.quickActionsFilter?.focus();
            } else {
                panel.hidden = true;
                panel.classList.remove('is-open');
                this.elements.quickActionBtn?.setAttribute('aria-expanded', 'false');
            }
        }

        async fetchNotifications(showSpinner) {
            if (!this.endpoints.notifications || this.state.notificationsLoading) {
                return;
            }

            this.state.notificationsLoading = true;
            if (showSpinner) {
                this.setNotificationsMessage('Actualizando...');
            }

            try {
                const url = new URL(this.endpoints.notifications, window.location.origin);
                url.searchParams.set('ts', Date.now().toString());
                const response = await fetch(url.toString(), { credentials: 'same-origin' });

                if (!response.ok) {
                    let remoteMessage = `Error ${response.status}`;
                    try {
                        const body = await response.json();
                        if (body && body.message) {
                            remoteMessage = body.message;
                        }
                    } catch (e) {
                        // ignore
                    }
                    throw new Error(remoteMessage);
                }

                const payload = await response.json();
                if (!payload.success) {
                    throw new Error(payload.message || 'No se pudieron cargar las notificaciones');
                }

                this.state.notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
                this.updateNotificationBadge(payload.unread_count ?? this.state.notifications.length);
                this.renderNotifications();
                this.scheduleNotificationsRefresh();
            } catch (error) {
                console.error('Notifications error', error);
                this.setNotificationsMessage('No pudimos cargar las notificaciones.');

                // Also show a dashboard level error (if present) so admins notice a persistent failure
                const dashboardErrorEl = document.getElementById('dashboard-error');
                if (dashboardErrorEl) {
                    dashboardErrorEl.style.display = 'block';
                    dashboardErrorEl.textContent = error.message || 'Error cargando notificaciones';
                }
            } finally {
                this.state.notificationsLoading = false;
            }
        }

        scheduleNotificationsRefresh() {
            clearTimeout(this.state.notificationsTimer);
            this.state.notificationsTimer = setTimeout(() => this.fetchNotifications(false), NOTIFICATION_REFRESH_MS);
        }

        renderNotifications() {
            const container = this.elements.notificationsContainer;
            if (!container) {
                return;
            }

            if (!this.state.notifications.length) {
                this.setNotificationsMessage('Sin notificaciones pendientes.');
                return;
            }

            const fragment = document.createDocumentFragment();
            this.state.notifications.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'notification-item';
                row.dataset.notificationId = item.id;
                if (item.url) {
                    row.dataset.notificationUrl = item.url;
                }

                const icon = document.createElement('div');
                icon.className = 'notification-icon';
                icon.innerHTML = `<i class="fas ${item.icon || 'fa-bell'}"></i>`;
                row.appendChild(icon);

                const content = document.createElement('div');
                content.className = 'notification-content';

                const title = document.createElement('strong');
                title.textContent = item.title || 'Nueva alerta';
                content.appendChild(title);

                if (item.message) {
                    const p = document.createElement('p');
                    p.textContent = item.message;
                    content.appendChild(p);
                }

                const meta = document.createElement('div');
                meta.className = 'notification-meta';

                const leftMeta = document.createElement('span');
                leftMeta.textContent = this.formatRelativeTime(item.created_at);
                meta.appendChild(leftMeta);

                const actions = document.createElement('div');
                actions.className = 'notification-actions';

                if (item.tag) {
                    const tag = document.createElement('span');
                    tag.className = 'notification-tag';
                    tag.textContent = item.tag;
                    actions.appendChild(tag);
                }

                const markBtn = document.createElement('button');
                markBtn.type = 'button';
                markBtn.className = 'link-button';
                markBtn.dataset.notificationAction = 'mark';
                markBtn.textContent = 'Listo';
                actions.appendChild(markBtn);

                meta.appendChild(actions);
                content.appendChild(meta);

                row.appendChild(content);
                fragment.appendChild(row);
            });

            container.innerHTML = '';
            container.appendChild(fragment);
        }

        setNotificationsMessage(message) {
            if (this.elements.notificationsContainer) {
                this.elements.notificationsContainer.innerHTML = `<p class="dropdown-empty">${message}</p>`;
            }
        }

        handleNotificationClick(event) {
            const markButton = event.target.closest('[data-notification-action="mark"]');
            if (markButton) {
                const parent = markButton.closest('.notification-item');
                if (parent?.dataset.notificationId) {
                    this.markNotification(parent.dataset.notificationId);
                }
                event.stopPropagation();
                return;
            }

            const item = event.target.closest('.notification-item');
            if (!item) {
                return;
            }

            const id = item.dataset.notificationId;
            const url = item.dataset.notificationUrl;
            if (id) {
                this.markNotification(id, url);
            }
        }

        async markNotification(id, redirectUrl) {
            try {
                const res = await fetch(this.endpoints.notifications, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ action: 'mark_read', id })
                });
                if (!res.ok) {
                    try {
                        const body = await res.json();
                        console.warn('No fue posible marcar la notificacion', body.message || res.statusText);
                    } catch (_e) {
                        console.warn('No fue posible marcar la notificacion', res.statusText);
                    }
                }
            } catch (error) {
                console.warn('No fue posible marcar la notificacion', error);
            } finally {
                this.state.notifications = this.state.notifications.filter((item) => item.id !== id);
                this.renderNotifications();
                this.updateNotificationBadge(this.state.notifications.length);
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }
        }

        async markAllNotifications() {
            if (!this.state.notifications.length) {
                return;
            }
            try {
                const res = await fetch(this.endpoints.notifications, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
                if (!res.ok) {
                    try {
                        const body = await res.json();
                        console.warn('No fue posible marcar todas las notificaciones', body.message || res.statusText);
                    } catch (_e) {
                        console.warn('No fue posible marcar todas las notificaciones', res.statusText);
                    }
                }
            } catch (error) {
                console.warn('No fue posible marcar todas las notificaciones', error);
            } finally {
                this.state.notifications = [];
                this.renderNotifications();
                this.updateNotificationBadge(0);
            }
        }

        updateNotificationBadge(count) {
            if (this.elements.notificationBadge) {
                if (count > 0) {
                    this.elements.notificationBadge.hidden = false;
                    this.elements.notificationBadge.textContent = String(count);
                } else {
                    this.elements.notificationBadge.hidden = true;
                    this.elements.notificationBadge.textContent = '0';
                }
            }
            if (this.elements.notificationLabel) {
                this.elements.notificationLabel.textContent = count > 0
                    ? `${count} notificaciones nuevas`
                    : 'Sin notificaciones pendientes';
            }
        }

        filterQuickActions(term) {
            if (!term) {
                this.renderQuickActionsList(this.quickActions);
                return;
            }
            const filtered = this.quickActions.filter((action) => {
                const haystack = [action.label, action.description].concat(action.keywords || [])
                    .join(' ')
                    .toLowerCase();
                return haystack.includes(term);
            });
            this.renderQuickActionsList(filtered);
        }

        renderQuickActionsList(actions) {
            const container = this.elements.quickActionsContainer;
            if (!container) {
                return;
            }

            if (!actions.length) {
                container.innerHTML = '<li class="dropdown-empty">Sin coincidencias</li>';
                return;
            }

            const fragment = document.createDocumentFragment();
            actions.forEach((action) => {
                const li = document.createElement('li');
                li.className = 'quick-action-item';

                const link = document.createElement('a');
                link.href = this.baseUrl + action.path;
                link.className = 'quick-action-link';

                const icon = document.createElement('span');
                icon.className = 'icon';
                icon.innerHTML = `<i class="fas ${action.icon || 'fa-bolt'}"></i>`;

                const body = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = action.label;
                body.appendChild(title);

                if (action.description) {
                    const desc = document.createElement('p');
                    desc.textContent = action.description;
                    body.appendChild(desc);
                }

                link.appendChild(icon);
                link.appendChild(body);
                li.appendChild(link);
                fragment.appendChild(li);
            });

            container.innerHTML = '';
            container.appendChild(fragment);
        }

        formatRelativeTime(dateString) {
            if (!dateString) {
                return 'Reciente';
            }
            const target = new Date(dateString).getTime();
            const now = Date.now();
            const diffMs = target - now;
            const diffMinutes = Math.round(diffMs / (1000 * 60));

            if (!this.formatters.relative) {
                return this.formatTimeFallback(diffMinutes);
            }

            const absMinutes = Math.abs(diffMinutes);
            if (absMinutes < 60) {
                return this.formatters.relative.format(diffMinutes, 'minute');
            }
            const diffHours = Math.round(diffMinutes / 60);
            const absHours = Math.abs(diffHours);
            if (absHours < 24) {
                return this.formatters.relative.format(diffHours, 'hour');
            }
            const diffDays = Math.round(diffHours / 24);
            return this.formatters.relative.format(diffDays, 'day');
        }

        formatTimeFallback(diffMinutes) {
            const absMinutes = Math.abs(diffMinutes);
            if (absMinutes < 60) {
                return diffMinutes >= 0 ? `En ${absMinutes} min` : `Hace ${absMinutes} min`;
            }
            const hours = Math.round(absMinutes / 60);
            return diffMinutes >= 0 ? `En ${hours} h` : `Hace ${hours} h`;
        }

        prefetchNotifications() {
            setTimeout(() => this.fetchNotifications(false), 400);
        }
    }

    document.addEventListener('DOMContentLoaded', () => new AdminHeaderWidgets());
})();
