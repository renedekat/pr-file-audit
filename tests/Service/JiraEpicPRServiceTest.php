<?php

namespace Tests\Service;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Service\JiraEpicPRService;
use DTO\JiraPRCollection;

class JiraEpicPRServiceTest extends TestCase
{
    private Client $mockClient;
    private string $jiraDomain = 'https://example.atlassian.net';

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
    }

    public function testGetPullRequestsByEpicReturnsCollection(): void
    {
        $mockClient = $this->createMock(Client::class);

        // Provide responses for all GET calls in order
        $mockClient->method('get')->willReturnOnConsecutiveCalls(
        // detectJqlFormat: first format check returns issues
            new Response(200, [], json_encode(['issues' => [['id' => '123']]])),

            // fetchEpicChildren returns issues
            new Response(200, [], json_encode(['issues' => [['id' => '123']]])),

            // fetchPullRequests returns pull requests for the issue
            new Response(200, [], json_encode([
                'detail' => [
                    [
                        'pullRequests' => [
                            [
                                'repositoryName' => 'repo1',
                                'id' => '#101',
                                'status' => 'MERGED',
                                'url' => 'http://github.com/repo1/pull/101'
                            ],
                            [
                                'repositoryName' => 'repo2',
                                'id' => '#202',
                                'status' => 'OPEN',
                                'url' => 'http://github.com/repo2/pull/202'
                            ]
                        ]
                    ]
                ]
            ]))
        );

        $expected = [
            ['repo1', 101, 'MERGED'],
            ['repo2', 202, 'OPEN'],
        ];

        $service = new JiraEpicPRService($mockClient, 'https://example.atlassian.net');
        $collection = $service->getPullRequestsByEpic(['EPIC-1']);

        $this->assertCount(2, $collection);
        $i = 0;
        foreach ($collection as $pr) {
            $this->assertEquals($expected[$i][0], $pr->repositoryName);
            $this->assertEquals($expected[$i][1], $pr->prNumber);
            $this->assertEquals($expected[$i][2], $pr->status);
            $i++;
        }
    }

    public function testGetPullRequestsByEpicHandlesNoPRs(): void
    {
        $epics = ['EPIC-2'];

        $this->mockClient->method('get')
            ->willReturnOnConsecutiveCalls(
            // detectJqlFormat
                new Response(200, [], json_encode(['issues' => [['id' => '999']]])),
                // fetchEpicChildren returns children
                new Response(200, [], json_encode(['issues' => [['id' => '999']]])),
                // fetchPullRequests returns empty
                new Response(200, [], json_encode(['detail' => [[]]]))
            );

        $service = new JiraEpicPRService(client: $this->mockClient, jiraDomain: $this->jiraDomain);
        $collection = $service->getPullRequestsByEpic($epics);

        $this->assertCount(0, iterator_to_array($collection));
    }

    public function testDetectJqlFormatThrowsRuntimeException(): void
    {
        $mockClient = $this->createMock(\GuzzleHttp\Client::class);

        // Make the client throw a GuzzleException for any get() call
        $mockClient->method('get')
            ->willThrowException(new \GuzzleHttp\Exception\ConnectException(
                'Network failure',
                $this->createMock(\Psr\Http\Message\RequestInterface::class)
            ));

        $service = new \Service\JiraEpicPRService($mockClient, 'https://example.atlassian.net');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not determine JQL format for epics.');

        $service->getPullRequestsByEpic(['TMNT-1234']);
    }

    public function testDetectJqlFormatUsesCache(): void
    {
        $epics = ['EPIC-2'];

        $this->mockClient->method('get')
            ->willReturnOnConsecutiveCalls(
                // detectJqlFormat
                new Response(200, [], json_encode(['issues' => [['id' => '999']]])),
                // fetchEpicChildren returns children
                new Response(200, [], json_encode(['issues' => [['id' => '999']]])),
                // fetchPullRequests returns empty
                new Response(200, [], json_encode(['detail' => [[]]])),
                // fetchEpicChildren returns children
                new Response(200, [], json_encode(['issues' => [['id' => '999']]])),
                // fetchPullRequests returns empty
                new Response(200, [], json_encode(['detail' => [[]]]))
            );

        $service = new JiraEpicPRService(client: $this->mockClient, jiraDomain: $this->jiraDomain);

        // First call: cache is empty, so the client is called
        $service->getPullRequestsByEpic($epics);

        // Second call: same epic, should use cache, so fewer or no additional client calls
        $service->getPullRequestsByEpic($epics);

        $this->assertNotEmpty($service->jqlFormatCache);
    }


    public function testPullRequestIdParsingRemovesHash(): void
    {
        $epics = ['EPIC-4'];

        $this->mockClient->method('get')
            ->willReturnOnConsecutiveCalls(
            // detectJqlFormat
                new Response(200, [], json_encode(['issues' => [['id' => '555']]])),
                // fetchEpicChildren
                new Response(200, [], json_encode(['issues' => [['id' => '555']]])),
                // fetchPullRequests with PR id like "#202"
                new Response(200, [], json_encode([
                    'detail' => [
                        [
                            'pullRequests' => [
                                [
                                    'repositoryName' => 'repo2',
                                    'id' => '#202',
                                    'status' => 'OPEN',
                                    'url' => 'http://github.com/repo2/pull/202'
                                ]
                            ]
                        ]
                    ]
                ]))
            );

        $service = new JiraEpicPRService(client: $this->mockClient, jiraDomain: $this->jiraDomain);
        $collection = $service->getPullRequestsByEpic($epics);

        $items = iterator_to_array($collection);
        $this->assertEquals(202, $items[0]->prNumber);
        $this->assertEquals('OPEN', $items[0]->status);
    }
}
