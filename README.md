# Ulaman — Laravel 12 Hybrid Action-Oriented Template

A production-ready Laravel 12 starter template built with **Livewire 4**, **Mary UI**, and a clean **Action/DTO architecture**. Ships with role-based access control, email verification, user management, and a full **hybrid i18n system** (static UI translations + dynamic DB content with Google Translate auto-fill).

> **Inspired by** Sufyan's service-layer architecture. **Crafted by** [Eka Nata](https://github.com/ekanata14).

---

## Features

- **Authentication & Verification** — Login, Register, Logout with mandatory email verification (`MustVerifyEmail`) and Mary UI-styled screens.
- **Two-Factor Authentication** — Fortify 2FA fully wired up (TOTP via authenticator app).
- **Role-Based Access Control** — `super_admin` and `user` roles enforced via `RoleMiddleware`. Supports pipe syntax (`role:super_admin|user`).
- **User Management** — Full CRUD with profile photo upload, role assignment, and department tagging. Admins cannot delete their own account.
- **Hybrid i18n System:**
  - *Static UI strings* — `__('...')` keys exported to `lang/en.json` / `lang/id.json`.
  - *Dynamic DB content* — `spatie/laravel-translatable` stores per-locale JSON in a single column.
  - *Auto-translation* — `AutoTranslationService` fills blank locale fields via Google Translate (no API key required).
  - *Locale & timezone sync* — User preference persisted in session + database.
- **Settings Panel** — Profile, password, 2FA, appearance, and account deletion.
- **Mary UI Components** — DaisyUI 5 + Tailwind v4 + Alpine.js.
- **Real-time ready** — Laravel Echo + Pusher configured (set your Pusher credentials in `.env`).

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 |
| Frontend | Livewire 4 + Alpine.js |
| UI Library | [Mary UI](https://mary-ui.com) (DaisyUI 5 + Tailwind v4) |
| Auth | Laravel Fortify |
| DB Translations | [`spatie/laravel-translatable`](https://github.com/spatie/laravel-translatable) |
| UI String Export | [`kkomelin/laravel-translatable-string-exporter`](https://github.com/kkomelin/laravel-translatable-string-exporter) |
| Auto-Translate | [`stichoza/google-translate-php`](https://github.com/Stichoza/google-translate-php) |
| Testing | Pest 4 |
| Linting | Laravel Pint |

---

## Quick Start

### Requirements

- PHP 8.2+
- MySQL 8+
- Node.js 18+
- Composer 2+

### Installation

```bash
git clone https://github.com/ekanata14/laravel-12-hybrid-action-oriented-template.git
cd laravel-12-hybrid-action-oriented-template

# Install all dependencies, copy .env, generate key, migrate, and build assets in one step
composer run setup
```

Or step by step:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=ulaman
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate --seed
npm install && npm run build
```

**Default admin credentials:**
- Email: `admin@ulaman.com`
- Password: `password`

### Development

```bash
composer run dev    # Starts php artisan serve + queue + pail logs + vite concurrently
```

---

## Scripts

| Command | Description |
|---------|-------------|
| `composer run dev` | Start all dev processes (server, queue, logs, vite) |
| `composer run test` | Lint check + full test suite |
| `composer run lint` | Apply Pint code style fixes |
| `composer run setup` | First-time install (install, .env, key, migrate, npm build) |
| `php artisan test --compact --filter=SomeTest` | Run a single test |

---

## Architecture

### Action-Oriented Flow

```
Livewire Component  →  DTO  →  Action  →  Model
(app/Livewire)        (DTOs)   (Actions)
```

Livewire components hold UI state and validation only. All business logic lives in single-responsibility `Action` classes that receive strongly-typed `DTO` objects.

```php
// Livewire component
public function save(CreateUserAction $action): void
{
    $this->validate();
    $action->execute(new UserData(
        name: $this->name,
        email: $this->email,
        role: $this->role,
        password: $this->password,
    ));
}

// Action
class CreateUserAction
{
    public function execute(UserData $data): User
    {
        return User::create([...]);
    }
}
```

### Hybrid i18n Workflow

**Static UI strings** (menus, buttons, error messages):

```bash
# 1. Write in Blade: {{ __('Dashboard') }}
# 2. Export keys to JSON
php artisan translatable:export en
php artisan translatable:export id
# 3. Auto-translate missing values
php artisan translate:json id
# 4. Edit lang/id.json manually to refine
```

**Dynamic DB content** (model fields that need per-locale storage):

```php
// Migration: use json column type
$table->json('name');

// Model: add HasTranslations trait
use Spatie\Translatable\HasTranslations;
class Project extends Model {
    use HasTranslations;
    public array $translatable = ['name', 'description'];
}

// In an Action: auto-fill missing locales via AutoTranslationService
$name = $this->translator->fillMissingTranslations($data->name);
// ['id' => 'Proyek Baru', 'en' => ''] → ['id' => 'Proyek Baru', 'en' => 'New Project']
```

---

## Reusable Components

### `<x-modal-confirm>` — Delete confirmation modal

```blade
<x-modal-confirm
    wire:model="deleteModalOpen"
    title="Delete this item?"
    text="This action cannot be undone."
    confirm-text="Yes, delete"
    method="delete"
/>
```

### `<x-translatable-input>` — Bilingual input (ID / EN tabs)

```blade
<x-translatable-input
    label="Project Name"
    model="name"  {{-- Livewire property must be array: ['id' => '', 'en' => ''] --}}
/>
```

---

## Security Notes

- Email verification is required before accessing the dashboard.
- Role checks are enforced at the route level via `RoleMiddleware` (not just in views).
- 2FA is available via Fortify; users can enable it in Settings.
- Profile photo uploads are validated (image, max 2 MB) and stored in `storage/app/public`.

---

## Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

MIT — see [LICENSE](LICENSE).

## Credits

- **Author:** [Eka Nata](https://github.com/ekanata14)
- **Architecture inspired by:** Sufyan's service-layer pattern
