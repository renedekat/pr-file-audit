<?php

namespace Tests\DTO;

use PHPUnit\Framework\TestCase;
use DTO\JiraPRCollection;
use DTO\JiraPRDto;
use Service\JsonExporter;

class JiraPRCollectionTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/jira_pr_test.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testAddAndPreventDuplicates(): void
    {
        $collection = new JiraPRCollection();

        $dto1 = new JiraPRDto('repo1', 101, 'MERGED', 'https://github.com/repo1/pull/101');
        $dto2 = new JiraPRDto('repo1', 101, 'MERGED', 'https://github.com/repo1/pull/101'); // duplicate
        $dto3 = new JiraPRDto('repo1', 102, 'OPEN', 'https://github.com/repo1/pull/102');

        $collection->add($dto1);
        $collection->add($dto2); // should be ignored
        $collection->add($dto3);

        $this->assertCount(2, $collection);

        $items = iterator_to_array($collection);
        $this->assertSame(101, $items[0]->prNumber);
        $this->assertSame(102, $items[1]->prNumber);
    }

    public function testToArrayForJson(): void
    {
        $collection = new JiraPRCollection();
        $collection->add(new JiraPRDto('repo1', 101, 'MERGED', 'url1'));
        $collection->add(new JiraPRDto('repo1', 103, 'OPEN', 'url2'));
        $collection->add(new JiraPRDto('repo1', 102, 'DECLINED', 'url3'));
        $collection->add(new JiraPRDto('repo2', 201, 'MERGED', 'url4'));

        $expected = [
            'repo1' => [101, 102, 103], // sorted
            'repo2' => [201],
        ];

        $this->assertSame($expected, $collection->toArrayForJson());
    }

    public function testToArrayForCsv(): void
    {
        $collection = new JiraPRCollection();
        $collection->add(new JiraPRDto('repo1', 101, 'MERGED', 'url1'));
        $collection->add(new JiraPRDto('repo2', 202, 'OPEN', 'url2'));

        $expected = [
            ['Repository', 'PR Number'],
            ['repo1', 101],
            ['repo2', 202],
        ];

        $this->assertSame($expected, $collection->toArrayForCsv());
    }

    public function testExportWithJsonExporter(): void
    {
        $collection = new JiraPRCollection();
        $collection->add(new JiraPRDto('repo1', 101, 'MERGED', 'url1'));
        $collection->add(new JiraPRDto('repo1', 102, 'OPEN', 'url2'));
        $collection->add(new JiraPRDto('repo2', 201, 'DECLINED', 'url3'));

        $exporter = new JsonExporter($this->tempFile);
        $collection->exportWith($exporter);

        $this->assertFileExists($this->tempFile);

        $contents = json_decode(file_get_contents($this->tempFile), true);

        $this->assertSame([101, 102], $contents['repo1']);
        $this->assertSame([201], $contents['repo2']);
    }
}
