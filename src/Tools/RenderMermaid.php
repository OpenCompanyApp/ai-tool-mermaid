<?php

namespace OpenCompany\AiToolMermaid\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolMermaid\MermaidService;

class RenderMermaid implements Tool
{
    public function __construct(
        private MermaidService $mermaidService,
    ) {}

    public function description(): string
    {
        return <<<'DESC'
Render a Mermaid diagram to a PNG image. Pass valid Mermaid syntax and get back a markdown image embed.

Supported diagram types: flowchart, sequence, class, state, ER, Gantt, pie, quadrant, requirement, git graph, C4, mindmap, timeline, sankey, XY chart, block.

Example syntax:
```
graph TD
    A[Start] --> B{Decision}
    B -->|Yes| C[Action]
    B -->|No| D[End]
```

Tips:
- Use `graph TD` for top-down flowcharts, `graph LR` for left-to-right
- Use `sequenceDiagram` for sequence diagrams
- Use `erDiagram` for entity-relationship diagrams
- Use `classDiagram` for class diagrams
- Use `stateDiagram-v2` for state diagrams
- Use `gantt` for Gantt charts
- Use `pie` for pie charts
- Use `gitgraph` for git graphs
- Use `mindmap` for mind maps
DESC;
    }

    public function handle(Request $request): string
    {
        $syntax = trim($request['syntax'] ?? '');
        if (empty($syntax)) {
            return 'Error: Mermaid syntax is required. Pass your diagram code in the "syntax" parameter.';
        }

        $title = $request['title'] ?? 'Diagram';
        $width = (int) ($request['width'] ?? 1400);
        $theme = $request['theme'] ?? 'default';

        $allowedThemes = ['default', 'dark', 'forest', 'neutral'];
        if (! in_array($theme, $allowedThemes, true)) {
            $theme = 'default';
        }

        try {
            $url = $this->mermaidService->render($syntax, $width, $theme);

            return "![{$title}]({$url})";
        } catch (\Throwable $e) {
            return 'Error rendering Mermaid diagram: ' . $e->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'syntax' => $schema
                ->string()
                ->description('Mermaid diagram syntax. Must be valid Mermaid markup (e.g., starting with graph TD, sequenceDiagram, erDiagram, etc.).')
                ->required(),
            'title' => $schema
                ->string()
                ->description('Diagram title used as alt text (default: "Diagram").'),
            'width' => $schema
                ->integer()
                ->description('Output width in pixels (default: 1400, range: 100–4000).'),
            'theme' => $schema
                ->string()
                ->description("Mermaid theme: 'default', 'dark', 'forest', or 'neutral' (default: 'default')."),
        ];
    }
}
