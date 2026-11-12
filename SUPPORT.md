# Support and project status

**Yablonsky Performance (YPerf)** is a local-first WordPress performance and caching plugin. The current public GitHub source baseline is **2.56.0**; an installable ZIP is available in the [2.56.0 GitHub Release](https://github.com/yablonsky-io/yablonsky-performance/releases/tag/v2.56.0), with WordPress.org compliance still under review.

## Start here

- [Product overview](https://yablonsky.io/performance/)
- [Feature and technical details](readme.txt)
- [GitHub release status](https://github.com/yablonsky-io/yablonsky-performance/releases)
- [Contribution guidance](CONTRIBUTING.md)
- [Security reporting](SECURITY.md)

## Reporting a reproducible problem

A useful report includes the plugin version/commit, WordPress and PHP versions, active caching providers, the relevant feature, expected/actual result, and sanitized reproducible steps. For WooCommerce concerns, note whether the route is a product, cart, checkout or account page. For cache concerns, identify which system owns the cache (YPerf, CDN, Nginx or another provider).

Never include secrets, personal data, raw logs, private URLs, origin addresses, screenshots of internal conversations, or customer details in public issues.

## Project boundaries

YPerf focuses on request efficiency, caching and performance evidence. Yablonsky SEO handles search indexing/discovery; Yablonsky Security handles identity verification and abuse protection. Existing services are integrated only through the relevant established authority and approval flow.

## Before installing

Use the specific [2.56.0 installation ZIP](https://github.com/yablonsky-io/yablonsky-performance/releases/download/v2.56.0/yablonsky-performance-2.56.0.zip), not GitHub's generated source archives. The GitHub Release is published; WordPress.org directory approval remains a separate process. See the [release-status section](README.md#release-status).
