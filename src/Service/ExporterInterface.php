<?php

namespace Service;

use DTO\DtoCollectionInterface;

interface ExporterInterface
{
    public function export(DtoCollectionInterface $collection): void;
}
