![PHP Tests](https://github.com/renedekat/pr-file-audit/actions/workflows/php.yml/badge.svg)

# PR File Audit Tool

This PHP project generates a CSV audit of all files modified across specified GitHub pull requests, including a list of contributors for each file.

---

## Prerequisites

- PHP 8.1 or higher
- Composer
- GitHub Personal Access Token (with at least `repo` access for private repositories)
- Jira API Access Token
- Git installed

---

## Setup

1. Clone the repository

```bash
  git clone <your-repo-url>
  cd <repo-folder>
```

## Install dependencies
```bash
  composer install
```

## Configure environment variables
```bash
  cp ./config/.env.example ./config/.env
```

Edit .env and set your GitHub token:
`GITHUB_TOKEN=your_personal_access_token`

## Prepare the list of JIRA Epics
```bash  
  cp ./config/epic_keys.json.example ./storage/epic_keys.json
```

## Prepare the repositories/PRs list
If you already have a list of pull requests you can use the file below. Or let the extractPullRequestsFromJiraEpics
script generate it for you instead.
```bash  
  cp ./config/repos_prs.json.example ./storage/repos_prs.json
```

Edit `./config/repos_prs.json` with your repository names and PR numbers in the following format:
```
{
    "org/repo1": [12, 34],
    "org/repo2": [7, 8, 9]
}
```

## Usage
Run the audit scripts:
```bash
    php extractPullRequestsFromJiraEpics.php #optional if you don't have your PR ids yet
    php extractFileChangesFromPullRequests.php
```

The latter script will fetch all files changed in the specified PRs and their contributors, then output a CSV file:

`files_audit.csv`

## Output
The CSV columns:
- Repo Name
- File Name
- Contributor 1
- Contributor 2
- ...

The number of contributor columns adjusts automatically based on how many contributors are detected per file.

## Notes
- The project uses Guzzle for GitHub API requests.
- Environment variables are loaded using vlucas/phpdotenv.
- Output files (files_audit.csv) and input JSON (repos_prs.json) are ignored by .gitignore.
- If a file is modified in multiple PRs, contributors are merged to avoid duplicates.

