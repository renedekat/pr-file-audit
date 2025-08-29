<?php

namespace DTO;

use Service\ExporterInterface;

interface DtoCollectionInterface
{
    public function toArrayForJson(): array;

    public function toArrayForCsv(): array;

    public function exportWith(ExporterInterface $exporter): void;
}
