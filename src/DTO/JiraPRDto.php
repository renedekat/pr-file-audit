<?php

namespace DTO;

class JiraPRDto
{
    public function __construct(
        public string $repositoryName,
        public int $prNumber,
        public string $status,         // e.g. "MERGED", "OPEN", "DECLINED"
        public string $url             // GitHub PR URL
    ) {}
}
