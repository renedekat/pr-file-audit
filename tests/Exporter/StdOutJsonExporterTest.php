<?php

namespace Tests\Exporter;

use PHPUnit\Framework\TestCase;
use Service\StdOutJsonExporter;
use Tests\DTO\TestCollection;

class StdOutJsonExporterTest extends TestCase
{
    public function testExportsJsonToStdOut(): void
    {
        $data = [
            'org/repo1' => [12, 34],
            'org/repo2' => [7, 8, 9],
        ];

        $collection = new TestCollection($data);

        $exporter = new StdOutJsonExporter();

        // Capture the exact JSON that should be output
        $expectedJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        $this->expectOutputString($expectedJson);

        $collection->exportWith($exporter);
    }
}
