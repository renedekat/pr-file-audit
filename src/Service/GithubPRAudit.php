<?php

namespace Service;

use DTO\FileAuditCollection;
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
        if (!$this->isMerged($repoFullName, $prNumber)) {
            return; // Skip unmerged PRs
        }

        $files = $this->getPullRequestFiles($repoFullName, $prNumber);
        $contributors = $this->getPullRequestContributors($repoFullName, $prNumber);

        foreach ($files as $filename) {
            $collection->add(new FileAuditDto($repoFullName, $filename, $contributors));

        }
    }

    private function isMerged(string $repo, int $pr): bool
    {
        $response = $this->client->get("repos/{$repo}/pulls/{$pr}");
        $prData = json_decode($response->getBody()->getContents(), true);
        return !empty($prData['merged']);
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