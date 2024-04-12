<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\User\UserInterface;
use App\Repository\User\UserRepository;
use App\Repository\Subscripation\SubscriptionInterface;
use App\Repository\Subscripation\SubscriptionRepository;
use App\Repository\Store\StoreInterface;
use App\Repository\Store\StoreRepository;
use App\Repository\Order\OrderInterface;
use App\Repository\Order\OrderRepository;
use App\Repository\Category\CategoryInterface;
use App\Repository\Category\CategoryRepository;
use App\Repository\Coupon\CouponInterface;
use App\Repository\Coupon\CouponRepository;
use App\Repository\Home\HomeInterface;
use App\Repository\Home\HomeRepository;
use App\Repository\StoreOrder\StoreOrderInterface;
use App\Repository\StoreOrder\StoreOrderRepository;
use App\Repository\Product\ProductInterface;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\StockInterface;
use App\Repository\Stock\StockRepository;
use App\Repository\Wallet\WalletInterface;
use App\Repository\Wallet\WalletRepository;
class RepositoryServiceProvider  extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(StoreInterface::class, StoreRepository::class);
        $this->app->bind(SubscriptionInterface::class, SubscriptionRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        $this->app->bind(OrderInterface::class, OrderRepository::class);
        $this->app->bind(CouponInterface::class, CouponRepository::class);
        $this->app->bind(HomeInterface::class, HomeRepository::class);
        $this->app->bind(StoreOrderInterface::class, StoreOrderRepository::class);
        $this->app->bind(ProductInterface::class, ProductRepository::class);
        $this->app->bind(StockInterface::class, StockRepository::class);
        $this->app->bind(WalletInterface::class, WalletRepository::class);

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
