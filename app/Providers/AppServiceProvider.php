<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use App\Policies\ActivityPolicy;
use App\Events\ContractSubmitted;
use App\Events\ContractApproved;
use App\Events\ContractRejected;
use App\Listeners\SendContractApprovalRequestNotification;
use App\Listeners\SendContractApprovedNotification;
use App\Listeners\SendContractRejectedNotification;
use App\Models\Employee;
use App\Models\Contract;
use App\Observers\EmployeeObserver;
use App\Observers\ContractObserver;

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
        // Auth data is now shared via HandleInertiaRequests middleware
        // No need to share here anymore

        // Register policy for Spatie Activity model (cannot auto-discover external package models)
        Gate::policy(Activity::class, ActivityPolicy::class);

        // Register observers
        Employee::observe(EmployeeObserver::class);
        Contract::observe(ContractObserver::class);

        // Event listeners use #[ListensTo] attributes and are auto-discovered
        // No need to manually register with Event::listen()
    }
}

