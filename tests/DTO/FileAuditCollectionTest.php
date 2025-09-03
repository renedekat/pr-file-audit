<?php

namespace Tests\DTO;

use PHPUnit\Framework\TestCase;
use DTO\FileAuditCollection;
use DTO\FileAuditDto;
use Service\JsonExporter;

class FileAuditCollectionTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/file_audit_test.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testAddAndMergeContributors(): void
    {
        $collection = new FileAuditCollection();

        $dto1 = new FileAuditDto('repo1', 'file1.php', ['Alice']);
        $dto2 = new FileAuditDto('repo1', 'file1.php', ['Bob']);
        $dto3 = new FileAuditDto('repo1', 'file2.php', ['Charlie']);

        $collection->add($dto1);
        $collection->add($dto2); // should merge contributors into file1.php
        $collection->add($dto3);

        $this->assertCount(2, $collection); // file1.php + file2.php

        $items = iterator_to_array($collection);
        $this->assertSame(['Alice', 'Bob'], $items[0]->contributors);
        $this->assertSame(['Charlie'], $items[1]->contributors);
    }

    public function testAddMergesContributorsAndRemovesDuplicates(): void
    {
        $collection = new FileAuditCollection();

        $dto1 = new FileAuditDto('repo1', 'file1.php', ['Alice']);
        $dto2 = new FileAuditDto('repo1', 'file1.php', ['Alice']);

        $collection->add($dto1);
        $collection->add($dto2); // should merge contributors into file1.php and unique

        $items = iterator_to_array($collection);
        $this->assertSame(['Alice'], $items[0]->contributors);

    }

    public function testToArrayForJson(): void
    {
        $collection = new FileAuditCollection();
        $collection->add(new FileAuditDto('repo1', 'file1.php', ['Alice']));
        $collection->add(new FileAuditDto('repo1', 'file2.php', ['Bob']));
        $collection->add(new FileAuditDto('repo2', 'file3.php', ['Charlie']));

        $expected = [
            'repo1' => ['file1.php', 'file2.php'],
            'repo2' => ['file3.php'],
        ];

        $this->assertSame($expected, $collection->toArrayForJson());
    }

    public function testToArrayForCsv(): void
    {
        $collection = new FileAuditCollection();

        $collection->add(new FileAuditDto(
            repo: 'repo1',
            filename: 'file1.php',
            contributors: ['Alice', 'Bob']
        ));

        $collection->add(new FileAuditDto(
            repo: 'repo2',
            filename: 'file2.php',
            contributors: ['Charlie']
        ));

        $expected = [
            // Header row
            ['Repo Name', 'File Name', 'Contributor 1', 'Contributor 2'],
            // Data rows
            ['repo1', 'file1.php', 'Alice', 'Bob'],
            ['repo2', 'file2.php', 'Charlie', ''],
        ];

        $this->assertSame($expected, $collection->toArrayForCsv());
    }

    public function testExportWithJsonExporter(): void
    {
        $collection = new FileAuditCollection();
        $collection->add(new FileAuditDto('repo1', 'file1.php', ['Alice']));
        $collection->add(new FileAuditDto('repo1', 'file2.php', ['Bob']));
        $collection->add(new FileAuditDto('repo2', 'file3.php', ['Charlie']));

        $exporter = new JsonExporter($this->tempFile);
        $collection->exportWith($exporter);

        $this->assertFileExists($this->tempFile);

        $contents = json_decode(file_get_contents($this->tempFile), true);

        $this->assertSame(['file1.php', 'file2.php'], $contents['repo1']);
        $this->assertSame(['file3.php'], $contents['repo2']);
    }
}
