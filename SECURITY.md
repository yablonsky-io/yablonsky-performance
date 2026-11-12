# Security Policy

## Supported versions

Security updates are provided for actively maintained releases. The 2.56.0 GitHub prerelease is distributed for controlled installation after successful packaging and Plugin Check. This is not a claim of WordPress.org approval or a guarantee across all deployments.

## Reporting vulnerabilities

Please do **not** post exploit details, credentials, or affected production site information in a public GitHub issue.

Use GitHub's **Report a vulnerability** private reporting option when available, or contact the project owner through [Yablonsky.io](https://yablonsky.io/).

## Security principles

YPerf uses administrator authorization, WordPress nonce/capability checks, bounded operations, ownership verification, runtime proof, and rollback where applicable. Optional integrations must operate only under explicitly approved scope.

A passing static check does not replace integration tests or a security review.
