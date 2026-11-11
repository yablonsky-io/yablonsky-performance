# Yablonsky Performance

**WordPress performance, caching, and origin protection — local-first, measurable, and under your control.**

Yablonsky Performance (**YPerf**) helps WordPress site owners speed up repeat requests, reduce the cost of uncached page loads, and protect server capacity when traffic rises. It combines practical performance tools with evidence, controlled adoption, and rollback rather than opaque “one-click” promises.

[Product overview](https://yablonsky.io/performance/) · [WordPress plugin description](readme.txt) · [Development and release status](#release-status) · [Support](SUPPORT.md)

> **Development baseline: 2.56.0.** The source tree is published for review and collaboration. A verified installation package has **not** been published as a GitHub Release. Do not treat this branch or GitHub's automatic source archives as production installation packages.

## What YPerf does

| Area | Practical benefit |
| --- | --- |
| **Page cache** | Serve eligible anonymous pages quickly, with cacheability decisions, HIT/MISS evidence, selective purge, and bounded prewarming |
| **Browser cache** | Apply conservative cache behavior to YPerf-owned public HTML |
| **Redis object cache** | Optionally add persistent object caching with ownership checks, isolation, verification, and rollback |
| **Frontend delivery** | Schedule eligible JavaScript and assets while preserving dependencies and transactional WooCommerce routes |
| **Origin Survival** | Bound expensive cold-origin request concurrency to help keep WordPress responsive under bursts |
| **Performance evidence** | Inspect cacheability, run local benchmarks and Runtime Acceptance, and compare observed results |
| **Performance Autopilot** | Apply deterministic, bounded optimizations with checks and rollback boundaries |
| **Provider integrations** | Optionally coordinate with Cloudflare, migrate from W3 Total Cache, or request Yablonsky AI analysis |

YPerf's operating principle:

**Repeat HIT → cache. First HIT → reduce work. Concurrency → protect the origin.**

## Built for real WordPress environments

- **Native-first.** No mandatory Docker, Nginx, Redis, Cloudflare, or AI subscription.
- **WooCommerce-aware.** Cart, checkout, account, logged-in, and session-sensitive requests retain conservative behavior.
- **Owner-aware.** Existing CDN, third-party cache, drop-in and native server authorities are not silently replaced.
- **Human-governed.** Changes that require authority follow measured evidence, operator approval, bounded execution, verification, and rollback where supported.
- **Independent responsibilities.** YPerf owns performance and request efficiency; YSEO owns search/indexing policy and YSec owns security/identity verification.

### Page caching and cache ownership

YPerf includes a portable page-cache path for eligible public responses, together with policy-aware bypass rules, invalidation and prewarming. Its optional advanced-cache drop-in is adopted only through explicit authority checks; sites using Nginx, CDN, or third-party caches retain their ownership unless a verified handover is approved.

### Object caching

An optional Redis integration provides persistent object caching with site-scoped isolation, health evidence and controlled lifecycle operations. Destructive server-wide Redis commands are outside the normal YPerf operating contract.

### Frontend and origin efficiency

The frontend optimizer focuses on dependency-safe scheduling and conservative delivery on eligible routes, especially around WooCommerce. Origin Survival and query-cost visibility address the other side of performance: how expensive the first request becomes before a cache HIT exists.

### Optional integrations

- **Cloudflare:** reviewed performance settings and bounded purge coordination with explicit administrator authority.
- **W3 Total Cache:** structured migration/handover with ownership and rollback evidence.
- **Yablonsky AI:** optional, administrator-requested analysis of privacy-minimized performance evidence. The local features do not require an AI account.

See [readme.txt](readme.txt) for the detailed feature, privacy, integration and operational boundaries.

## How the workflow operates

1. **Observe** the WordPress environment and identify the current performance authority.
2. **Measure** request cost, caching and runtime evidence.
3. **Recommend** only an optimization supported by the available evidence.
4. **Approve and apply** the selected change within its defined boundary.
5. **Verify** the result and retain or roll back as appropriate.

This workflow is intended to complement existing WordPress configurations, not replace them invisibly.

## Languages and accessibility

The development localization inventory covers **36 language families and 41 locale variants**. Catalog coverage is distinct from editorial review and WordPress visual acceptance. Do not assume every translation or RTL layout has been finally accepted because a catalog exists.

## Release status

| Item | Current state |
| --- | --- |
| Source baseline | **2.56.0** |
| Plugin PHP header and WordPress `readme.txt` | **2.56.0** |
| GitHub installation release | **Not yet published / not certified** |
| WordPress.org publication | **Not claimed** |
| Release verification | **Pending complete evidence and acceptance** |

**Do not install GitHub's “Source code (zip)” as if it were the approved WordPress distribution artifact.** A release must include the verified, correctly named plugin ZIP after the agreed checks have passed.

[GitHub Releases](https://github.com/yablonsky-io/yablonsky-performance/releases) · [Changelog](CHANGELOG.md) · [Pre-publication guidance](docs/WORDPRESS_ORG_PREPUBLICATION_GUIDANCE.md)

## For contributors and reviewers

- [Contributing](CONTRIBUTING.md) — focused contributions and accepted-functionality boundaries
- [Security](SECURITY.md) — responsible private reporting
- [Support](SUPPORT.md) — help, reports, and triage
- [Agent instructions](AGENTS.md) — read the advisory WordPress.org guidance before changes
- [Public repository content policy](docs/PUBLIC_REPOSITORY_CONTENT_POLICY.md) — keep private reports and internal content private

**Scope discipline:** a WordPress.org compliance review is not authorization to rewrite the product. Preserve accepted behavior and require evidence plus explicit approval for changes to runtime semantics, migrations, storage or integration contracts.

## Ecosystem

Yablonsky.io develops a family of independently useful WordPress products.

- [Yablonsky SEO](https://yablonsky.io/seo/) — discovery, indexing and AI search visibility
- [Yablonsky Security](https://github.com/yablonsky-io/yablonsky-security) — WordPress security and request protection
- [Yablonsky Multilingual](https://github.com/yablonsky-io/yablonsky-multilingual) — multilingual workflows
- [Yablonsky AI Chatbots](https://yablonsky.io/ai-chatbots/) — AI assistance and business workflows

## Compatibility and license

- **WordPress:** requires 6.0 or later (see the plugin's tested-version statement for its documented ceiling).
- **PHP:** requires 7.4 or later.
- **License:** GPL-2.0-or-later; see the [GNU GPL v2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
- **Product versions:** `MAJOR.MINOR.PATCH`, with MINOR 0–99. API, hook and stored-data compatibility is versioned independently.

---

Developed by **[Yablonsky.io](https://yablonsky.io/)**. Product documentation and public communication are maintained in English; shipped UI localizations are separate product assets.
