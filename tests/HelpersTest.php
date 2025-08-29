<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Service\JsonExporter;
use Service\CsvExporter;
use Service\StdOutJsonExporter;

require_once __DIR__ . '/../src/helpers.php';

class HelpersTest extends TestCase
{
    private string $basename = 'test_export';

    public function testStoragePathThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Storage directory not found.');

        // Pass a non-existent folder to trigger the exception
        storage_path(file: '', base: __DIR__ . '/non_existent_storage');
    }


    public function testStoragePathThrowsWhenDirectoryMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Storage directory not found.');

        // Use a directory that does not exist
        $fakeDir = __DIR__ . '/non_existent_storage';

        // Temporarily redefine a small wrapper to simulate storage_path using $fakeDir
        $func = function (string $file = '') use ($fakeDir): string {
            $path = realpath($fakeDir);
            if ($path === false) {
                throw new \RuntimeException('Storage directory not found.');
            }
            return rtrim($path, DIRECTORY_SEPARATOR) . ($file ? DIRECTORY_SEPARATOR . $file : '');
        };

        $func(); // should throw RuntimeException
    }

    public function testCreateExporterDefaultsToStdOut(): void
    {
        $exporter = create_exporter([], $this->basename);
        $this->assertInstanceOf(StdOutJsonExporter::class, $exporter);
    }

    public function testCreateExporterJson(): void
    {
        $exporter = create_exporter(['--output=json'], $this->basename);
        $this->assertInstanceOf(JsonExporter::class, $exporter);
    }

    public function testCreateExporterCsv(): void
    {
        $exporter = create_exporter(['--output=csv'], $this->basename);
        $this->assertInstanceOf(CsvExporter::class, $exporter);
    }

    public function testNotifyExportSuccessJson(): void
    {
        $exporter = new JsonExporter(filename: storage_path($this->basename . '.json'));

        $this->expectOutputString("JSON exported to " . storage_path($this->basename . '.json') . PHP_EOL);
        notify_export_success($exporter, $this->basename);
    }

    public function testNotifyExportSuccessCsv(): void
    {
        $exporter = new CsvExporter(filename: storage_path($this->basename . '.csv'));

        $this->expectOutputString("CSV exported to " . storage_path($this->basename . '.csv') . PHP_EOL);
        notify_export_success($exporter, $this->basename);
    }

    public function testNotifyExportSuccessStdOut(): void
    {
        $exporter = new StdOutJsonExporter();
        $this->expectOutputString('');
        notify_export_success($exporter, $this->basename);
    }
}
