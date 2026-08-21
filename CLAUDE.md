# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Gretiva is a project-management starter template built on **Laravel 12 + Livewire 4 + Mary UI** (DaisyUI/Tailwind v4). Its two defining traits are a **hybrid action-oriented architecture** (thin Livewire components delegating to single-purpose Actions via DTOs) and a **hybrid i18n system** (static JSON UI strings + dynamic `spatie/laravel-translatable` DB columns with Google Translate auto-fill).

Note: README.md says "Laravel 11" but `composer.json` pins `laravel/framework: ^12.0` and runs on PHP 8.4 — trust composer/Boost, not the README prose. Source comments are in Indonesian.

## Commands

```bash
composer run dev      # Start everything: php serve + queue:listen + pail logs + vite (concurrently)
composer run test     # config:clear, then pint --test, then artisan test
composer run lint     # pint --parallel (apply formatting)
composer run setup    # First-time: install, .env, key:gen, migrate, npm build

php artisan test --compact --filter=SomeTest   # Run a single test/filter (Pest)
php artisan test tests/Feature/Auth/LoginTest.php
npm run dev / npm run build                      # Vite only (frontend changes need this)
```

Tests use Pest 4 with `RefreshDatabase` on an in-memory SQLite DB (see `phpunit.xml`). Feature tests bind `Tests\TestCase` automatically via `tests/Pest.php`.

## Architecture

### Action-oriented request flow
Livewire components hold UI state + validation only; business logic lives in Actions. The path is:

```
Livewire Component  →  builds DTO  →  Action->execute(DTO)  →  Model/persistence
(app/Livewire)         (app/DTOs)     (app/Actions)
```

- **Actions** (`app/Actions/{Domain}/`): one class, one `execute()` method, one job (e.g. `CreateUserAction`, `LoginAction`). Injected into Livewire methods via method injection — `public function login(LoginAction $action)`.
- **DTOs** (`app/DTOs/{Domain}/`): plain constructor-promoted data carriers (`LoginData`, `UserData`). Components wrap their public properties into a DTO before calling the Action.
- **Services** (`app/Services/`): cross-cutting/stateful helpers used by Actions (e.g. `AutoTranslationService`, `AuthService`).
- `app/Actions/Fortify/` is separate — these are Laravel Fortify's registered actions (`CreateNewUser`, `ResetUserPassword`), wired in `FortifyServiceProvider`. Don't confuse them with the app's own `app/Actions/Auth/`.

When adding a feature, mirror this: create the DTO, create the Action, keep the Livewire component thin. Mary UI's `Toast` trait handles user feedback (`$this->error(...)`, `$this->success(...)`).

### Routing & roles
Routes are Livewire-class-based (`Route::get('/users', AdminUserIndex::class)`), grouped by role in `routes/web.php`:
- `admin.*` prefix → `role:super_admin`
- `user.*` prefix → `role:user` + `verified`

`RoleMiddleware` (alias `role`, registered in `bootstrap/app.php`) supports pipe syntax (`role:super_admin|user`) and redirects users to their own dashboard on mismatch, with explicit loop-prevention. `RedirectIfAuthenticated` is aliased as `guest`. Email verification is mandatory (`User implements MustVerifyEmail`).

### Hybrid i18n (the core feature)
Two independent translation layers, both kept in sync with a session+DB-backed locale:

1. **Static UI strings** — `__('...')` keys exported to `lang/{en,id}.json`.
   - `php artisan translatable:export {locale}` (kkomelin) scans Blade/PHP for `__()` keys into the JSON file.
   - `php artisan translate:json {locale}` (custom, `app/Console/Commands/AutoTranslateJson.php`) then Google-Translates any untranslated entries (where value === key), preserving manual translations. Rate-limited with `usleep`.
2. **Dynamic DB content** — `spatie/laravel-translatable` `HasTranslations` stores per-locale JSON in a single column. `AutoTranslationService::fillMissingTranslations(['id'=>..., 'en'=>...])` auto-translates whichever language is left blank on save.

