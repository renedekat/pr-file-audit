<?php

namespace DTO;

class FileAuditDto
{
    public string $repo;
    public string $filename;
    public array $contributors;

    public function __construct(string $repo, string $filename, array $contributors)
    {
        $this->repo = $repo;
        $this->filename = $filename;
        $this->contributors = $contributors;
    }
}
