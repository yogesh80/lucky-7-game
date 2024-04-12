<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MyFatoorahGateway;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('myFatoorah', function ($app) {
            return new MyFatoorahGateway();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
