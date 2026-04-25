<?php

declare(strict_types=1);

namespace Klipy\Laravel;

use Illuminate\Support\ServiceProvider;
use Klipy\Klipy;

class KlipyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/klipy.php', 'klipy');

        $this->app->singleton(Klipy::class, function ($app) {
            $config = $app['config']->get('klipy');

            return new Klipy(
                apiKey: $config['api_key'] ?? '',
                options: array_filter([
                    'base_url' => $config['base_url'] ?? null,
                    'timeout' => $config['timeout'] ?? null,
                    'default_locale' => $config['default_locale'] ?? null,
                ], fn ($v) => $v !== null),
            );
        });

        $this->app->alias(Klipy::class, 'klipy');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/klipy.php' => $this->app->configPath('klipy.php'),
            ], 'klipy-config');
        }
    }

    public function provides(): array
    {
        return [Klipy::class, 'klipy'];
    }
}
