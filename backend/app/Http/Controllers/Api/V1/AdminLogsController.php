<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogsController
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    public function files(): JsonResponse
    {
        $files = collect(glob($this->logPath . '/laravel_*.log'))
            ->map(fn($path) => basename($path))
            ->sortDesc()
            ->values();

        return response()->json(['files' => $files]);
    }

    public function content(Request $request): JsonResponse
    {
        $filename = basename((string) $request->input('file', ''));

        if (!$filename || !str_ends_with($filename, '.log')) {
            return response()->json(['message' => 'Invalid filename.'], 400);
        }

        $path = $this->logPath . '/' . $filename;

        if (!file_exists($path) || !str_starts_with(realpath($path), realpath($this->logPath))) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $lines = array_slice(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -1000);

        $parsed = array_map(fn($line) => $this->parseLine($line), $lines);

        return response()->json(['lines' => array_reverse($parsed)]);
    }

    private function parseLine(string $line): array
    {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)$/', $line, $m)) {
            return [
                'timestamp' => $m[1],
                'env'       => $m[2],
                'level'     => strtoupper($m[3]),
                'message'   => rtrim($m[4]),
                'raw'       => $line,
            ];
        }

        return [
            'timestamp' => null,
            'env'       => null,
            'level'     => null,
            'message'   => $line,
            'raw'       => $line,
        ];
    }
}
