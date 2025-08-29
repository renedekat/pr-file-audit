<?php

use GuzzleHttp\Client;
use Service\CsvExporter;
use Service\JiraEpicPRService;
use Service\JsonExporter;
use Service\StdOutJsonExporter;

require __DIR__ . '/../src/bootstrap.php';

$epicKeys = json_decode(json: file_get_contents(filename: __DIR__ . '/../storage/epic_keys.json'), associative: true);

if (!is_array($epicKeys)) {
    fwrite(STDERR, "epic_keys.json is invalid\n");
    exit(1);
}

$client = new Client(config: [
    'auth' => [$_ENV['JIRA_EMAIL'], $_ENV['JIRA_TOKEN']],
]);
$service = new JiraEpicPRService(client: $client, jiraDomain: $_ENV['JIRA_DOMAIN']);

$collection = $service->getPullRequestsByEpic($epicKeys);

$exporter = create_exporter(argv: $argv, basename: 'repos_prs', default: 'json');
$collection->exportWith($exporter);
notify_export_success(exporter: $exporter, basename: 'repos_prs');
