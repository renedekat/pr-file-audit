<?php
declare(strict_types=1);

$dotenvPath = __DIR__ . '/config/.env';
$dotenvExamplePath = __DIR__ . '/config/.env.example';
$epicsFile = __DIR__ . '/storage/epic_keys.json';

/**
 * Prompt user for input, optionally masked (for secrets)
 */
function prompt(string $message, bool $mask = false): string
{
    if ($mask && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        echo $message;
        system('stty -echo');
        $value = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        echo $message;
        $value = trim(fgets(STDIN));
    }
    return $value;
}

/**
 * Copy .env.example to .env if it doesn't exist
 */
function setupEnvFile(string $dotenvPath, string $dotenvExamplePath): void
{
    if (!file_exists($dotenvPath)) {
        if (!file_exists($dotenvExamplePath)) {
            fwrite(STDERR, "Error: config/.env.example not found.\n");
            exit(1);
        }
        copy($dotenvExamplePath, $dotenvPath);
        echo "Created config/.env from .env.example\n";
    } else {
        echo "config/.env already exists, skipping copy\n";
    }
}

/**
 * Prompt for environment variables and update .env
 */
function updateEnvVariables(string $dotenvPath, array $envVars): void
{
    // Load existing .env
    $envData = [];
    if (file_exists($dotenvPath)) {
        foreach (file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $envData[$key] = $value;
            }
        }
    }

    foreach ($envVars as $key => $mask) {
        if (!empty($envData[$key])) {
            echo "$key already set, skipping.\n";
            continue;
        }
        $value = prompt("$key: ", $mask);
        $envData[$key] = $value;
    }

    // Write back .env
    $lines = [];
    foreach ($envData as $key => $value) {
        $lines[] = "$key=$value";
    }
    file_put_contents($dotenvPath, implode("\n", $lines) . "\n");
    echo ".env updated successfully.\n";
}

/**
 * Prompt for Jira epics and save to storage/epic_keys.json
 */
function setupJiraEpics(string $epicsFile): void
{
    if (file_exists($epicsFile)) {
        echo "storage/epic_keys.json already exists, skipping epic entry.\n";
        return;
    }

    $epics = [];
    echo "Enter Jira epics (one per line). Leave empty to finish:\n";
    while (true) {
        $epic = prompt("Epic key: ");
        if ($epic === '') {
            break;
        }
        $epics[] = $epic;
    }

    $storagePath = dirname($epicsFile);
    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0777, true);
    }

    file_put_contents($epicsFile, json_encode($epics, JSON_PRETTY_PRINT));
    echo "Jira epics saved to storage/epic_keys.json\n";
}

/**
 * Print Composer scripts and descriptions
 */
function printInstructions(): void
{
    echo "Setup complete!\n\n";
    echo "Next steps:\n";
    echo "--------------------------\n";

    $composerFile = __DIR__ . '/composer.json';
    if (!file_exists($composerFile)) {
        echo "composer.json not found.\n";
        return;
    }

    $composerData = json_decode(file_get_contents($composerFile), true);
    $scriptDescriptions = $composerData['extra']['script-descriptions'] ?? [];

    if (!empty($composerData['scripts'])) {
        echo "You can use the following Composer scripts:\n\n";
        foreach ($composerData['scripts'] as $script => $cmd) {
            if ($script === 'post-install-cmd') {
                continue;
            }
            $desc = $scriptDescriptions[$script] ?? '';
            echo $desc ? "  composer run $script  # $desc\n" : "  composer run $script\n";
        }
    } else {
        echo "No scripts found in composer.json\n";
    }

    echo "\nExample usage:\n";
    echo "  composer run test-coverage\n";
    echo "  composer run extract-prs\n";
    echo "  composer run extract-changes\n";
    echo "  composer run run-all\n";
    echo "--------------------------\n";
}

// --- Run setup steps in order ---
setupEnvFile($dotenvPath, $dotenvExamplePath);

$envVars = [
    'GITHUB_TOKEN' => true,
    'JIRA_DOMAIN' => false,
    'JIRA_EMAIL' => false,
    'JIRA_TOKEN' => true,
];

updateEnvVariables($dotenvPath, $envVars);
setupJiraEpics($epicsFile);
printInstructions();
