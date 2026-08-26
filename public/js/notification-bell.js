(() => {
    'use strict';
    const script = document.currentScript;
    if (!script || document.getElementById('usernotifications-bell')) return;
    const pluginRoot = script.src.replace(/\/public\/js\/[^/]+$/, '');
    const userMenu = document.querySelector('.user-menu');
    if (!userMenu) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'nav-item usernotifications-wrapper';
    wrapper.id = 'usernotifications-bell';
    wrapper.innerHTML = `<button type="button" class="nav-link usernotifications-toggle" aria-label="Notificações" aria-expanded="false"><i class="ti ti-bell" aria-hidden="true"></i><span class="usernotifications-badge d-none"></span></button><section class="usernotifications-panel d-none" aria-label="Notificações"><header class="usernotifications-header"><strong>Notificações</strong><button type="button" class="usernotifications-close" aria-label="Fechar">×</button></header><div class="usernotifications-list" role="list"></div><footer class="usernotifications-footer"><button type="button" class="btn btn-link btn-sm usernotifications-mark">Marcar notificações como lidas</button></footer></section>`;
    userMenu.insertBefore(wrapper, userMenu.firstElementChild);

    const toggle = wrapper.querySelector('.usernotifications-toggle');
    const panel = wrapper.querySelector('.usernotifications-panel');
    const list = wrapper.querySelector('.usernotifications-list');
    const badge = wrapper.querySelector('.usernotifications-badge');
    const close = () => { panel.classList.add('d-none'); toggle.setAttribute('aria-expanded', 'false'); };
    const open = () => { panel.classList.remove('d-none'); toggle.setAttribute('aria-expanded', 'true'); };
    const formatDate = (date) => new Intl.DateTimeFormat(document.documentElement.lang || 'pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(date.replace(' ', 'T')));
    const render = (data) => {
        badge.textContent = data.unread > 99 ? '99+' : data.unread;
        badge.classList.toggle('d-none', !data.unread);
        list.replaceChildren();
        if (!data.notifications.length) {
            const empty = document.createElement('p'); empty.className = 'usernotifications-empty'; empty.textContent = 'Você não tem notificações.'; list.append(empty); return;
        }
        data.notifications.forEach((notification) => {
            const item = document.createElement('a');
            item.className = `usernotifications-item${notification.is_read ? ' is-read' : ''}`;
            item.href = notification.url;
            const icon = document.createElement('i'); icon.className = notification.kind === 'approval' ? 'ti ti-circle-check' : 'ti ti-ticket';
            const text = document.createElement('span'); text.className = 'usernotifications-text';
            const message = document.createElement('span'); message.textContent = notification.message;
            const time = document.createElement('small'); time.textContent = formatDate(notification.date_creation);
            text.append(message, time); item.append(icon, text); list.append(item);
        });
    };
    const refresh = async () => {
        try {
            const response = await fetch(`${pluginRoot}/front/feed.php`, { credentials: 'same-origin', cache: 'no-store' });
            if (response.ok) render(await response.json());
        } catch (_) { /* A temporary network error must not affect GLPI. */ }
    };
    toggle.addEventListener('click', (event) => { event.preventDefault(); panel.classList.contains('d-none') ? open() : close(); });
    wrapper.querySelector('.usernotifications-close').addEventListener('click', close);
    document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
    wrapper.querySelector('.usernotifications-mark').addEventListener('click', async () => {
        const token = document.querySelector('meta[property="glpi:csrf_token"]')?.content;
        if (!token) return;
        const response = await fetch(`${pluginRoot}/front/mark-read.php`, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _glpi_csrf_token: token }) });
        if (response.ok) await refresh();
    });
    refresh();
    window.setInterval(refresh, 60000);
})();