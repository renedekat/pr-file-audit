<?php

use Service\CsvExporter;
use Service\ExporterInterface;
use Service\JsonExporter;
use Service\StdOutJsonExporter;

if (!function_exists(function: 'storage_path')) {
    function storage_path(string $file = '', ?string $base = null): string
    {
        $base = $base ?? __DIR__ . '/../storage';
        $path = realpath($base);

        if ($path === false) {
            throw new RuntimeException('Storage directory not found.');
        }

        return rtrim($path, DIRECTORY_SEPARATOR) . ($file ? DIRECTORY_SEPARATOR . $file : '');
    }
}

if (!function_exists(function: 'create_exporter')) {
    function create_exporter(array $argv, string $basename, string $default = 'stdout'): ExporterInterface
    {
        $outputFileBase = storage_path($basename);

        $format = $default;

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--output=')) {
                $format = substr($arg, 9); // everything after "--output="
                break;
            }
        }

        return match ($format) {
            'json'   => new JsonExporter(filename: $outputFileBase . '.json'),
            'csv'    => new CsvExporter(filename: $outputFileBase . '.csv'),
            default  => new StdOutJsonExporter(),
        };
    }

}

if (!function_exists(function: 'notify_export_success')) {
    function notify_export_success(ExporterInterface $exporter, string $basename): void
    {
        if ($exporter instanceof JsonExporter) {
            echo "JSON exported to " . storage_path($basename . '.json') . PHP_EOL;
        } elseif ($exporter instanceof CsvExporter) {
            echo "CSV exported to " . storage_path($basename . '.csv') . PHP_EOL;
        }
    }
}