Locale resolution: `SetLocale` middleware (appended to `web` group) prefers `Session('locale')`, falls back to the authenticated user's `locale` column. `SetTimezone` middleware does the same for `User->timezone`. Both columns plus `preferences` (cast to `array`) live on the `User` model.

### Frontend
Mary UI components (`<x-...>`, Mary traits like `Toast`) over Tailwind v4 + DaisyUI 5 + Alpine. Real-time bits use Laravel Echo + Pusher (`pusher/pusher-php-server`, configured via `BROADCAST_CONNECTION`; channels in `routes/channels.php`). Notifications: `app/Notifications/` + `app/Livewire/NavbarNotifications.php`.

## Conventions (from Laravel Boost / GEMINI.md)

- Follow existing sibling-file structure; don't add top-level directories or change dependencies without approval.
- PHP: constructor property promotion, explicit return types, type-hinted params, curly braces always, PHPDoc over inline comments.
- Every change must be tested — write/update a Pest test and run it (`php artisan test --compact --filter=...`). Don't write tinker/verification scripts when a test can prove it.
- `app/Concerns/` holds shared validation rule traits (`PasswordValidationRules`, `ProfileValidationRules`); `app/Http/Requests/` holds FormRequests for non-Livewire validation.
- Laravel Boost MCP is configured — prefer its `search-docs`, `tinker`, `database-query`, and `list-artisan-commands` tools for Laravel ecosystem work.

---

# Ulaman Purchase Log (UPL) — Project Extension

> Section ini DITAMBAHKAN di atas template Gretiva. Konvensi Gretiva di atas tetap berlaku penuh.

## Konteks Proyek
Ulaman Purchase Log adalah sistem pencatatan pembelian barang proyek renovasi Ulaman, dibangun DI ATAS template ini. Publik (Guest, tanpa login) menelusuri nota/laporan; Admin & Super Admin mengelola nota, diskon/bundle, foto nota, master data, impor Excel, dan audit log. Sumber data awal: `docs/Ulaman Renovation.xlsx` (16 sheet, ±730 item, pola "Date & Supplier hanya diisi di baris pertama tiap nota").

## Keputusan Rekonsiliasi (final — jangan dibahas ulang)
- Stack: **Laravel 12 + Livewire 4 + Fortify + Mary UI** (BUKAN Laravel 11/Breeze). Jangan `laravel new`, jangan pasang Breeze.
- Action: **kelas polos, satu method `execute(DTO)`, method-injected** (pola template). BUKAN lorisleiva. §22.D#2 dibaca "satu public method `execute()`".
- DTO: **polos constructor-promoted di `app/DTOs/{Domain}/`** (BUKAN `app/Data`/spatie-data). Uang & qty bertipe `string` di DTO demi presisi bcmath.
- Folder Action per domain di `app/Actions/{Calculation,Purchase,Photo,Supplier,Item,Import,Report,Export,Maintenance}/` + `Actions/Concerns/`. `app/Actions/Fortify` milik template — jangan diutak-atik.
- Role: **`super_admin|admin`** (migrasi ubah enum template `user`→`admin`); area admin = `role:super_admin|admin`.
- Uang: **DECIMAL(18,2) + bcmath + `App\Support\Money`**; pembulatan **half-up** ke rupiah bulat; cast Eloquent `decimal:2`.
- Auth: Fortify existing; **registrasi publik dimatikan**; sesi 8 jam; rate-limit 5 gagal/15 mnt; argon2id; pesan gagal generik.
- Area Guest publik (`/`, `/nota/{id}`, `/laporan`) TANPA middleware auth.
- Tabel `users` simpan `name` (Inggris); migrasi baru tambah `is_active` + `last_login_at`. Tabel domain baru pakai nama kolom Indonesia (§8).
- Tooling: Pest 4 (existing) + **Larastan** (`phpstan.neon` level 5 + `phpstan-baseline.neon` untuk debt template lama) di DoD & CI. Pint preset `laravel`. Analisis jalankan dengan `--memory-limit=1G`.
- Paket terpasang di Fase 0 (Q1 dipertahankan — TANPA lorisleiva/spatie-data): `maatwebsite/excel`, `intervention/image`, `barryvdh/laravel-dompdf`, dev `larastan/larastan` + `pestphp/pest-plugin-livewire`; JS `browser-image-compression`, `sortablejs`, `chart.js`.

