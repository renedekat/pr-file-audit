<?php

namespace Service;

use DTO\DtoCollectionInterface;

class CsvExporter implements ExporterInterface
{
    private string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function export(DtoCollectionInterface $collection): void
    {
        $handle = fopen($this->filename, 'w');

        foreach ($collection->toArrayForCsv() as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }

        fclose($handle);
    }
}
