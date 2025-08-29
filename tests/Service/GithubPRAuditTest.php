<?php

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Service\GithubPRAudit;
use DTO\FileAuditCollection;

class GithubPRAuditTest extends TestCase
{
    public function testFetchAuditDataMergedPr(): void
    {
        $repos = ['org/repo1' => [101]];

        $mockClient = $this->createMock(Client::class);

        $mockClient->method('get')->willReturnOnConsecutiveCalls(
            new Response(200, [], json_encode(['merged' => true])), // isMerged
            new Response(200, [], json_encode([['filename' => 'file1.php'], ['filename' => 'file2.php']])), // files
            new Response(200, [], json_encode([['author' => ['login' => 'alice']], ['author' => ['login' => 'bob']]])) // contributors
        );

        $audit = new GithubPRAudit(repos: $repos, client: $mockClient);
        $collection = $audit->fetchAuditData();

        $this->assertInstanceOf(FileAuditCollection::class, $collection);
        $items = iterator_to_array($collection);

        $this->assertCount(2, $items);
        $this->assertEquals('file1.php', $items[0]->filename);
        $this->assertEquals(['alice', 'bob'], $items[0]->contributors);
    }

    public function testFetchAuditDataUnmergedPrSkipsFiles(): void
    {
        $repos = ['org/repo1' => [102]];

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')->willReturn(new Response(200, [], json_encode(['merged' => false])));

        $audit = new GithubPRAudit(repos: $repos, client: $mockClient);
        $collection = $audit->fetchAuditData();

        $this->assertCount(0, iterator_to_array($collection));
    }

    public function testContributorFallbackToEmail(): void
    {
        $repos = ['org/repo2' => [103]];

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')->willReturnOnConsecutiveCalls(
            new Response(200, [], json_encode(['merged' => true])),
            new Response(200, [], json_encode([['filename' => 'fileX.php']])),
            new Response(200, [], json_encode([['author' => null, 'commit' => ['author' => ['email' => 'fallback@example.com']]]]))
        );

        $audit = new GithubPRAudit(repos: $repos, client: $mockClient);
        $collection = $audit->fetchAuditData();
        $items = iterator_to_array($collection);

        $this->assertEquals(['fallback@example.com'], $items[0]->contributors);
    }

    public function testMultiplePrsPerRepo(): void
    {
        $repos = ['org/repo3' => [201, 202]];

        $mockClient = $this->createMock(Client::class);

        $mockClient->method('get')->willReturnOnConsecutiveCalls(
        // PR 201
            new Response(200, [], json_encode(['merged' => true])),
            new Response(200, [], json_encode([['filename' => 'fileA.php']])),
            new Response(200, [], json_encode([['author' => ['login' => 'user1']]])),
            // PR 202
            new Response(200, [], json_encode(['merged' => true])),
            new Response(200, [], json_encode([['filename' => 'fileB.php']])),
            new Response(200, [], json_encode([['author' => ['login' => 'user2']]]))
        );

        $audit = new GithubPRAudit(client: $mockClient, repos: $repos);
        $collection = $audit->fetchAuditData();
        $items = iterator_to_array($collection);

        $this->assertCount(2, $items);
        $this->assertEquals('fileA.php', $items[0]->filename);
        $this->assertEquals('fileB.php', $items[1]->filename);
    }
}
