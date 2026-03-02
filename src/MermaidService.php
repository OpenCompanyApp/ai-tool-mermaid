<?php

namespace OpenCompany\AiToolMermaid;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MermaidService
{
    private const DEFAULT_WIDTH = 2000;
    private const MIN_DIMENSION = 100;
    private const MAX_DIMENSION = 5000;

    /**
     * Render Mermaid syntax to raw PNG bytes.
     */
    public function renderToBytes(string $syntax, int $width = self::DEFAULT_WIDTH, string $theme = 'default'): string
    {
        $width = max(self::MIN_DIMENSION, min(self::MAX_DIMENSION, $width));

        $uuid = Str::uuid()->toString();
        $tmpInput = tempnam(sys_get_temp_dir(), 'mmd_') . '.mmd';
        $tmpOutput = sys_get_temp_dir() . '/' . $uuid . '.png';

        file_put_contents($tmpInput, $syntax);

        try {
            $mmdc = $this->findMmdc();
            $scale = $this->resolveScale($syntax);

            $command = [
                $mmdc,
                '-i', $tmpInput,
                '-o', $tmpOutput,
                '-w', (string) $width,
                '-s', (string) $scale,
                '-t', $theme,
                '-b', 'transparent',
                '--quiet',
            ];

            $process = new Process($command);
            $process->setTimeout(30);
            $process->setEnv($this->buildEnv($process));
            $process->run();

            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput() ?: $process->getOutput();
                throw new \RuntimeException('Mermaid rendering failed: ' . trim($error));
            }

            if (! file_exists($tmpOutput) || filesize($tmpOutput) === 0) {
                throw new \RuntimeException('mmdc produced no output.');
            }

            return file_get_contents($tmpOutput);
        } finally {
            @unlink($tmpInput);
            @unlink($tmpOutput);
        }
    }

    /**
     * Render Mermaid syntax to a PNG image on public disk.
     *
     * @return string Public URL path to the generated PNG
     */
    public function render(string $syntax, int $width = self::DEFAULT_WIDTH, string $theme = 'default'): string
    {
        $bytes = $this->renderToBytes($syntax, $width, $theme);

        Storage::disk('public')->makeDirectory('mermaid');

        $relativePath = 'mermaid/' . Str::uuid()->toString() . '.png';
        Storage::disk('public')->put($relativePath, $bytes);

        return '/storage/' . $relativePath;
    }

    /**
     * Determine scale factor based on diagram complexity.
     * Small diagrams get higher resolution, large ones stay within Telegram's limits.
     */
    private function resolveScale(string $syntax): int
    {
        $lines = count(array_filter(explode("\n", $syntax), fn ($l) => trim($l) !== ''));

        return match (true) {
            $lines <= 10 => 5,
            $lines <= 25 => 4,
            $lines <= 50 => 3,
            default => 2,
        };
    }

    private function buildEnv(Process $process): array
    {
        $env = $process->getEnv();
        $path = getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin';
        foreach (['/opt/homebrew/bin', '/usr/local/bin', dirname(PHP_BINARY)] as $dir) {
            if (is_dir($dir) && !str_contains($path, $dir)) {
                $path = $dir . ':' . $path;
            }
        }
        $env['PATH'] = $path;

        return $env;
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
