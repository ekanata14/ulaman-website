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

// --- Penyaringan berkas unggahan (dipakai foto nota & bukti transfer) ---------
// HEIC/HEIF adalah format bawaan kamera iPhone. Browser non-Safari tidak bisa
// mendekodenya dan server tidak mendukungnya, jadi berkas seperti itu disaring
// di klien: kalau lolos, Livewire melempar FileNotPreviewableException saat
// merender pratinjau dan berkasnya tetap ditolak server.
const HEIC_EXTENSIONS = /\.(heic|heif|hif)$/i;

const IMAGE_COMPRESSION = { maxSizeMB: 1.5, maxWidthOrHeight: 2000, useWebWorker: true };

function isHeicFile(file) {
    return file.type.startsWith('image/hei') || HEIC_EXTENSIONS.test(file.name || '');
}

/**
 * Kompres gambar (bila browser sanggup), lalu saring berkas yang tetap tidak
 * didukung atau melebihi batas. Pemeriksaan ukuran dilakukan SETELAH kompresi
 * supaya foto besar yang bisa dikecilkan tidak ikut ditolak.
 *
 * @returns {Promise<{ready: File[], errors: string[]}>}
 */
async function prepareUploads(files, maxMb) {
    const ready = [];
    const errors = [];
    const maxBytes = maxMb * 1024 * 1024;

    for (const file of files) {
        const name = file.name || 'berkas';
        let prepared = file;

        if (file.type.startsWith('image/')) {
            try {
                prepared = await imageCompression(file, IMAGE_COMPRESSION);
            } catch (error) {
                prepared = file;
            }
        }

        if (isHeicFile(prepared)) {
            errors.push(`${name}: format HEIC/HEIF belum didukung. Ubah format kamera iPhone ke "Paling Kompatibel", atau pilih foto JPG/PNG.`);
            continue;
        }

        if (maxBytes > 0 && prepared.size > maxBytes) {
            errors.push(`${name}: ukuran melebihi ${maxMb} MB.`);
            continue;
        }

        ready.push(prepared);
    }

    return { ready, errors };
}

/**
 * Factory bersama untuk komponen unggah: menyaring di klien, menampilkan alasan
 * penolakan, lalu mengunggah sisanya ke properti Livewire yang dituju.
 */
function uploader(maxMb, property, afterUpload = null) {
    return {
        uploading: false,
        progress: 0,
        errors: [],

        async handle(event) {
            const files = Array.from(event.target.files || []);
            if (!files.length) {
                return;
            }

            const { ready, errors } = await prepareUploads(files, maxMb);
            this.errors = errors;

            if (!ready.length) {
                this.$refs.input.value = '';

                return;
            }

            this.uploading = true;
            this.progress = 0;

            this.$wire.uploadMultiple(
                property,
                ready,
                () => {
                    this.uploading = false;
                    this.progress = 0;
                    this.$refs.input.value = '';
                    if (afterUpload) {
                        this.$wire[afterUpload]();
                    }
                },
                () => {
                    this.uploading = false;
                },
                (progressEvent) => {
                    this.progress = progressEvent.detail.progress;
                },
            );
        },
    };
}

// Foto nota: berkas menunggu di properti `photos`, lalu langsung disimpan (§F-05).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('photoUpload', (maxMb) => uploader(maxMb, 'photos', 'storeUploaded'));
});

// Bukti transfer: kompres hanya gambar (PDF diunggah apa adanya) dan biarkan
// menggantung di properti `buktiTransfers` sampai nota disimpan (§F-05).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('buktiTransferUpload', (maxMb) => uploader(maxMb, 'buktiTransfers'));
});
