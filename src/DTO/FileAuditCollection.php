<?php

namespace DTO;

use ArrayIterator;
use IteratorAggregate;
use Service\ExporterInterface;

class FileAuditCollection implements IteratorAggregate, DtoCollectionInterface
{
    /** @var FileAuditDto[] */
    private array $items = [];

    public function add(FileAuditDto $dto): void
    {
        foreach ($this->items as $existing) {
            if ($existing->repo === $dto->repo && $existing->filename === $dto->filename) {
                $existing->contributors = array_unique(array_merge($existing->contributors, $dto->contributors));
                return;
            }
        }

        $this->items[] = $dto;
    }

    /**
     * Build grouped array for JSON export
     */
    public function toArrayForJson(): array
    {
        $result = [];
        foreach ($this->items as $dto) {
            $result[$dto->repo][] = $dto->filename;
        }

        return $result;
    }

    /**
     * Build CSV-ready array
     */
    public function toArrayForCsv(): array
    {
        $rows = [];

        // Determine max contributors
        $maxContributors = 0;
        foreach ($this->items as $dto) {
            $maxContributors = max($maxContributors, count($dto->contributors));
        }

        foreach ($this->items as $dto) {
            $row = array_merge([$dto->repo, $dto->filename], $dto->contributors);
            $row = array_pad($row, 2 + $maxContributors, '');
            $rows[] = $row;
        }

        // Add header as first row
        $header = array_merge(['Repo Name', 'File Name'], array_map(fn($i) => "Contributor $i", range(1, $maxContributors)));
        array_unshift($rows, $header);

        return $rows;
    }

    public function exportWith(ExporterInterface $exporter): void
    {
        $exporter->export($this);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
