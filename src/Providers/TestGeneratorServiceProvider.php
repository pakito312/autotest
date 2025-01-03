<?php

namespace Paki\AutoTest\Providers;

use Illuminate\Support\ServiceProvider;
use Paki\AutoTest\Commands\GenerateTests;

class TestGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            GenerateTests::class,
        ]);
    }

    public function boot()
    {
        // Actions de démarrage, si nécessaires
    }
}
