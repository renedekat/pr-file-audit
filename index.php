<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/bootstrap.php';

use Service\GithubPRAudit;
use Service\CsvExporter;

// Load environment variables and repo JSON in bootstrap.php
$token = $_ENV['GITHUB_TOKEN'];
$repos = json_decode(file_get_contents(__DIR__ . '/config/repos_prs.json'), true);

$githubAudit = new GithubPRAudit($token, $repos);
$auditData = $githubAudit->fetchAuditData();

$exporter = new CsvExporter(__DIR__ . '/files_audit.csv');
$exporter->export($auditData);

echo "CSV audit saved to files_audit.csv\n";
