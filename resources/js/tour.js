/**
 * Spotlight tour onboarding untuk area admin (§Tutorial Interaktif).
 *
 * Konfigurasi (route aktif, langkah per-route, label tombol, flag auto-start)
 * di-render oleh Blade ke `window.__adminTour` (lihat partials/admin-tour.blade.php).
 * Modul ini hanya membaca konfigurasi tsb lalu menjalankan Driver.js pada DOM
 * halaman yang sedang aktif. Tidak ada teks yang di-hardcode di sini — semuanya
 * mengikuti sistem i18n Laravel.
 */

import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * Bangun daftar langkah Driver.js dari konfigurasi, dan buang langkah yang
 * elemen anchornya tidak ada di halaman (mis. menu "Users" untuk non-super-admin).
 */
const buildSteps = (rawSteps) =>
    (rawSteps || [])
        .filter((s) => document.querySelector(s.selector))
        .map((s) => ({
            element: s.selector,
            popover: {
                title: s.title,
                description: s.description,
                side: s.side || 'bottom',
                align: s.align || 'start',
            },
        }));

const startTour = () => {
    const config = window.__adminTour;
    if (!config || !config.route) {
        return;
    }

    const steps = buildSteps(config.steps?.[config.route]);
    if (steps.length === 0) {
        return;
    }

    const labels = config.labels || {};

    const drive = driver({
        showProgress: true,
        allowClose: true,
        overlayColor: 'oklch(0.2 0.02 260 / 0.65)',
        nextBtnText: labels.next || 'Next',
        prevBtnText: labels.prev || 'Back',
        doneBtnText: labels.done || 'Done',
        progressText: labels.progress || '{{current}} / {{total}}',
        steps,
        onDestroyed: () => {
            // Tandai onboarding selesai di server (dibiarkan gagal-diam bila
            // Livewire belum siap — flag auto-start hanya soal kenyamanan).
            if (window.Livewire) {
                window.Livewire.dispatch('tour-finished');
            }
        },
    });

    drive.drive();
};

// Pemicu manual dari tombol "?" (event browser) maupun dari Livewire (resetTour).
window.addEventListener('admin-tour:start', startTour);
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.on('admin-tour:start', startTour);
    }
});

// Auto-start saat login pertama (hanya di halaman Dashboard, flag dari server).
document.addEventListener('livewire:navigated', () => {
    if (window.__adminTour?.autoStart) {
        // Beri jeda agar chart/komponen Livewire sempat ter-render.
        setTimeout(startTour, 600);
    }
});
