<?php

namespace Service;

use DTO\DtoCollectionInterface;

class StdOutJsonExporter implements ExporterInterface
{
    public function export(DtoCollectionInterface $collection): void
    {
        $data = $collection->toArrayForJson();

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
