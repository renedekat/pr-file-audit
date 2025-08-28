<?php

namespace Service;

class CsvExporter
{
    private string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function export(array $auditData): void
    {
        $handle = fopen($this->filename, 'w');

        // Determine max contributors for header
        $maxContributors = 0;
        foreach ($auditData as $dto) {
            $maxContributors = max($maxContributors, count($dto->contributors));
        }

        // Header
        $header = array_merge(['Repo Name', 'File Name'], array_map(fn($i) => "Contributor $i", range(1, $maxContributors)));
        fputcsv($handle, $header, ',', '"', '\\');

        // Rows
        foreach ($auditData as $dto) {
            $row = array_merge([$dto->repo, $dto->filename], $dto->contributors);
            fputcsv($handle, $row, ',', '"', '\\');
        }

        fclose($handle);
    }
}
