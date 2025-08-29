<?php

namespace Service;

use DTO\DtoCollectionInterface;
use RuntimeException;

class JsonExporter implements ExporterInterface
{
    private string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function export(DtoCollectionInterface $collection): void
    {
        $data = $collection->toArrayForJson();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $dir = dirname($this->filename);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new RuntimeException(message: "Cannot write JSON to {$this->filename}: directory does not exist or is not writable");
        }

        file_put_contents($this->filename, $json);
    }
}
