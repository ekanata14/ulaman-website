/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import './tour';

import Chart from 'chart.js/auto';
import imageCompression from 'browser-image-compression';

// Input harga dengan pemisah ribuan otomatis (id-ID). Tampilan diformat, tapi
// nilai mentah (desimal titik) yang didorong ke Livewire/DB — tanpa float, tanpa
// mengubah sisi server. Dipakai lewat komponen <x-money-input> & inline spreadsheet.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('moneyInput', (initial = '') => ({
        display: '',
        init() {
            this.display = this.format(initial);
        },
        // string mentah (desimal '.') -> tampilan id-ID (ribuan '.', desimal ',')
        format(raw) {
            if (raw === null || raw === undefined) {
                return '';
            }
            let s = String(raw).trim().replace(/[^0-9.\-]/g, '');
            if (s === '' || s === '-') {
                return '';
            }
            const neg = s.startsWith('-');
            s = s.replace(/-/g, '');
            let [int, dec] = s.split('.');
            int = (int || '').replace(/^0+(?=\d)/, '') || '0';
            const grouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            let out = grouped;
            if (dec !== undefined && dec.replace(/0+$/, '') !== '') {
                out = grouped + ',' + dec;
            }
            return (neg ? '-' : '') + out;
        },
        // tampilan (ribuan '.', desimal ',') -> string mentah untuk server (desimal '.')
        unmask(display) {
            if (display === null || display === undefined) {
                return '';
            }
            let s = String(display).trim()
                .replace(/\./g, '')
                .replace(',', '.')
                .replace(/[^0-9.\-]/g, '');
            const parts = s.split('.');
            if (parts.length > 2) {
                s = parts[0] + '.' + parts.slice(1).join('');
            }
            return s === '-' || s === '' ? '' : s;
        },
    }));
});

// Grafik tren belanja bulanan di dashboard (§Dashboard). Di-bundle via Vite —
// import bare-specifier tidak bisa di-resolve bila ditulis inline di @script.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('spendingChart', (config) => ({
        chart: null,
        init() {
            this.$nextTick(() => this.build());
        },
        build() {
            const canvas = this.$refs.canvas;
            if (!canvas) {
                return;
            }
            if (this.chart) {
                this.chart.destroy();
            }
            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: config.currency,
                        data: config.totals,
                        backgroundColor: 'rgba(168, 137, 76, 0.85)',
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (c) => 'Rp ' + new Intl.NumberFormat('id-ID').format(c.parsed.y),
                            },
                        },
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: (v) => 'Rp ' + new Intl.NumberFormat('id-ID', {
                                    notation: 'compact',
                                }).format(v),
                            },
                        },
                    },
                },
            });
        },
        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },
    }));
});

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

// Bukti transfer: kompres hanya gambar (PDF diunggah apa adanya) dan biarkan
// menggantung di properti `buktiTransfers` sampai nota disimpan (§F-05).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('buktiTransferUpload', () => ({
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
            const prepared = [];
            for (const file of files) {
                if (file.type && file.type.startsWith('image/')) {
                    try {
                        prepared.push(await imageCompression(file, options));
                    } catch (error) {
                        prepared.push(file);
                    }
                } else {
                    prepared.push(file);
                }
            }

            this.$wire.uploadMultiple(
                'buktiTransfers',
                prepared,
                () => {
                    this.uploading = false;
                    this.progress = 0;
                    this.$refs.input.value = '';
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
