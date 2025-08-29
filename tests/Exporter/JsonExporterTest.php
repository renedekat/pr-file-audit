<?php

namespace Tests\Exporter;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Service\JsonExporter;
use Tests\DTO\TestCollection;

class JsonExporterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/test_repos.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testExportsJsonCorrectly(): void
    {
        $collection = new TestCollection([
            'repo1' => [101],
            'repo2' => [202, 203],
        ]);

        $exporter = new JsonExporter($this->tempFile);
        $collection->exportWith($exporter);

        $this->assertFileExists($this->tempFile);

        $contents = json_decode(file_get_contents($this->tempFile), true);

        $this->assertSame([101], $contents['repo1']);
        $this->assertSame([202, 203], $contents['repo2']);
    }

    public function testExportThrowsRuntimeExceptionWhenFileCannotBeWritten(): void
    {
        $data = [
            'org/repo1' => [12, 34],
        ];

        $collection = new TestCollection($data);

        // Use an invalid filename to force file_put_contents to fail
        $invalidFile = '/this/path/does/not/exist/repos_prs.json';

        $exporter = new JsonExporter($invalidFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot write JSON to $invalidFile: directory does not exist or is not writable");

        $collection->exportWith($exporter);
    }
}
