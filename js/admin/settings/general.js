(function () {
    async function load() {
        try {
            const res = await fetch(window.SITE_SETTINGS_ENDPOINTS.url, { credentials: 'same-origin' });
            const payload = await res.json();
            if (!payload.success) throw new Error(payload.message || 'error');
            renderForm(payload);
        } catch (e) {
            console.error('load settings', e);
            document.getElementById('settings-fields').innerHTML = '<div class="text-muted">No se pudieron cargar las configuraciones.</div>';
        }
    }

    function renderForm(payload) {
        const container = document.getElementById('settings-fields');
        const defs = payload.definitions || {};
        const values = payload.settings?.values || {};
        container.innerHTML = Object.keys(defs).map((k) => {
            const def = defs[k];
            const val = values[k] ?? def.default ?? '';
            if (def.type === 'text' || def.type === 'email') {
                return `<div class="form-row"><label>${def.label}</label><input type="text" name="${k}" value="${escapeHtml(val)}" placeholder="${def.hint || ''}"></div>`;
            }
            if (def.type === 'textarea') {
                return `<div class="form-row"><label>${def.label}</label><textarea name="${k}" placeholder="${def.hint || ''}">${escapeHtml(val)}</textarea></div>`;
            }
            if (def.type === 'image') {
                return `<div class="form-row"><label>${def.label}</label><input type="file" name="${k}" accept="image/*"><div class="text-muted">Imagen actual: ${escapeHtml(val || 'No hay imagen')}</div></div>`;
            }
            return `<div class="form-row"><label>${def.label}</label><input type="text" name="${k}" value="${escapeHtml(val)}"></div>`;
        }).join('');
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text).replace(/[&<>"']/g, function (s) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s];
        });
    }

    async function save(e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        try {
            const res = await fetch(window.SITE_SETTINGS_ENDPOINTS.url, { method: 'POST', credentials: 'same-origin', body: fd });
            const payload = await res.json();
            if (!payload.success) throw new Error(payload.message || 'error');
            alert('Configuración guardada');
        } catch (err) {
            console.error('save settings', err);
            alert('No se pudo guardar la configuración');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('settings-form').addEventListener('submit', save);
        load();
    });
})();
