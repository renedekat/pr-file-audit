<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_ENV['GITHUB_TOKEN'];
$inputFile = __DIR__ . '/repos_prs.json';
$outputFile = __DIR__ . '/files_audit.csv';

// Read input JSON file (repos => PRs)
$repos = json_decode(file_get_contents($inputFile), true);

$client = new Client([
    'base_uri' => 'https://api.github.com/',
    'headers' => [
        'Authorization' => "token $token",
        'Accept' => 'application/vnd.github+json',
    ],
]);

$data = [];

// Build internal structure: repo => file => contributors
foreach ($repos as $repoFullName => $prNumbers) {
    foreach ($prNumbers as $prNumber) {
        // Get PR files
        $response = $client->get("repos/{$repoFullName}/pulls/{$prNumber}/files?per_page=100");
        $files = json_decode($response->getBody()->getContents(), true);

        // Get PR commits to identify contributors
        $responseCommits = $client->get("repos/{$repoFullName}/pulls/{$prNumber}/commits?per_page=100");
        $commits = json_decode($responseCommits->getBody()->getContents(), true);

        $contributors = [];
        foreach ($commits as $commit) {
            if (isset($commit['author']['login'])) {
                $contributors[$commit['author']['login']] = true;
            }
        }
        $contributors = array_keys($contributors);

        foreach ($files as $file) {
            $filename = $file['filename'];
            if (!isset($data[$repoFullName][$filename])) {
                $data[$repoFullName][$filename] = [];
            }
            $data[$repoFullName][$filename] = array_unique(array_merge($data[$repoFullName][$filename], $contributors));
        }
    }
}

// Write CSV
$handle = fopen($outputFile, 'w');

// Determine max contributors for header
$maxContributors = 0;
foreach ($data as $files) {
    foreach ($files as $contributors) {
        $maxContributors = max($maxContributors, count($contributors));
    }
}

// Write header
$header = array_merge(['Repo Name', 'File Name'], array_map(fn($i) => "Contributor $i", range(1, $maxContributors)));
fputcsv($handle, $header, ',', '"', '\\');

// Write rows
foreach ($data as $repo => $files) {
    foreach ($files as $filename => $contributors) {
        $row = array_merge([$repo, $filename], $contributors);
        fputcsv($handle, $row, ',', '"', '\\');
    }
}

fclose($handle);
echo "CSV audit saved to $outputFile\n";
