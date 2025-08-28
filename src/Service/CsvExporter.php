<?php

namespace Service;

use DTO\FileAuditCollection;

class CsvExporter
{
    private string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function export(FileAuditCollection $collection): void
    {
        $handle = fopen($this->filename, 'w');

        $items = $collection->toArray();

        // Determine max contributors for header
        $maxContributors = 0;
        foreach ($items as $dto) {
            $maxContributors = max($maxContributors, count($dto->contributors));
        }

        // Header
        $header = array_merge(['Repo Name', 'File Name'], array_map(fn($i) => "Contributor $i", range(1, $maxContributors)));
        fputcsv($handle, $header, ',', '"', '\\');

        // Rows
        foreach ($items as $dto) {
            $row = array_merge([$dto->repo, $dto->filename], $dto->contributors);

            // Fill empty columns if some files have fewer contributors
            $row = array_pad($row, 2 + $maxContributors, '');
            fputcsv($handle, $row, ',', '"', '\\');
        }

        fclose($handle);
    }

}
