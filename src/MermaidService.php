<?php

namespace OpenCompany\AiToolMermaid;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MermaidService
{
    private const DEFAULT_WIDTH = 1400;
    private const MIN_DIMENSION = 100;
    private const MAX_DIMENSION = 4000;

    /**
     * Render Mermaid syntax to a PNG image.
     *
     * @return string Public URL path to the generated PNG
     */
    public function render(string $syntax, int $width = self::DEFAULT_WIDTH, string $theme = 'default'): string
    {
        $width = max(self::MIN_DIMENSION, min(self::MAX_DIMENSION, $width));

        Storage::disk('public')->makeDirectory('mermaid');

        $uuid = Str::uuid()->toString();
        $relativePath = 'mermaid/' . $uuid . '.png';
        $outputPath = Storage::disk('public')->path($relativePath);

        // Write Mermaid syntax to temp file
        $tmpInput = tempnam(sys_get_temp_dir(), 'mmd_') . '.mmd';
        file_put_contents($tmpInput, $syntax);

        try {
            $mmdc = $this->findMmdc();

            $command = [
                $mmdc,
                '-i', $tmpInput,
                '-o', $outputPath,
                '-w', (string) $width,
                '-t', $theme,
                '-b', 'transparent',
                '--quiet',
            ];

            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput() ?: $process->getOutput();

                throw new \RuntimeException('Mermaid rendering failed: ' . trim($error));
            }

            if (! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new \RuntimeException('mmdc produced no output.');
            }
        } finally {
            @unlink($tmpInput);
        }

        return '/storage/' . $relativePath;
    }

    /**
     * Find the mmdc binary in common locations.
     */
    private function findMmdc(): string
    {
        $candidates = [
            base_path('node_modules/.bin/mmdc'),
            '/usr/local/bin/mmdc',
            '/usr/bin/mmdc',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return 'mmdc';
    }
}
