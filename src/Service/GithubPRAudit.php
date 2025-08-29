<?php

namespace Service;

use DTO\FileAuditCollection;
use GuzzleHttp\Client;
use DTO\FileAuditDto;

class GithubPRAudit
{
    private Client $client;
    private array $repos;

    public function __construct(Client $client, array $repos)
    {
        $this->client = $client;
        $this->repos = $repos;
    }

    public function fetchAuditData(): FileAuditCollection
    {
        $collection = new FileAuditCollection();

        foreach ($this->repos as $repoFullName => $prNumbers) {
            foreach ($prNumbers as $prNumber) {
                $this->processPullRequest($repoFullName, $prNumber, $collection);
            }
        }

        return $collection;
    }

    private function processPullRequest(string $repoFullName, int $prNumber, FileAuditCollection $collection): void
    {
        if (!$this->isMerged(repo: $repoFullName, pr: $prNumber)) {
            return; // Skip unmerged PRs
        }

        $files = $this->getPullRequestFiles(repo: $repoFullName, pr: $prNumber);
        $contributors = $this->getPullRequestContributors(repo: $repoFullName, pr: $prNumber);

        foreach ($files as $filename) {
            $collection->add(dto: new FileAuditDto(
                repo: $repoFullName,
                filename: $filename,
                contributors: $contributors
            ));
        }
    }

    private function isMerged(string $repo, int $pr): bool
    {
        $response = $this->client->get(uri: "repos/{$repo}/pulls/{$pr}");
        $prData = json_decode(json: $response->getBody()->getContents(), associative: true);
        return !empty($prData['merged']);
    }

    private function getPullRequestFiles(string $repo, int $pr): array
    {
        $response = $this->client->get(uri: "repos/{$repo}/pulls/{$pr}/files?per_page=100");
        $files = json_decode(json: $response->getBody()->getContents(), associative: true);
        return array_column($files, 'filename');
    }

    private function getPullRequestContributors(string $repo, int $pr): array
    {
        $response = $this->client->get(uri: "repos/{$repo}/pulls/{$pr}/commits?per_page=100");
        $commits = json_decode(json: $response->getBody()->getContents(), associative: true);

        $contributors = [];
        foreach ($commits as $commit) {
            // Prefer GitHub login if available
            if (!empty($commit['author']['login'])) {
                $contributors[$commit['author']['login']] = true;
            } else {
                // fallback to commit author name (works for merged PRs)
                $name = $commit['commit']['author']['email'] ?? null;
                if ($name) {
                    $contributors[$name] = true;
                }
            }
        }

        return array_keys($contributors);
    }
}