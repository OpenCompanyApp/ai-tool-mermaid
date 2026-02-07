<?php

namespace OpenCompany\AiToolMermaid;

use Illuminate\Support\ServiceProvider;
use OpenCompany\IntegrationCore\Support\ToolProviderRegistry;

class AiToolMermaidServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MermaidService::class);
    }

    public function boot(): void
    {
        if ($this->app->bound(ToolProviderRegistry::class)) {
            $this->app->make(ToolProviderRegistry::class)
                ->register(new MermaidToolProvider());
        }
    }
}
