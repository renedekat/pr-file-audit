# PR File Audit Tool

This PHP project generates a CSV audit of all files modified across specified GitHub pull requests, including a list of contributors for each file.

---

## Prerequisites

- PHP 8.1 or higher
- Composer
- GitHub Personal Access Token (with at least `repo` access for private repositories)
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
  cp .env.example .env
```

Edit .env and set your GitHub token:
`GITHUB_TOKEN=your_personal_access_token`

## Prepare the repositories/PRs list
```bash
  cp repos_prs.json.example repos_prs.json
```

Edit repos_prs.json with your repository names and PR numbers in the following format:
```
{
    "org/repo1": [12, 34],
    "org/repo2": [7, 8, 9]
}
```

## Usage
Run the audit script:
```bash
    php index.php
```

The script will fetch all files changed in the specified PRs and their contributors, then output a CSV file:

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

