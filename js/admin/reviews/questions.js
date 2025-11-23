class QuestionsInbox {
    constructor(cfg) {
        this.cfg = cfg;
        this.state = { page: 1, perPage: 12, search: '', answered: 'all' };
        this.items = new Map();
        this.charts = {};
        this.cacheDom();
        this.state.answered = this.filterSelect?.value === 'all' ? 'all' : (this.filterSelect?.value === '1' ? 1 : 0);
        this.bind();
        this.loadOverview();
        this.loadList();
    }

    cacheDom() {
        this.container = document.getElementById('questions-hub');
        this.statCards = this.container?.querySelectorAll('.stat-card') || [];
        this.tableBody = document.getElementById('questions-table');
        this.searchInput = document.getElementById('questions-search');
        this.filterSelect = document.getElementById('questions-filter');
        this.pagination = document.getElementById('questions-pagination');
        this.detailPanel = document.getElementById('question-detail');
        this.detailToggle = document.getElementById('question-detail-toggle');
        this.answersList = document.getElementById('question-answers');
        this.answerForm = document.getElementById('answer-form');
        this.statusCanvas = document.getElementById('questions-status-chart');
        this.statusLegend = document.getElementById('questions-status-legend');
        this.clearBtn = document.getElementById('questions-clear-filters');
        this.debugPanel = document.getElementById('questions-debug');
        this.debugPre = document.getElementById('questions-debug-pre');
        this.refreshBtn = document.getElementById('questions-refresh');
    }

    bind() {
        this.searchInput?.addEventListener('input', this.debounce((e) => {
            this.state.search = e.target.value.trim();
            this.state.page = 1;
            this.loadList();
        }, 350));

        this.filterSelect?.addEventListener('change', (e) => {
            const val = e.target.value;
            this.state.answered = val === 'all' ? 'all' : (val === '1' ? 1 : 0);
            this.state.page = 1;
            this.loadList();
        });

        this.pagination?.querySelectorAll('button[data-page]')?.forEach((btn) => {
            btn.addEventListener('click', () => {
                const dir = btn.getAttribute('data-page');
                if (dir === 'prev' && this.state.page > 1) {
                    this.state.page -= 1; this.loadList();
                } else if (dir === 'next') {
                    this.state.page += 1; this.loadList();
                }
            });
        });

        this.tableBody?.addEventListener('click', (evt) => {
            const action = evt.target.closest('[data-action]');
            const row = evt.target.closest('tr[data-id]');
            if (action) {
                const id = action.closest('tr')?.getAttribute('data-id');
                const act = action.getAttribute('data-action');
                if (act === 'view') this.openDetail(id);
                if (act === 'delete') this.deleteQuestion(id);
                return;
            }
            if (row) this.openDetail(row.getAttribute('data-id'));
        });

        this.answerForm?.addEventListener('submit', (e) => this.submitAnswer(e));
        // handle internal action buttons (delete)
        this.answerForm?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const action = btn.getAttribute('data-action');
            if (action === 'delete') {
                const id = this.answerForm.dataset.questionId;
                this.deleteQuestion(id);
            }
        });

        this.clearBtn?.addEventListener('click', () => {
            if (this.searchInput) this.searchInput.value = '';
            if (this.filterSelect) this.filterSelect.value = 'all';
            this.state.search = '';
            this.state.answered = 'all';
            this.state.page = 1;
            this.loadList();
        });

        this.refreshBtn?.addEventListener('click', () => {
            this.loadOverview();
            this.loadList();
        });

        this.detailToggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!this.detailPanel) return;
            const closed = this.detailPanel.classList.toggle('collapsed');
            this.detailPanel.setAttribute('aria-hidden', String(closed));
            this.detailToggle.setAttribute('aria-expanded', String(!closed));
        });
    }

    async loadOverview() {
        try {
            const totalResp = await fetch(`${this.cfg.endpoints.list}?page=1&per_page=1`, { credentials: 'same-origin' });
            const totalData = await totalResp.json();
            const answeredResp = await fetch(`${this.cfg.endpoints.list}?page=1&per_page=1&answered=1`, { credentials: 'same-origin' });
            const answeredData = await answeredResp.json();
            const unansweredResp = await fetch(`${this.cfg.endpoints.list}?page=1&per_page=1&answered=0`, { credentials: 'same-origin' });
            const unansweredData = await unansweredResp.json();

            const stats = {
                total: totalData?.pagination?.total ?? 0,
                answered: answeredData?.pagination?.total ?? 0,
                unanswered: unansweredData?.pagination?.total ?? 0
            };
            this.renderStats(stats);
        } catch (e) {
            console.error('questions overview', e);
        }
    }

    async loadList() {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = '<tr><td colspan="5">Cargando preguntas...</td></tr>';
        const params = new URLSearchParams();
        params.set('page', this.state.page);
        params.set('per_page', this.state.perPage);
        if (this.state.search) params.set('search', this.state.search);
        if (this.state.answered !== 'all') params.set('answered', String(this.state.answered));
        try {
            const resp = await fetch(`${this.cfg.endpoints.list}?${params}`, { credentials: 'same-origin' });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            this.renderTable(payload.items || []);
            this.renderPagination(payload.pagination || { page: this.state.page, per_page: this.state.perPage, total: payload.items?.length || 0, pages: 1 });
            this.loadOverview();
            if (!payload.items || !payload.items.length) {
                // No items found, just ensure debug is hidden
                if (this.debugPanel) {
                    this.debugPanel.style.display = 'none';
                    this.debugPanel.setAttribute('aria-hidden', 'true');
                }
            } else {
                if (this.debugPanel) {
                    this.debugPanel.style.display = 'none';
                    this.debugPanel.setAttribute('aria-hidden', 'true');
                    if (this.debugPre) this.debugPre.textContent = '';
                }
            }
        } catch (e) {
            console.error('questions list', e);
            this.tableBody.innerHTML = '<tr><td colspan="5">No se pudieron cargar las preguntas</td></tr>';
            if (this.debugPanel && this.debugPre) {
                this.debugPanel.style.display = '';
                this.debugPanel.removeAttribute('aria-hidden');
                this.debugPre.textContent = (e?.message || String(e)) + '\n\nURL: ' + this.cfg.endpoints.list + '?' + params.toString();
            }
        }
    }

    renderStats(stats) {
        this.statCards.forEach(card => {
            const metric = card.getAttribute('data-metric');
            const strong = card.querySelector('strong');
            if (!strong) return;
            if (metric === 'total') strong.textContent = stats.total ?? '--';
            if (metric === 'answered') strong.textContent = stats.answered ?? '--';
            if (metric === 'unanswered') strong.textContent = stats.unanswered ?? '--';
        });
        // render answered/unanswered chart
        this.renderStatusChart(stats);
    }

    renderStatusChart(stats = {}) {
        if (!this.statusCanvas || typeof Chart === 'undefined') return;
        const answered = stats.answered ?? 0;
        const unanswered = stats.unanswered ?? 0;
        if (this.charts.status) { this.charts.status.destroy(); this.charts.status = null; }
        const ctx = this.statusCanvas.getContext('2d');
        const labels = ['Respondidas', 'Sin responder'];
        const data = [answered, unanswered];
        if (data.reduce((a, b) => a + b, 0) === 0) {
            this.statusCanvas.style.display = 'none';
            if (this.statusLegend) this.statusLegend.innerHTML = '';
            return;
        }
        this.statusCanvas.style.display = '';
        this.charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: ['#3b82f6', '#f97316'] }] },
            options: {
                plugins: { legend: { display: false } }, maintainAspectRatio: false,
                onClick: (evt, elements) => {
                    if (!elements.length) return;
                    const idx = elements[0].index;
                    // 0 = Respondidas, 1 = Sin responder
                    if (idx === 0) {
                        this.state.answered = 1;
                    } else {
                        this.state.answered = 0;
                    }
                    this.state.page = 1;
                    this.loadList();
                }
            }
        });
        if (this.statusLegend) {
            this.statusLegend.innerHTML = `
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#3b82f6"></span><strong>Respondidas</strong><small>${answered}</small></span>
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#f97316"></span><strong>Sin responder</strong><small>${unanswered}</small></span>
            `;
        }
    }

    renderTable(items) {
        if (!items.length) {
            let message = 'Sin preguntas';

            // Provide specific messages for filter states
            if (this.state.answered === 1) {
                message = 'No hay preguntas respondidas';
            } else if (this.state.answered === 0) {
                message = 'No hay preguntas sin responder';
            } else if (this.state.search) {
                message = `No se encontraron preguntas con "${this.state.search}"`;
            }

            const clear = this.clearBtn ? `<button class="btn-soft" id="questions-inline-clear">Limpiar filtros</button>` : '';
            this.tableBody.innerHTML = `<tr><td colspan="5">${message} ${clear}</td></tr>`;
            setTimeout(() => {
                const btn = document.getElementById('questions-inline-clear');
                btn?.addEventListener('click', () => this.clearBtn?.click());
            }, 50);
            return;
        }
        this.tableBody.innerHTML = items.map((q) => {
            this.items.set(String(q.id), q);
            const excerpt = (q.question || '').slice(0, 80);
            const date = new Date(q.created_at);
            const isAnswered = q.answer_count > 0;
            const statusBadge = isAnswered
                ? '<span class="badge-status answered"><i class="fas fa-circle-check"></i> Respondida</span>'
                : '<span class="badge-status unanswered"><i class="fas fa-clock"></i> Pendiente</span>';
            return `
                <tr data-id="${q.id}">
                    <td><div>${excerpt}${excerpt.length === 80 ? '...' : ''}</div><small class="text-muted">${date.toLocaleString()}</small></td>
                    <td>${q.product_name || 'Producto'}</td>
                    <td>${q.customer_name || 'Cliente'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn-soft" data-action="view"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn-soft danger" data-action="delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    renderPagination(meta) {
        if (!this.pagination || !meta) return;
        const pages = Math.max(1, meta.pages || 1);
        this.state.page = Math.max(1, Math.min(pages, this.state.page));
        const info = this.pagination.querySelector('[data-role="meta"]');
        if (info) info.textContent = `${meta.total ?? 0} preguntas`;
        const buttons = this.pagination.querySelectorAll('button[data-page]');
        const [prevBtn, nextBtn] = buttons;
        if (prevBtn) prevBtn.disabled = this.state.page <= 1;
        if (nextBtn) nextBtn.disabled = this.state.page >= pages;
        // Hide pagination arrows when there is only a single page of results
        const actionsContainer = this.pagination.querySelector('.actions');
        if (actionsContainer) {
            const hidden = pages <= 1;
            actionsContainer.style.display = hidden ? 'none' : '';
            if (hidden) actionsContainer.setAttribute('aria-hidden', 'true'); else actionsContainer.removeAttribute('aria-hidden');
        }
    }

    async openDetail(id) {
        if (!id || !this.detailPanel) return;
        try {
            const detailEndpoint = this.cfg.endpoints.detail || this.cfg.endpoints.getDetails || this.cfg.endpoints.getDetail;
            if (!detailEndpoint) throw new Error('Endpoint detail no configurado');
            const resp = await fetch(`${detailEndpoint}?id=${id}`, { credentials: 'same-origin' });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            const item = payload.item;
            const answers = payload.answers || [];
            // show in UI
            const emptyState = this.detailPanel.querySelector('[data-state="empty"]');
            const content = this.detailPanel.querySelector('[data-state="content"]');
            emptyState?.setAttribute('hidden', 'hidden');
            content?.removeAttribute('hidden');
            // expand detail panel
            this.detailPanel.classList.remove('collapsed');
            this.detailPanel.setAttribute('aria-hidden', 'false');
            if (this.detailToggle) this.detailToggle.setAttribute('aria-expanded', 'true');
            content.querySelector('[data-role="title"]').textContent = 'Pregunta #' + item.id;
            content.querySelector('[data-role="product"]').textContent = item.product_name;
            content.querySelector('[data-role="date"]').textContent = new Date(item.created_at).toLocaleString();
            content.querySelector('[data-role="question"]').textContent = item.question;
            if (this.answersList) {
                this.answersList.innerHTML = answers.length ? answers.map(a => `
                <li>
                    <strong>${a.user_name || 'Admin'}</strong>
                    <span>${a.answer}</span>
                    <small class="text-muted">${new Date(a.created_at).toLocaleString()}</small>
                </li>
                `).join('') : '<li class="text-muted">Sin respuestas</li>';
            }
            if (this.answerForm) this.answerForm.dataset.questionId = id;
        } catch (e) {
            console.error('questions.detail', e);
            alert('No se pudo cargar la pregunta');
        }
    }

    formatDate(value) {
        if (!value) return 'Sin fecha';
        return new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
    }

    async deleteQuestion(id) {
        if (!id) return;
        if (!confirm('¿Eliminar esta pregunta?')) return;
        try {
            const resp = await fetch(this.cfg.endpoints.delete, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_id: id })
            });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            this.loadList();
            this.loadOverview();
            const activeDetailId = this.answerForm?.dataset.questionId;
            if (activeDetailId && String(activeDetailId) === String(id)) {
                const emptyState = this.detailPanel.querySelector('[data-state="empty"]');
                const content = this.detailPanel.querySelector('[data-state="content"]');
                content?.setAttribute('hidden', 'hidden');
                emptyState?.removeAttribute('hidden');
            }
        } catch (e) {
            console.error('questions.delete', e);
            alert('No se pudo eliminar la pregunta');
        }
    }

    async submitAnswer(e) {
        e.preventDefault();
        if (!this.answerForm) return;
        const id = this.answerForm.dataset.questionId;
        if (!id) return alert('Selecciona una pregunta');
        const fd = new FormData(this.answerForm);
        const answer = fd.get('answer');
        if (!answer) return alert('Escribe una respuesta');
        try {
            const resp = await fetch(this.cfg.endpoints.submitAnswer, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_id: id, answer: answer })
            });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            // clear form
            this.answerForm.reset();
            // refresh detail
            this.openDetail(id);
            this.loadList();
            this.loadOverview();
        } catch (err) {
            console.error('answer submit', err);
            alert('No se pudo enviar la respuesta');
        }
    }

    debounce(fn, wait) {
        let timeout;
        return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => fn(...args), wait); };
    }
}

if (window.QUESTIONS_INBOX_CONFIG) {
    document.addEventListener('DOMContentLoaded', () => new QuestionsInbox(window.QUESTIONS_INBOX_CONFIG));
}
