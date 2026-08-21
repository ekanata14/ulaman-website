<?php

namespace App\Providers;

use App\Events\PurchaseSaved;
use App\Listeners\SyncItemLastPrice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Cegah lazy loading (anti N+1) di luar produksi — §22.D#10.
        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );

        Relation::morphMap([
            'revision' => 'App\Models\RevisionThread',
            'report' => 'App\Models\JobdeskReport',
            // 'jobdesk' => 'App\Models\Jobdesk', // Jika jobdesk punya attachment langsung
        ]);

        // UPL: perbarui harga terakhir item setiap nota tersimpan (§10.4).
        Event::listen(PurchaseSaved::class, SyncItemLastPrice::class);
    }
}
