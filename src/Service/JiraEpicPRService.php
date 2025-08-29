<?php

namespace Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DTO\JiraPRDto;
use DTO\JiraPRCollection;
use RuntimeException;

class JiraEpicPRService
{
    private Client $client;
    private string $jiraDomain;
    public readonly ?string $jqlFormatCache;

    public function __construct(Client $client, string $jiraDomain)
    {
        $this->client = $client;
        $this->jiraDomain = $jiraDomain;
    }

    public function getPullRequestsByEpic(array $epicKeys): JiraPRCollection
    {
        $collection = new JiraPRCollection();

        foreach ($epicKeys as $epicKey) {
            $issues = $this->fetchEpicChildren(epicKey: $epicKey);

            foreach ($issues as $issue) {
                $prs = $this->fetchPullRequests(issueId: $issue['id']);

                foreach ($prs as $pr) {
                    $collection->add(dto: new JiraPRDto(
                        repositoryName: $pr['repositoryName'],
                        prNumber: $pr['prNumber'],
                        status: $pr['status'],
                        url: $pr['url']
                    ));
                }
            }
        }

        return $collection;
    }

    private function fetchEpicChildren(string $epicKey): array
    {
        $format = $this->detectJqlFormat(epicKey: $epicKey);
        $jql = sprintf($format, $epicKey);

        $response = $this->client->get(uri: "{$this->jiraDomain}/rest/api/3/search", options: [
            'query' => [
                'jql' => $jql,
                'fields' => 'key'
            ]
        ]);

        return json_decode(json: $response->getBody(), associative: true)['issues'] ?? [];
    }

    private function detectJqlFormat(string $epicKey): string
    {
        if (isset($this->jqlFormatCache)) {
            return $this->jqlFormatCache;
        }

        $formats = [
            '"Epic Link" = %s',
            'parent = %s'
        ];

        foreach ($formats as $format) {
            $jql = sprintf($format, $epicKey);

            try {
                $response = $this->client->get(uri: "{$this->jiraDomain}/rest/api/3/search", options:[
                    'query' => [
                        'jql' => $jql,
                        'maxResults' => 1,
                        'fields' => 'key'
                    ]
                ]);

                $data = json_decode(json: $response->getBody(), associative: true);

                if (isset($data['issues'])) {
                    $this->jqlFormatCache = $format;
                    return $format;
                }
            } catch (GuzzleException $exception) {
                // ignore
            }
        }

        throw new RuntimeException(message: "Could not determine JQL format for epics.");
    }

    private function fetchPullRequests(string $issueId): array
    {
        $response = $this->client->get(uri: "{$this->jiraDomain}/rest/dev-status/1.0/issue/detail", options:[
            'query' => [
                'issueId' => $issueId,
                'applicationType' => 'GitHub',
                'dataType' => 'pullrequest'
            ]
        ]);

        $data = json_decode(json: $response->getBody(), associative: true);
        $prs = [];

        foreach ($data['detail'][0]['pullRequests'] ?? [] as $pr) {
            $repo = $pr['repositoryName'] ?? null;
            $prId = isset($pr['id']) ? (int) ltrim($pr['id'], '#') : null;
            $status = $pr['status'] ?? 'UNKNOWN';
            $url = $pr['url'] ?? '';

            if ($repo && $prId) {
                $prs[] = [
                    'repositoryName' => $repo,
                    'prNumber' => $prId,
                    'status' => $status,
                    'url' => $url
                ];
            }
        }

        return $prs;
    }
}
