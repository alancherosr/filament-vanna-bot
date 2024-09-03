<?php

namespace Alancherosr\FilamentVannaBot;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Alancherosr\FilamentVannaBot\Components\VannaBot;
use Livewire\Livewire;

class FilamentVannaBotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-vanna-bot')
            ->hasConfigFile()
            ->hasViews();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootLoaders();
        $this->bootPublishing();

        Livewire::component('filament-vanna-bot', VannaBot::class);

        if(config('filament-vanna-bot.enable')){
            FilamentView::registerRenderHook(
                'panels::body.end',
                fn (): string => auth()->check() ? Blade::render('@livewire(\'filament-vanna-bot\')'):'',
            );
        }

    }

    protected function bootLoaders()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-vanna-bot');
    }

    protected function bootPublishing()
    {
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/filament-vanna-bot'),
        ], 'filament-vanna-bot-views');

        $this->publishes([
            __DIR__.'/../config/filament-vanna-bot.php' => config_path('filament-vanna-bot.php'),
        ], 'filament-vanna-bot-config');
    }

}
