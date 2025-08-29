<?php

namespace DTO;

use ArrayIterator;
use IteratorAggregate;
use Service\ExporterInterface;

class JiraPRCollection implements IteratorAggregate, DtoCollectionInterface
{
    /** @var JiraPRDto[] */
    private array $items = [];

    public function add(JiraPRDto $dto): void
    {
        foreach ($this->items as $existing) {
            if ($existing->repositoryName === $dto->repositoryName && $existing->prNumber === $dto->prNumber) {
                return;
            }
        }

        $this->items[] = $dto;
    }

    public function toArrayForJson(): array
    {
        $result = [];
        foreach ($this->items as $pr) {
            $result[$pr->repositoryName][] = $pr->prNumber;
        }

        foreach ($result as &$ids) {
            sort($ids);
        }

        return $result;
    }

    public function toArrayForCsv(): array
    {
        $rows = [];

        $rows[] = ['Repository', 'PR Number'];

        foreach ($this->items as $pr) {
            $rows[] = [$pr->repositoryName, $pr->prNumber];
        }

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
