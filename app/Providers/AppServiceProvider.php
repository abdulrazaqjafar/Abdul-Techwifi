<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Sale;
use App\Models\Voucher;
use App\Policies\AdminPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PackagePolicy;
use App\Policies\SalePolicy;
use App\Policies\VoucherPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Admin::class, AdminPolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Voucher::class, VoucherPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
