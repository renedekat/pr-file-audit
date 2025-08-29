<?php

namespace Tests\Exporter;

use PHPUnit\Framework\TestCase;
use Service\CsvExporter;
use Tests\DTO\TestCollection;

class CsvExporterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/test_repos.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testExportsCsvCorrectly(): void
    {
        $collection = new TestCollection([
            'repo1' => [101],
            'repo2' => [202, 203],
        ]);

        $exporter = new CsvExporter($this->tempFile);
        $collection->exportWith($exporter);

        $this->assertFileExists($this->tempFile);

        $lines = array_map(fn($line) => str_getcsv($line, ',', '"', '\\'), file($this->tempFile));


        $this->assertContains(['repo1', '101'], $lines);
        $this->assertContains(['repo2', '202'], $lines);
        $this->assertContains(['repo2', '203'], $lines);
    }
}
