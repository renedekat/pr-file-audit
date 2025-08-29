<?php

require __DIR__ . '/../src/bootstrap.php';

use GuzzleHttp\Client;
use Service\GithubPRAudit;

$token = $_ENV['GITHUB_TOKEN'];
$repos = json_decode(json: file_get_contents(filename: storage_path(file: 'repos_prs.json')), associative: true);

$client = new Client([
    'base_uri' => 'https://api.github.com/',
    'headers' => [
        'Authorization' => "token {$token}",
        'Accept' => 'application/vnd.github+json',
    ],
]);

$githubAudit = new GithubPRAudit(client: $client, repos: $repos);
$collection = $githubAudit->fetchAuditData();

$exporter = create_exporter(argv: $argv, basename: 'files_audit', default: 'csv');
$collection->exportWith($exporter);
notify_export_success(exporter: $exporter, basename: 'files_audit');