## Larangan Mutlak
1. **DILARANG `float`/`double` untuk uang.** Wajib `bcmath` + `DECIMAL(18,2)` + cast `decimal:2`. Uang mengalir sebagai `string`.
2. **DILARANG logika bisnis / aritmetika uang di komponen Livewire.** Komponen hanya state form, validasi presentasi, panggil Action, render.
3. **DILARANG business rule di model event Eloquent** (`saving`/`saved`/observer). Total dihitung Action, bukan model.
4. **DILARANG `request()`/`auth()`/`session()` di dalam `execute()` Action.** User yang bertindak dioper sebagai parameter eksplisit.
5. **DILARANG `DB::transaction()` di luar Action orchestrator.** Sub-action tidak membuka transaksi sendiri.
6. Kalkulator (`app/Actions/Calculation/*`) **tidak menyentuh DB** — input array/DTO, output DTO, unit-test 100%.
7. Komponen Guest **tanpa method mutasi** apa pun.

## Checklist Arsitektur (§22.D — wajib lulus tiap review)
1. Tidak ada operasi uang di `app/Livewire/**`.
2. Tiap Action tepat satu public method (`execute()`).
3. Tidak ada `request()`/`auth()`/`session()` di dalam `execute()`.
4. `DB::transaction()` hanya di Action orchestrator.
5. Tiap invariant §7 divalidasi di Action, bukan hanya di Form Object.
6. Tiap baris Livewire dinamis punya `wire:key` berbasis `uid`, bukan index array.
7. Tidak ada business rule di model event Eloquent.
8. Semua nilai uang `string`/`decimal`, tanpa float.
9. Komponen Guest tanpa method mutasi.
10. Eager loading di tiap query list (anti N+1).

## Asumsi dari Default PRD §20 (dicatat, bukan diputuskan ulang)
- Q6 Satuan **opsional/nullable** (data lama tak punya unit).
- Q7 Seed **2–3 akun admin** (1 super_admin + contoh).
- Q8 `purchases.project_id` **nullable disiapkan sekarang**, UI multi-proyek nanti.
- Q9 Diskon tingkat nota **masuk MVP** (prioritas Should) — dibangun di mesin Fase 1.
- Q10 **Tanpa PPN/VAT** di MVP.
- Q1/Q2/Q3 Ketidakcocokan data (merge supplier, selisih total, tahun salah) → tandai `needs_review`, koreksi manual pasca-impor.
- Q4/Q5 Guest boleh unduh & lihat Foto Nota, default aktif + bisa dimatikan via setting (Fase 4/5).
- Q11 Hosting VPS + Forge + Redis (fase deploy).

## Perintah Verifikasi (wajib lulus sebelum fitur dianggap selesai)
```bash
composer lint                                   # Pint apply
composer test                                   # config:clear -> pint --test -> artisan test
php artisan test --compact --filter=NamaTest    # test spesifik (Pest)
./vendor/bin/pest                               # sesuai gate CI
vendor/bin/phpstan analyse                       # Larastan (setelah ditambahkan)
php artisan purchase:verify-totals              # invariant §7: Sum(net_item) == grand_total
```
Definition of Done per fitur: Action ada test · komponen Livewire ada test · Larastan clean · Pint clean · validasi server penuh · tanpa N+1 · uang bebas float.

