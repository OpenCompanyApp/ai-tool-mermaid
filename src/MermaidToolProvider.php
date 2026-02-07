<?php

namespace OpenCompany\AiToolMermaid;

use Laravel\Ai\Contracts\Tool;
use OpenCompany\AiToolMermaid\Tools\RenderMermaid;
use OpenCompany\IntegrationCore\Contracts\ToolProvider;

class MermaidToolProvider implements ToolProvider
{
    public function appName(): string
    {
        return 'mermaid';
    }

    public function appMeta(): array
    {
        return [
            'label' => 'diagrams, flowcharts, sequences',
            'description' => 'Mermaid diagram rendering',
            'icon' => 'ph:graph',
            'logo' => 'ph:graph',
        ];
    }

    public function tools(): array
    {
        return [
            'render_mermaid' => [
                'class' => RenderMermaid::class,
                'type' => 'write',
                'name' => 'Render Mermaid',
                'description' => 'Render Mermaid diagram syntax (flowcharts, sequence, ER, class, state, Gantt, and more) to a PNG image.',
                'icon' => 'ph:graph',
            ],
        ];
    }

    public function isIntegration(): bool
    {
        return true;
    }

    public function createTool(string $class, array $context = []): Tool
    {
        return new $class(app(MermaidService::class));
    }
}
