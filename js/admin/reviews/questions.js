class QuestionsInbox {
    constructor(cfg) {
        this.cfg = cfg;
        this.state = { page: 1, perPage: 12, search: '', answered: 'all' };
        this.items = new Map();
        this.cacheDom();
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
        this.answersList = document.getElementById('question-answers');
        this.answerForm = document.getElementById('answer-form');
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
        const params = new URLSearchParams({
            page: this.state.page,
            per_page: this.state.perPage,
            search: this.state.search,
        });
        if (this.state.answered !== 'all') params.set('answered', String(this.state.answered));
        try {
            const resp = await fetch(`${this.cfg.endpoints.list}?${params}`, { credentials: 'same-origin' });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            this.renderTable(payload.items || []);
            this.renderPagination(payload.pagination || { page: this.state.page, per_page: this.state.perPage, total: payload.items?.length || 0, pages: 1 });
            this.loadOverview();
        } catch (e) {
            console.error('questions list', e);
            this.tableBody.innerHTML = '<tr><td colspan="5">No se pudieron cargar las preguntas</td></tr>';
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
    }

    renderTable(items) {
        if (!items.length) {
            this.tableBody.innerHTML = '<tr><td colspan="5">Sin preguntas</td></tr>';
            return;
        }
        this.tableBody.innerHTML = items.map((q) => {
            this.items.set(String(q.id), q);
            const excerpt = (q.question || '').slice(0, 80);
            return `
                <tr data-id="${q.id}">
                    <td><div>${excerpt}${excerpt.length === 80 ? '...' : ''}</div><small class="text-muted">${q.created_at}</small></td>
                    <td>${q.product_name || 'Producto'}</td>
                    <td>${q.customer_name || 'Cliente'}</td>
                    <td><span class="badge-ghost">${q.answer_count} respuestas</span></td>
                    <td>
                        <button class="btn-soft" data-action="view">Ver</button>
                        <button class="btn-soft" data-action="delete">Eliminar</button>
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
    }

    async openDetail(id) {
        if (!id) return;
        try {
            const resp = await fetch(`${this.cfg.endpoints.detail}?id=${id}`, { credentials: 'same-origin' });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            const item = payload.item;
            const answers = payload.answers || [];
            // show in UI
            const emptyState = this.detailPanel.querySelector('[data-state="empty"]');
            const content = this.detailPanel.querySelector('[data-state="content"]');
            emptyState?.setAttribute('hidden', 'hidden');
            content?.removeAttribute('hidden');
            content.querySelector('[data-role="title"]').textContent = 'Pregunta #' + item.id;
            content.querySelector('[data-role="product"]').textContent = item.product_name;
            content.querySelector('[data-role="date"]').textContent = new Date(item.created_at).toLocaleString();
            content.querySelector('[data-role="question"]').textContent = item.question;
            this.answersList.innerHTML = answers.length ? answers.map(a => `
                <li>
                    <strong>${a.user_name || 'Admin'}</strong>
                    <span>${a.answer}</span>
                    <small class="text-muted">${a.created_at}</small>
                </li>
            `).join('') : '<li class="text-muted">Sin respuestas</li>';
            this.answerForm.dataset.questionId = id;
        } catch (e) {
            console.error('questions.detail', e);
            alert('No se pudo cargar la pregunta');
        }
    }

    async deleteQuestion(id) {
        if (!id) return;
        if (!confirm('¿Eliminar esta pregunta?')) return;
        try {
            const resp = await fetch(this.cfg.endpoints.delete, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ question_id: id })
            });
            const payload = await resp.json();
            if (!payload.success) throw new Error(payload.message || 'API');
            this.loadList();
            this.loadOverview();
            const activeDetailId = this.answerForm.dataset.questionId;
            if (String(activeDetailId) === String(id)) {
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
        const id = this.answerForm.dataset.questionId;
        const fd = new FormData(this.answerForm);
        fd.append('question_id', id);
        const answer = fd.get('answer');
        if (!answer) return alert('Escribe una respuesta');
        try {
            const resp = await fetch(this.cfg.endpoints.submitAnswer, {
                method: 'POST',
                credentials: 'same-origin',
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
