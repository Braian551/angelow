class AdminsHub {
    constructor(cfg) {
        this.cfg = cfg;
        this.baseUrl = cfg.baseUrl || document.querySelector('meta[name="base-url"]')?.content || '';
        this.table = document.getElementById('admins-table');
        this.load();
        this.bind();
    }

    bind() {
        this.table?.addEventListener('click', (evt) => {
            const btn = evt.target.closest('[data-action]');
            if (!btn) return;
            const id = btn.closest('tr')?.getAttribute('data-id');
            const action = btn.getAttribute('data-action');
            if (!id) return;
            this.perform(action, id);
        });
    }

    async load() {
        this.table.innerHTML = '<tr><td colspan="5">Cargando administradores...</td></tr>';
        try {
            const res = await fetch(this.cfg.endpoints.list, { credentials: 'same-origin'});
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'API');
            this.render(json.items || []);
        } catch (e) {
            console.error('admins list', e);
            this.table.innerHTML = '<tr><td colspan="5">No se pudo cargar la lista</td></tr>';
        }
    }

    render(items) {
        if (!items.length) {
            this.table.innerHTML = '<tr><td colspan="5">Sin administradores</td></tr>';
            return;
        }
        const baseUrl = this.baseUrl;

        this.table.innerHTML = items.map((a) => `
            <tr data-id="${a.id}">
                <td>${a.name || 'Administrador'}</td>
                <td>${a.email}</td>
                <td>${a.job_title || a.role || 'Admin'}</td>
                <td>${!a.is_blocked ? '<span class="status-chip success">Activo</span>' : '<span class="status-chip warning">Bloqueado</span>'}</td>
                <td>
                    <button class="btn-soft" data-action="${a.is_blocked ? 'unblock' : 'block'}">${a.is_blocked ? 'Activar' : 'Bloquear'}</button>
                    <a class="btn-soft" href="${baseUrl}/admin/services/admin_edit.php?id=${a.id}">Editar</a>
                </td>
            </tr>
        `).join('');
    }

    async perform(action, id) {
        if (!confirm('Confirmar accion?')) return;
        try {
            // API expects a 'user_id' field and actions 'block'/'unblock'
            const body = JSON.stringify({ user_id: id, action: action });
            const res = await fetch(this.cfg.endpoints.update, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'api');
            this.load();
        } catch (e) {
            alert('Operacion fallida');
            console.error('admin action', e);
        }
    }
}

if (window.ADMINS_HUB_CONFIG) {
    document.addEventListener('DOMContentLoaded', () => new AdminsHub(window.ADMINS_HUB_CONFIG));
}
