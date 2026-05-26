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

async function pollSectionStatuses() {
    const statusEls = Array.from(document.querySelectorAll('.js-section-status'));
    if (statusEls.length === 0) return;

    await Promise.all(
        statusEls.map(async (el) => {
            const id = el.dataset.sectionId;
            try {
                const res = await fetch(`/admin/sections/${id}/status`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const json = await res.json();
                el.textContent = json.status;
            } catch (e) {}
        })
    );
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
    let pollTimer = null;
    let pollingEnabled = false;

    const readStatuses = () =>
        Array.from(document.querySelectorAll('.js-section-status')).map((el) => (el.textContent || '').trim());

    const startPolling = () => {
        if (pollTimer) return;
        pollingEnabled = true;

        const tick = async () => {
            await pollSectionStatuses();

            if (!pollingEnabled) {
                clearInterval(pollTimer);
                pollTimer = null;
                return;
            }

            const statuses = readStatuses();
            const shouldKeepPolling = statuses.some((s) => s === 'pending' || s === 'processing');
            if (!shouldKeepPolling) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        };

        tick();
        pollTimer = setInterval(tick, 3000);
    };

    const initialStatuses = readStatuses();
    if (initialStatuses.some((s) => s === 'processing')) {
        startPolling();
    }

    Array.from(document.querySelectorAll('form[action*="/admin/sections/"][action$="/video"]')).forEach((form) => {
        form.addEventListener('submit', () => {
            startPolling();
        });
    });
});
