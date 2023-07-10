<?php

namespace Visualbuilder\AiTranslate;

use Illuminate\Support\ServiceProvider;
use Visualbuilder\AiTranslate\Console\InstallCommand;
use Visualbuilder\AiTranslate\Console\TranslateStrings;

class AiTranslateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                                 __DIR__.'/../config/ai-translate.php' => config_path('ai-translate.php'),
                             ], 'config');
        }
    }
    
    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/ai-translate.php', 'ai-translate');
        
        $this->registerConsoleCommands();
    }
    
    private function registerConsoleCommands() {
        $this->commands([
                            InstallCommand::class,
                            TranslateStrings::class,
                        ]);
    }
}
