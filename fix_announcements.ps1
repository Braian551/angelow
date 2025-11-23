$file = 'c:\laragon\www\angelow\js\admin\announcements\announcementsadmin.js'
$content = Get-Content $file -Raw -Encoding UTF8

# Reemplazar la función displayAnnouncements completa
$pattern = '(?s)    // Mostrar anuncios\r?\n    function displayAnnouncements\(announcements\) \{.*?\n    \}'
$replacement = @'
    // Mostrar anuncios
    function displayAnnouncements(announcements) {
        if (announcements.length === 0) {
            container.innerHTML = '<tr><td colspan="7"><div class="alert alert-info">No se encontraron anuncios.</div></td></tr>';
            return;
        }

        container.innerHTML = announcements.map(announcement => {
            const typeLabel = announcement.type === 'top_bar' ? 'Barra Superior' : 'Banner Promo';
            const typeBadge = `<span class="badge badge-info">${typeLabel}</span>`;
            const statusBadge = announcement.is_active
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-danger">Inactivo</span>';

            const iconHtml = announcement.icon
                ? `<i class="fas ${announcement.icon} fa-lg text-primary"></i>`
                : '<i class="fas fa-bullhorn fa-lg text-muted"></i>';

            const dates = [];
            if (announcement.start_date) dates.push(`Inicio: ${formatDate(announcement.start_date)}`);
            if (announcement.end_date) dates.push(`Fin: ${formatDate(announcement.end_date)}`);
            const datesHtml = dates.length > 0 ? `<small class="text-muted">${dates.join('<br>')}</small>` : '<span class="text-muted">-</span>';

            return `
                <tr>
                    <td class="text-center">${iconHtml}</td>
                    <td>
                        <strong>${announcement.title}</strong>
                        <br>
                        <small class="text-muted">${truncate(announcement.message, 50)}</small>
                    </td>
                    <td>${typeBadge}</td>
                    <td>${announcement.priority}</td>
                    <td>${datesHtml}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="action-buttons">
                            <a href="${window.BASE_URL}/admin/announcements/edit.php?id=${announcement.id}" class="btn-icon btn-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmDelete(${announcement.id})" class="btn-icon btn-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
'@

$content = $content -replace $pattern, $replacement

# Guardar el archivo
Set-Content $file -Value $content -Encoding UTF8 -NoNewline

Write-Host "Archivo actualizado correctamente"
