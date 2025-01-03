<?php

namespace Paki\TestGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Paki\TestGenerator\Commands\GenerateTests;

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
