<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use App\Policies\EmployeePolicy;
use App\Policies\TaskPolicy;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use App\Repositories\Contracts\KpiRecordRepositoryInterface;
use App\Repositories\Contracts\KpiScoreRepositoryInterface;
use App\Repositories\Contracts\KpiTargetRepositoryInterface;
use App\Repositories\Eloquent\EloquentKpiIndicatorRepository;
use App\Repositories\Eloquent\EloquentKpiRecordRepository;
use App\Repositories\Eloquent\EloquentKpiScoreRepository;
use App\Repositories\Eloquent\EloquentKpiTargetRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(KpiIndicatorRepositoryInterface::class, EloquentKpiIndicatorRepository::class);
        $this->app->bind(KpiTargetRepositoryInterface::class, EloquentKpiTargetRepository::class);
        $this->app->bind(KpiRecordRepositoryInterface::class, EloquentKpiRecordRepository::class);
        $this->app->bind(KpiScoreRepositoryInterface::class, EloquentKpiScoreRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, EmployeePolicy::class);

        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
