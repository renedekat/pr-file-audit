<?php

namespace Service;

use GuzzleHttp\Client;
use DTO\FileAuditDto;

class GithubPRAudit
{
    private string $token;
    private Client $client;
    private array $repos;

    public function __construct(string $token, array $repos)
    {
        $this->token = $token;
        $this->repos = $repos;
        $this->client = new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => "token {$this->token}",
                'Accept' => 'application/vnd.github+json',
            ],
        ]);
    }

    public function fetchAuditData(): array
    {
        $auditData = [];

        foreach ($this->repos as $repoFullName => $prNumbers) {
            foreach ($prNumbers as $prNumber) {
                $this->processPullRequest($repoFullName, $prNumber, $auditData);
            }
        }

        return $auditData;
    }

    private function processPullRequest(string $repoFullName, int $prNumber, array &$auditData): void
    {
        if (!$this->isMerged($repoFullName, $prNumber)) {
            return; // Skip unmerged PRs
        }

        $files = $this->getPullRequestFiles($repoFullName, $prNumber);
        $contributors = $this->getPullRequestContributors($repoFullName, $prNumber);

        foreach ($files as $filename) {
            $this->addOrMergeAuditData($repoFullName, $filename, $contributors, $auditData);
        }
    }

    private function isMerged(string $repo, int $pr): bool
    {
        $response = $this->client->get("repos/{$repo}/pulls/{$pr}");
        $prData = json_decode($response->getBody()->getContents(), true);
        return !empty($prData['merged']);
    }

    private function addOrMergeAuditData(string $repoFullName, string $filename, array $contributors, array &$auditData): void
    {
        foreach ($auditData as $dto) {
            if ($dto->repo === $repoFullName && $dto->filename === $filename) {
                $dto->contributors = array_unique(array_merge($dto->contributors, $contributors));
                return;
            }
        }

        // No existing DTO found, create a new one
        $auditData[] = new FileAuditDto($repoFullName, $filename, $contributors);
    }


    private function getPullRequestFiles(string $repo, int $pr): array
    {
        $response = $this->client->get("repos/{$repo}/pulls/{$pr}/files?per_page=100");
        $files = json_decode($response->getBody()->getContents(), true);
        return array_column($files, 'filename');
    }

    private function getPullRequestContributors(string $repo, int $pr): array
    {
        $response = $this->client->get("repos/{$repo}/pulls/{$pr}/commits?per_page=100");
        $commits = json_decode($response->getBody()->getContents(), true);

        $contributors = [];
        foreach ($commits as $commit) {
            if (isset($commit['author']['login'])) {
                $contributors[$commit['author']['login']] = true;
            }
        }

        return array_keys($contributors);
    }
}