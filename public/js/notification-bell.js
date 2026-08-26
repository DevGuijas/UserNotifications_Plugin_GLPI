(() => {
    'use strict';

    const pluginScript = Array.from(document.scripts).find((element) => element.src.includes('/plugins/usernotifications/js/notification-bell.js'));
    if (!pluginScript) return;
    const scriptUrl = new URL(pluginScript.src, window.location.href);
    const jsPathIndex = scriptUrl.pathname.lastIndexOf('/js/');
    if (jsPathIndex < 0) return;
    const pluginRoot = `${scriptUrl.origin}${scriptUrl.pathname.slice(0, jsPathIndex)}`;

    let attempts = 0;
    const initialize = () => {
        const header = document.querySelector('[data-testid="main-header"]') || document.querySelector('header.topbar');
        const profileToggle = Array.from(header?.querySelectorAll('.user-menu-dropdown-toggle') || []).find((element) => element.getClientRects().length > 0);
        const profileItem = profileToggle?.closest('.nav-item');
        const userMenu = profileItem?.parentElement;
        if (!userMenu || !profileItem) {
            if (attempts++ < 20) window.setTimeout(initialize, 250);
            return;
        }

        document.getElementById('usernotifications-bell')?.remove();
        const wrapper = document.createElement('div');
        wrapper.className = 'nav-item usernotifications-wrapper';
        wrapper.id = 'usernotifications-bell';
        wrapper.innerHTML = `<button type="button" class="nav-link usernotifications-toggle" aria-label="Notificações" aria-expanded="false"><i class="ti ti-bell" aria-hidden="true"></i><span class="usernotifications-badge d-none"></span></button><section class="usernotifications-panel d-none" aria-label="Notificações"><header class="usernotifications-header"><strong>Notificações</strong><button type="button" class="usernotifications-close" aria-label="Fechar">×</button></header><div class="usernotifications-list" role="list"></div><footer class="usernotifications-footer"><button type="button" class="btn btn-link btn-sm usernotifications-mark">Marcar notificações como lidas</button></footer></section>`;
        profileItem.before(wrapper);

        const toggle = wrapper.querySelector('.usernotifications-toggle');
        const panel = wrapper.querySelector('.usernotifications-panel');
        const list = wrapper.querySelector('.usernotifications-list');
        const badge = wrapper.querySelector('.usernotifications-badge');
        const markAllButton = wrapper.querySelector('.usernotifications-mark');
        let state = { notifications: [], unread: 0 };
        const close = () => { panel.classList.add('d-none'); toggle.setAttribute('aria-expanded', 'false'); };
        const open = () => { panel.classList.remove('d-none'); toggle.setAttribute('aria-expanded', 'true'); };
        const formatDate = (date) => new Intl.DateTimeFormat(document.documentElement.lang || 'pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(date.replace(' ', 'T')));
        const render = (data) => {
            state = { notifications: Array.isArray(data.notifications) ? data.notifications : [], unread: Number(data.unread) || 0, mark_token: data.mark_token || state.mark_token || '' };
            badge.textContent = state.unread > 99 ? '99+' : state.unread;
            badge.classList.toggle('d-none', state.unread === 0);
            markAllButton.disabled = state.unread === 0;
            list.replaceChildren();
            if (!state.notifications.length) {
                const empty = document.createElement('p');
                empty.className = 'usernotifications-empty';
                empty.textContent = 'Você não tem notificações.';
                list.append(empty);
                return;
            }
            state.notifications.forEach((notification) => {
                const item = document.createElement('a');
                item.className = `usernotifications-item${notification.is_read ? ' is-read' : ''}`;
                item.href = notification.url;
                const icon = document.createElement('i');
                icon.className = notification.kind === 'approval' ? 'ti ti-circle-check' : 'ti ti-ticket';
                const text = document.createElement('span'); text.className = 'usernotifications-text';
                const message = document.createElement('span'); message.textContent = notification.message;
                const time = document.createElement('small'); time.textContent = formatDate(notification.date_creation);
                text.append(message, time); item.append(icon, text); list.append(item);
                item.addEventListener('click', async (event) => {
                    if (notification.is_read) return;
                    event.preventDefault();
                    await markRead(notification.id);
                    window.location.assign(item.href);
                });
            });
        };
        const markToken = () => state.mark_token || '';
        const markRead = async (id = 0) => {
            const token = markToken();
            if (!token) return false;
            try {
                const response = await fetch(`${pluginRoot}/front/mark-read.php`, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ plugin_usernotifications_mark_token: token, ...(id ? { id: String(id) } : {}) }),
                });
                const result = await response.json();
                if (!response.ok || !result.ok) return false;
                state.mark_token = result.mark_token || state.mark_token;
                state.notifications = state.notifications.map((notification) => (!id || notification.id === id ? { ...notification, is_read: true } : notification));
                state.unread = state.notifications.filter((notification) => !notification.is_read).length;
                render(state);
                return true;
            } catch (_) { render(state); return false; }
        };
        const refresh = async () => {
            try {
                const response = await fetch(`${pluginRoot}/front/feed.php`, { credentials: 'same-origin', cache: 'no-store' });
                if (response.ok) render(await response.json());
            } catch (_) { /* The bell stays available if the feed is temporarily unavailable. */ }
        };

        toggle.addEventListener('click', (event) => { event.preventDefault(); panel.classList.contains('d-none') ? open() : close(); });
        wrapper.querySelector('.usernotifications-close').addEventListener('click', close);
        document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) close(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
        markAllButton.addEventListener('click', async () => {
            markAllButton.disabled = true;
            await markRead();
        });
        refresh();
        window.setInterval(refresh, 60000);
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();