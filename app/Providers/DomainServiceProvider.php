<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Olulo\Domain\Restaurant\Repositories\RestaurantRepository;
use Olulo\Domain\Order\Repositories\OrderRepository;
use Olulo\Domain\User\Repositories\UserRepository;
use Olulo\Infrastructure\Persistence\Repositories\EloquentRestaurantRepository;
use Olulo\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use Olulo\Infrastructure\Persistence\Repositories\EloquentUserRepository;

/**
 * 도메인 서비스 프로바이더
 *
 * DDD 도메인 레이어의 의존성 주입을 관리
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * 서비스 등록
     */
    public function register(): void
    {
        // Repository 바인딩
        $this->registerRepositories();

        // Domain Services 바인딩
        $this->registerDomainServices();

        // Event Handlers 등록
        $this->registerEventHandlers();
    }

    /**
     * 서비스 부팅
     */
    public function boot(): void
    {
        // 도메인 이벤트 리스너 등록
        $this->registerDomainEventListeners();
    }

    /**
     * 리포지토리 인터페이스와 구현체 바인딩
     */
    private function registerRepositories(): void
    {
        // Restaurant 도메인
        $this->app->bind(
            RestaurantRepository::class,
            EloquentRestaurantRepository::class
        );

        // Order 도메인
        $this->app->bind(
            OrderRepository::class,
            EloquentOrderRepository::class
        );

        // User 도메인
        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class
        );
    }

    /**
     * 도메인 서비스 등록
     */
    private function registerDomainServices(): void
    {
        // 도메인 서비스들을 싱글톤으로 등록
        $this->app->singleton(
            \Olulo\Domain\Restaurant\Services\RestaurantService::class
        );

        $this->app->singleton(
            \Olulo\Domain\Order\Services\OrderService::class
        );

        $this->app->singleton(
            \Olulo\Domain\Payment\Services\PaymentService::class
        );
    }

    /**
     * 이벤트 핸들러 등록
     */
    private function registerEventHandlers(): void
    {
        // CQRS Command/Query 핸들러 등록
        $this->app->tag([
            \Olulo\Application\Restaurant\Handlers\CreateRestaurantHandler::class,
            \Olulo\Application\Order\Handlers\PlaceOrderHandler::class,
            \Olulo\Application\Payment\Handlers\ProcessPaymentHandler::class,
        ], 'command-handlers');
    }

    /**
     * 도메인 이벤트 리스너 등록
     */
    private function registerDomainEventListeners(): void
    {
        // 이벤트 디스패처에 도메인 이벤트 리스너 등록
        $events = $this->app['events'];

        // Restaurant Events
        $events->listen(
            \Olulo\Domain\Restaurant\Events\RestaurantCreated::class,
            \Olulo\Application\Restaurant\Listeners\RestaurantCreatedListener::class
        );

        // Order Events
        $events->listen(
            \Olulo\Domain\Order\Events\OrderPlaced::class,
            \Olulo\Application\Order\Listeners\OrderPlacedListener::class
        );

        $events->listen(
            \Olulo\Domain\Order\Events\OrderStatusChanged::class,
            \Olulo\Application\Order\Listeners\OrderStatusChangedListener::class
        );
    }
}