<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        $this->applyTimezoneFromSettings();
    }

    /**
     * Terapkan timezone dari tabel settings ke runtime PHP & Carbon.
     * Fallback ke APP_TIMEZONE env atau Asia/Jakarta (WIB) jika belum diset.
     */
    protected function applyTimezoneFromSettings(): void
    {
        try {
            $timezone = Setting::get('timezone', env('APP_TIMEZONE', 'Asia/Jakarta'));
            if ($timezone && in_array($timezone, timezone_identifiers_list())) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Exception) {
            // Tabel settings belum ada (misal: sebelum migrate pertama kali)
            date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
