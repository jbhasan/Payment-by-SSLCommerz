<?php

namespace Sayeed\PaymentBySslcommerz\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentBySslcommerzServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
		$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
		$this->mergeConfigFrom(
			__DIR__.'/../config/sslcommerz.php', 'sslcommerz'
		);
    }
}
