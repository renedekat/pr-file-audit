<?php

namespace DTO;

class FileAuditCollection implements \ArrayAccess, \IteratorAggregate
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

    public function toArray(): array
    {
        return $this->items;
    }

    // Implement ArrayAccess
    public function offsetExists($offset): bool { return isset($this->items[$offset]); }
    public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
    public function offsetSet($offset, $value): void { $this->items[$offset] = $value; }
    public function offsetUnset($offset): void { unset($this->items[$offset]); }

    // Implement IteratorAggregate
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
