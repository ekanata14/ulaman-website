<?php

use App\Http\Controllers\NotaPhotoController;
use App\Livewire\Admin\AuditLog\Index as AuditLogIndex;
// Auth Routes
use App\Livewire\Admin\Category\Index as CategoryIndex;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\GlobalSearch;
use App\Livewire\Admin\Import\Wizard as ImportWizard;
use App\Livewire\Admin\Item\Index as ItemIndex;
use App\Livewire\Admin\Purchase\Form as PurchaseForm;
use App\Livewire\Admin\Purchase\Index as PurchaseIndex;
use App\Livewire\Admin\Purchase\Spreadsheet as PurchaseSpreadsheet;
use App\Livewire\Admin\Supplier\Index as SupplierIndex;
use App\Livewire\Admin\Unit\Index as UnitIndex;
use App\Livewire\Admin\User\Index as AdminUserIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
// Email Verification Routes
use App\Livewire\Auth\ResetPassword;
// Route khusus untuk handle klik link dari email (Laravel Handle Otomatis)
use App\Livewire\Auth\VerifyEmail;
// Settings Route
use App\Livewire\Guest\PurchaseBrowser;
// Admin Routes
use App\Livewire\Settings;
use App\Livewire\User\Dashboard as UserDashboard;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
// User Routes
use Illuminate\Support\Facades\Route;

// Halaman publik guest (tanpa login) — rate limit 60 request/menit per IP (§F-06).
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/', PurchaseBrowser::class)->name('home');
});

// Akses Foto Nota HANYA lewat signed URL 15 menit (§F-05). Tidak ada path publik.
Route::get('/nota/foto/{photo}', NotaPhotoController::class)->name('nota.photo')->middleware('signed');

// Route khusus untuk halaman "Please Verify"
Route::get('/email/verify', VerifyEmail::class)
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('user.dashboard'); // Redirect kemana setelah sukses
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::middleware(['auth'])->group(function () {
    // ... route dashboard lainnya

    // Route Settings (Bisa diakses semua user yang login)
    Route::get('/settings', Settings::class)->name('settings');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');

    // Halaman Request Link
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');

    // Halaman Input Password Baru (Link dari Email akan mengarah kesini)
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::get('/register', Register::class)->name('register')->middleware('guest');

Route::middleware(['auth', 'role:super_admin|admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

    // Nota Pembelian (§11.1)
    Route::get('/nota', PurchaseIndex::class)->name('purchases');
    Route::get('/nota/create', PurchaseForm::class)->name('purchases.create');
    Route::get('/nota/{purchase}/edit', PurchaseForm::class)->name('purchases.edit');

    // Mode Spreadsheet — editor datar per-item
    Route::get('/spreadsheet', PurchaseSpreadsheet::class)->name('spreadsheet');

    // Master Data (§11.4)
    Route::get('/supplier', SupplierIndex::class)->name('suppliers');
    Route::get('/barang', ItemIndex::class)->name('items');
    Route::get('/satuan', UnitIndex::class)->name('units');
    Route::get('/kategori', CategoryIndex::class)->name('categories');

    // Impor & Audit (§F-09 / §F-10)
    Route::get('/import', ImportWizard::class)->name('import');
    Route::get('/audit-log', AuditLogIndex::class)->name('audit-logs');

    // Khusus Super Admin
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/global-search', GlobalSearch::class)->name('global-search');
        Route::get('/users', AdminUserIndex::class)->name('users');
    });
});

Route::middleware(['auth', 'verified', 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
});
