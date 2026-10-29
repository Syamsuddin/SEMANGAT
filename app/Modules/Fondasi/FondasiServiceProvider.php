<?php

namespace App\Modules\Fondasi;

use App\Modules\Fondasi\Actions\AuthenticateUser;
use App\Modules\Fondasi\Http\Responses\LoginResponse;
use App\Modules\Fondasi\Listeners\RecordSuccessfulLogin;
use App\Modules\Fondasi\Listeners\RefreshAccountsAfterSync;
use App\Modules\Fondasi\Livewire\EmployeeIndex;
use App\Modules\Fondasi\Livewire\EmployeeShow;
use App\Modules\Fondasi\Livewire\Home;
use App\Modules\Fondasi\Livewire\TeamList;
use App\Modules\Integrasi\Events\EmployeesSynced;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

/** M01 Fondasi: auth, peran, audit, verifikasi dokumen. */
class FondasiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponse::class, LoginResponse::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'fondasi');
        Route::middleware('web')->group(__DIR__.'/routes.php');

        Livewire::component('fondasi.home', Home::class);
        Livewire::component('fondasi.employee-index', EmployeeIndex::class);
        Livewire::component('fondasi.employee-show', EmployeeShow::class);
        Livewire::component('fondasi.team-list', TeamList::class);

        Event::listen(EmployeesSynced::class, RefreshAccountsAfterSync::class);
        Event::listen(Login::class, RecordSuccessfulLogin::class);

        $this->bootFortify();
    }

    private function bootFortify(): void
    {
        Fortify::loginView(fn () => view('fondasi::auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('fondasi::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('fondasi::auth.confirm-password'));

        Fortify::authenticateUsing(fn (Request $request) => app(AuthenticateUser::class)(
            (string) $request->input('username'),
            (string) $request->input('password'),
        ));

        // docs/21: 5×/menit per NIP+IP [ASUMSI].
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(
            mb_strtolower((string) $request->input(Fortify::username())).'|'.$request->ip()
        ));
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by((string) $request->session()->get('login.id')));
    }
}
