<?php

namespace Tests\DTO;

use DTO\DtoCollectionInterface;
use Service\ExporterInterface;

class TestCollection implements DtoCollectionInterface
{
    private array $groupedData;

    public function __construct(array $groupedData)
    {
        // groupedData should be in the same format as toGroupedArray returns
        $this->groupedData = $groupedData;
    }

    public function toArrayForJson(): array
    {
        return $this->groupedData;
    }

    public function toArrayForCsv(): array
    {
        $rows = [];
        foreach ($this->groupedData as $repo => $prs) {
            foreach ($prs as $prNumber) {
                $rows[] = [$repo, $prNumber];
            }
        }
        return $rows;
    }

    public function exportWith(ExporterInterface $exporter): void
    {
        $exporter->export($this);
    }
}
