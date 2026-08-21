/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

import imageCompression from 'browser-image-compression';

// Kompresi gambar di sisi klien sebelum upload Livewire (§F-05).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('photoUpload', () => ({
        uploading: false,
        progress: 0,
        async handle(event) {
            const files = Array.from(event.target.files || []);
            if (!files.length) {
                return;
            }

            this.uploading = true;
            this.progress = 0;

            const options = { maxSizeMB: 1.5, maxWidthOrHeight: 2000, useWebWorker: true };
            const compressed = [];
            for (const file of files) {
                try {
                    compressed.push(await imageCompression(file, options));
                } catch (error) {
                    compressed.push(file);
                }
            }

            this.$wire.uploadMultiple(
                'photos',
                compressed,
                () => {
                    this.uploading = false;
                    this.progress = 0;
                    this.$refs.input.value = '';
                    this.$wire.storeUploaded();
                },
                () => {
                    this.uploading = false;
                },
                (progressEvent) => {
                    this.progress = progressEvent.detail.progress;
                },
            );
        },
    }));
});
