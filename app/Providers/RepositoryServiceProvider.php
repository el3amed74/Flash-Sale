<?php

namespace App\Providers;


use App\Services\HoldService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Repositories\HoldRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\PaymentWebhookService;
use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\HoldServiceInterface;
use App\Repositories\PaymentWebhookLogRepository;
use App\Services\Contracts\OrderServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Repositories\Contracts\HoldRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\PaymentWebhookServiceInterface;
use App\Repositories\Contracts\PaymentWebhookLogRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(HoldRepositoryInterface::class, HoldRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(PaymentWebhookLogRepositoryInterface::class, PaymentWebhookLogRepository::class);

        // Service bindings
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(HoldServiceInterface::class, HoldService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(PaymentWebhookServiceInterface::class, PaymentWebhookService::class);
    }

    public function boot(): void
    {
        //
    }
}

