# Contributing to Yablonsky Performance

Thank you for helping improve reliable WordPress performance.

## Before proposing a change

Read [README.md](README.md), [AGENTS.md](AGENTS.md), the [WordPress.org pre-publication guidance](docs/WORDPRESS_ORG_PREPUBLICATION_GUIDANCE.md), and the [public repository content policy](docs/PUBLIC_REPOSITORY_CONTENT_POLICY.md).

YPerf has a previously accepted functional baseline. A request to improve documentation or meet WordPress.org publication rules is **not** permission to redesign caching, change routing, alter integrations, rewrite migrations or modify customer data.

## Contributions we welcome

- Reproducible bug reports that identify the affected WordPress/PHP versions and a minimal reproduction.
- Focused documentation fixes that reflect shipped behavior.
- Explicitly approved, narrowly scoped fixes for demonstrated defects or WordPress.org requirements.
- Tests that verify a specific accepted behavior without mutating product source during installation.
- Proposed ideas and enhancements, **separately** from release-compliance work.

## Workflow

1. Describe the problem and its evidence before editing code.
2. Distinguish a demonstrable defect from a preference or future enhancement.
3. Identify the smallest affected surface and compatibility risks.
4. Obtain approval before changing runtime behavior, storage, API or hook contracts, caching ownership, upgrade paths, translations or UI workflows.
5. Keep PRs focused. Include targeted tests and relevant before/after behavior.
6. Do not claim CI acceptance unless a run has actually completed and passed.
7. Never add passwords, API tokens, customer data, private troubleshooting logs, internal chat transcripts or unapproved screenshots.
8. Do not silently introduce Docker, Nginx, Redis or any external provider as a mandatory dependency.

## WordPress.org guidance

The [publication guidance](docs/WORDPRESS_ORG_PREPUBLICATION_GUIDANCE.md) is **advisory** during ongoing feature work. In a pre-publication pass, only proven publication findings should generate minimal patches, with explicit human approval for behavior changes.

Use the product version canon: `MAJOR.MINOR.PATCH`, MINOR 0–99. Keep externally published package names and all metadata aligned.

## Pull requests

A useful PR description includes the problem, evidence, changed files, acceptance criteria, compatibility impact, and test results. Never publish an installation release or edit protected functional code solely to make a repository look more polished.
