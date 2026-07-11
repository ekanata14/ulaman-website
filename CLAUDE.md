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

