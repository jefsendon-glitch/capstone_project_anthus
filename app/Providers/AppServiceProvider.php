<?php

namespace App\Providers;

use App\Models\Consumable;
use App\Models\Product;
use App\Observers\ConsumableObserver;
use App\Observers\ProductObserver;
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
        Product::observe(ProductObserver::class);
        Consumable::observe(ConsumableObserver::class);
    }
}
