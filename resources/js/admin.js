import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/skins/ui/oxide/skin.css';
import 'tinymce/skins/content/default/content.css';

function initEditors() {
    const editors = Array.from(document.querySelectorAll('textarea.js-editor'));
    if (editors.length === 0) return;

    tinymce.init({
        selector: 'textarea.js-editor',
        menubar: false,
        height: 360,
        plugins: 'link lists',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link',
        license_key: 'gpl',
        skin: false,
        content_css: false,
    });

    const forms = new Set(editors.map((el) => el.closest('form')).filter(Boolean));
    for (const form of forms) {
        form.addEventListener('submit', () => {
            tinymce.triggerSave();
        });
    }
}

async function pollSectionStatuses(sectionIds) {
    const allStatusEls = Array.from(document.querySelectorAll('.js-section-status'));
    if (allStatusEls.length === 0) return new Map();

    const byId = new Map(
        allStatusEls
            .map((el) => [Number(el.dataset.sectionId), el])
            .filter(([id]) => Number.isFinite(id) && id > 0)
    );

    const idsToPoll = Array.isArray(sectionIds)
        ? sectionIds.map((n) => Number(n)).filter((n) => Number.isFinite(n) && n > 0)
        : Array.from(byId.keys());

    const results = new Map();

    await Promise.all(
        idsToPoll.map(async (id) => {
            const el = byId.get(id);
            if (!el) return;

            try {
                const res = await fetch(`/admin/sections/${id}/status`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const json = await res.json();
                const status = (json?.status || '').toString().trim();
                const error = (json?.error || '').toString().trim();
                el.textContent = status;
                results.set(id, { status, error });
            } catch (e) { }
        })
    );

    return results;
}

window.photoManager = function photoManager(initialPhotos) {
    return {
        photos: initialPhotos,
        draggingId: null,
        deletingId: null,
        onDragStart(id) {
            this.draggingId = id;
        },
        async onDrop(targetId) {
            if (!this.draggingId || this.draggingId === targetId) return;

            const fromIndex = this.photos.findIndex((p) => p.id === this.draggingId);
            const toIndex = this.photos.findIndex((p) => p.id === targetId);
            if (fromIndex < 0 || toIndex < 0) return;

            const next = [...this.photos];
            const [moved] = next.splice(fromIndex, 1);
            next.splice(toIndex, 0, moved);
            this.photos = next;
            this.draggingId = null;

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await fetch('/admin/photos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ orders: this.photos.map((p) => p.id) }),
            });
        },
        async onDeleteSubmit(event, id) {
            if (!window.confirm('Você quer mesmo excluir essa foto?')) {
                // Restore form submitting state since it was blocked
                const form = event.target;
                form.dataset.submitting = '0';
                const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
                for (const btn of submitButtons) {
                    btn.disabled = false;
                    btn.textContent = btn.dataset.originalLabel || 'Excluir';
                }
                return false;
            }

            const form = event.target;
            const action = (form?.getAttribute('action') || '').toString();
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            if (!action || !csrf) {
                window.location.href = action || window.location.href;
                return true;
            }

            this.deletingId = id;
            console.log('[photos.delete] start', { id, action });

            try {
                const res = await fetch(action, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await res.json().catch(() => null);
                if (!res.ok || payload?.ok !== true) {
                    const message = (payload?.message || '').toString().trim() || 'Não foi possível remover a foto.';
                    throw new Error(message);
                }

                console.log('[photos.delete] success', { id });
                this.photos = this.photos.filter((p) => p.id !== id);
                return true;
            } catch (e) {
                console.log('[photos.delete] error', { id, error: e?.message || e });
                window.alert(e?.message || 'Não foi possível remover a foto.');

                form.dataset.submitting = '0';
                const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
                for (const btn of submitButtons) {
                    btn.disabled = false;
                    btn.textContent = btn.dataset.originalLabel || 'Excluir';
                }

                return false;
            } finally {
                this.deletingId = null;
            }
        },
    };
};

window.addEventListener('DOMContentLoaded', () => {
    initEditors();

    const setFormSubmitting = (form) => {
        if (form.dataset.submitting === '1') return false;
        form.dataset.submitting = '1';

        const loadingText = (form.dataset.loadingText || '').trim();
        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        const fileInputs = Array.from(form.querySelectorAll('input[type="file"]'));

        for (const btn of submitButtons) {
            if (!btn.dataset.originalLabel) {
                btn.dataset.originalLabel = btn.tagName === 'INPUT' ? btn.value : btn.textContent || '';
            }
            btn.disabled = true;
            if (loadingText) {
                if (btn.tagName === 'INPUT') btn.value = loadingText;
                else btn.textContent = loadingText;
            }
        }

        const textareas = Array.from(form.querySelectorAll('textarea'));
        for (const t of textareas) t.readOnly = true;

        const inputs = Array.from(form.querySelectorAll('input:not([type="file"]):not([type="submit"])'));
        for (const i of inputs) i.readOnly = true;

        return true;
    };

    let pollTimer = null;
    const trackedSectionIds = new Set();
    const notifiedSectionIds = new Set();
    const videoBanner = document.getElementById('js-video-processing-banner');
    const videoUploadForms = Array.from(document.querySelectorAll('form.js-video-upload-form'));

    const readStatusesById = () => {
        const statusEls = Array.from(document.querySelectorAll('.js-section-status'));
        const byId = new Map();
        for (const el of statusEls) {
            const id = Number(el.dataset.sectionId);
            if (!Number.isFinite(id) || id <= 0) continue;
            byId.set(id, (el.textContent || '').trim());
        }
        return byId;
    };

    const setVideoBanner = (kind, message) => {
        if (!videoBanner) return;

        videoBanner.classList.remove('hidden');
        videoBanner.classList.remove('bg-amber-50', 'text-amber-900', 'bg-green-50', 'text-green-800', 'bg-red-50', 'text-red-800');

        if (kind === 'success') {
            videoBanner.classList.add('bg-green-50', 'text-green-800');
        } else if (kind === 'error') {
            videoBanner.classList.add('bg-red-50', 'text-red-800');
        } else {
            videoBanner.classList.add('bg-amber-50', 'text-amber-900');
        }

        videoBanner.textContent = message || '';
        if (!videoBanner.textContent) {
            videoBanner.classList.add('hidden');
        }
    };

    const setVideoUploadsEnabled = (enabled, message) => {
        for (const form of videoUploadForms) {
            const button = form.querySelector('button[type="submit"], input[type="submit"]');
            if (button) button.disabled = !enabled;
        }

        if (enabled) {
            setVideoBanner('info', '');
        } else {
            setVideoBanner('info', message || 'Processamento em andamento. Aguarde concluir para enviar outro vídeo.');
        }
    };

    const startPolling = () => {
        if (pollTimer) return;

        const tick = async () => {
            const ids = Array.from(trackedSectionIds);
            if (ids.length === 0) {
                clearInterval(pollTimer);
                pollTimer = null;
                return;
            }
            const statuses = await pollSectionStatuses(ids);
            let completedDone = 0;
            let completedError = 0;
            let lastErrorMessage = '';

            for (const [id, info] of statuses.entries()) {
                const status = info?.status;
                const error = info?.error;

                if (status !== 'pending' && status !== 'processing') {
                    trackedSectionIds.delete(id);
                    if (!notifiedSectionIds.has(id)) {
                        notifiedSectionIds.add(id);
                        if (status === 'done') {
                            completedDone += 1;
                        } else if (status === 'error') {
                            completedError += 1;
                            if (error) lastErrorMessage = error;
                        }
                    }
                }
            }

            if (trackedSectionIds.size === 0) {
                clearInterval(pollTimer);
                pollTimer = null;
                setVideoUploadsEnabled(true);
                sessionStorage.removeItem('adminActiveVideoSectionId');
                if (completedDone > 0) {
                    setVideoBanner('success', completedDone === 1 ? 'Conversão concluída com sucesso.' : `Conversões concluídas com sucesso: ${completedDone}.`);
                    setTimeout(() => setVideoBanner('success', ''), 6000);
                } else if (completedError > 0) {
                    setVideoBanner('error', lastErrorMessage ? `Falha na conversão: ${lastErrorMessage}` : 'Falha na conversão. Verifique o status da seção.');
                }
            }
        };

        tick();
        pollTimer = setInterval(tick, 3000);
    };

    Array.from(document.querySelectorAll('form.js-admin-form')).forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!setFormSubmitting(form)) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            const action = (form.getAttribute('action') || '').toString();
            const isVideoForm =
                form.classList.contains('js-video-upload-form') ||
                (action.includes('/admin/sections/') && action.endsWith('/video'));

            if (isVideoForm) {
                const match = action.match(/\/admin\/sections\/(\d+)\/video$/);
                if (match) {
                    const id = Number(match[1]);
                    trackedSectionIds.add(id);
                    sessionStorage.setItem('adminActiveVideoSectionId', String(id));
                }
                setVideoUploadsEnabled(false);
                startPolling();
            }
        });
    });

    const initialStatusesById = readStatusesById();
    for (const [id, status] of initialStatusesById.entries()) {
        if (status === 'processing') {
            trackedSectionIds.add(id);
        }
    }

    const activeSectionId = Number(sessionStorage.getItem('adminActiveVideoSectionId'));
    if (Number.isFinite(activeSectionId) && activeSectionId > 0) {
        const status = initialStatusesById.get(activeSectionId);
        if (status === 'pending' || status === 'processing') {
            trackedSectionIds.add(activeSectionId);
            setVideoUploadsEnabled(false);
        } else {
            sessionStorage.removeItem('adminActiveVideoSectionId');
        }
    }

    if (trackedSectionIds.size > 0) {
        startPolling();
    }
});
