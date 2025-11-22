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

        // Group by category
        const categories = {
            'brand': { title: 'Marca e Identidad', icon: 'fa-id-card' },
            'support': { title: 'Soporte y Contacto', icon: 'fa-headset' },
            'operations': { title: 'Operaciones', icon: 'fa-cogs' },
            'social': { title: 'Redes Sociales', icon: 'fa-share-alt' }
        };

        let html = '<div class="settings-grid-layout">';

        Object.entries(categories).forEach(([catKey, catInfo]) => {
            const catFields = Object.keys(defs).filter(k => defs[k].category === catKey);
            if (catFields.length === 0) return;

            html += `<div class="surface-card settings-section">
                <div class="section-header">
                    <h3><div class="section-icon"><i class="fas ${catInfo.icon}"></i></div> ${catInfo.title}</h3>
                </div>
                <div class="fields-grid">`;

            catFields.forEach(k => {
                const def = defs[k];
                const val = values[k] ?? def.default ?? '';
                const icon = def.icon ? `<i class="fas ${def.icon}"></i>` : '';

                html += `<div class="form-group">
                    <label class="form-label">${def.label}</label>
                    <div class="input-group">
                        ${icon ? `<span class="input-group-text">${icon}</span>` : ''}
                        ${renderInput(k, def, val)}
                    </div>
                    ${def.hint ? `<small class="form-hint">${def.hint}</small>` : ''}
                </div>`;
            });

            html += `</div></div>`;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    function renderInput(key, def, value) {
        const safeValue = escapeHtml(value);

        if (def.type === 'textarea') {
            return `<textarea name="${key}" class="form-control" placeholder="${def.hint || ''}">${safeValue}</textarea>`;
        }

        if (def.type === 'image') {
            return `
                <div class="file-input-wrapper">
                    <input type="file" name="${key}" accept="image/*" class="form-control">
                    ${value ? `<div class="current-image-preview"><img src="../${safeValue}" alt="Current"></div>` : ''}
                </div>`;
        }

        if (def.type === 'bool') {
            const isChecked = value == true || value == 'true' || value == '1';
            return `
                <select name="${key}" class="form-control">
                    <option value="1" ${isChecked ? 'selected' : ''}>Sí</option>
                    <option value="0" ${!isChecked ? 'selected' : ''}>No</option>
                </select>`;
        }

        if (def.type === 'color' || (def.pattern && def.pattern.includes('#'))) {
            return `<input type="color" name="${key}" value="${safeValue}" class="form-control form-control-color">`;
        }

        const type = def.type === 'int' ? 'number' : (def.type === 'email' ? 'email' : 'text');
        return `<input type="${type}" name="${key}" value="${safeValue}" class="form-control" placeholder="${def.hint || ''}">`;
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text).replace(/[&<>"']/g, function (s) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": "&#39;" })[s];
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
