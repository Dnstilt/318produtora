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
                el.textContent = status;
                results.set(id, status);
            } catch (e) {}
        })
    );

    return results;
}

window.photoManager = function photoManager(initialPhotos) {
    return {
        photos: initialPhotos,
        draggingId: null,
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
        onDeleteSubmit(id) {
            this.photos = this.photos.filter((p) => p.id !== id);
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

        for (const input of fileInputs) {
            input.disabled = true;
        }

        const textareas = Array.from(form.querySelectorAll('textarea'));
        for (const t of textareas) t.readOnly = true;

        const inputs = Array.from(form.querySelectorAll('input:not([type="file"]):not([type="submit"])'));
        for (const i of inputs) i.readOnly = true;

        return true;
    };

    Array.from(document.querySelectorAll('form.js-admin-form')).forEach((form) => {
        form.addEventListener('submit', () => {
            setFormSubmitting(form);
        });
    });

    let pollTimer = null;
    const trackedSectionIds = new Set();
    const videoBanner = document.getElementById('js-video-processing-banner');
    const videoUploadForms = Array.from(document.querySelectorAll('form.js-video-upload-form'));

    const setVideoUploadsEnabled = (enabled, message) => {
        for (const form of videoUploadForms) {
            const button = form.querySelector('button[type="submit"], input[type="submit"]');
            const file = form.querySelector('input[type="file"]');
            if (button) button.disabled = !enabled;
            if (file) file.disabled = !enabled;
        }

        if (!videoBanner) return;
        if (enabled) {
            videoBanner.classList.add('hidden');
            videoBanner.textContent = '';
        } else {
            videoBanner.classList.remove('hidden');
            videoBanner.textContent = message || 'Processamento em andamento. Aguarde concluir para enviar outro vídeo.';
        }
    };

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
            for (const [id, status] of statuses.entries()) {
                if (status !== 'pending' && status !== 'processing') {
                    trackedSectionIds.delete(id);
                }
            }

            if (trackedSectionIds.size === 0) {
                clearInterval(pollTimer);
                pollTimer = null;
                setVideoUploadsEnabled(true);
                sessionStorage.removeItem('adminActiveVideoSectionId');
            }
        };

        tick();
        pollTimer = setInterval(tick, 3000);
    };

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

    Array.from(document.querySelectorAll('form[action*="/admin/sections/"][action$="/video"]')).forEach((form) => {
        form.addEventListener('submit', () => {
            const match = form.getAttribute('action')?.match(/\/admin\/sections\/(\d+)\/video$/);
            if (match) {
                const id = Number(match[1]);
                trackedSectionIds.add(id);
                sessionStorage.setItem('adminActiveVideoSectionId', String(id));
            }
            setVideoUploadsEnabled(false);
            startPolling();
        });
    });
});
