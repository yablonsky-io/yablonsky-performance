# Yablonsky Performance — WordPress Performance, Caching & Origin Protection

**Yablonsky Performance (YPerf)** is a local-first performance management plugin for WordPress. It helps site owners improve page speed, manage caching, reduce origin-server load, and verify the results of optimization with measurable evidence.

[Product overview](https://yablonsky.io/performance/) · [Yablonsky.io](https://yablonsky.io/) · [Yablonsky SEO](https://yablonsky.io/seo/) · [Yablonsky AI Chatbots](https://yablonsky.io/ai-chatbots/)

> **Development status:** Source migration and release validation are in progress. The current development baseline is **2.56.0**. This repository should not be treated as an approved production release until the release gate and published release assets are complete.

## Overview

YPerf is built around a simple principle: make repeated requests fast, reduce the cost of the first uncached request, and protect the origin when traffic rises.

The plugin provides a WordPress-native operating path without requiring Docker, Nginx, or a separate paid performance service. Optional infrastructure integrations can extend capabilities when an administrator explicitly chooses them.

## Performance and caching features

### Page cache and browser cache

- Portable page caching for eligible anonymous requests, with cacheability rules.
- Cache HIT/MISS evidence, safe invalidation, selective purge, and controlled prewarming.
- Browser-cache policy for YPerf-owned public HTML.
- Awareness of authenticated sessions, WooCommerce checkout, cart and account flows.

### Redis object cache

- Optional persistent object caching using Redis.
- Site-specific namespace and generation-based invalidation.
- Ownership checks, health verification, controlled adoption and rollback.
- No Redis FLUSHDB, FLUSHALL, or configuration writes in the YPerf operating contract.

### Frontend delivery and WooCommerce

- Route-aware optimization of eligible JavaScript and frontend assets.
- Dependency-aware script scheduling and conservative defer behavior.
- Protection of transaction-sensitive WooCommerce routes.
- Bounded first-hit optimization without blindly delaying essential scripts.

### Origin protection and performance evidence

- Origin Survival admission control for costly cold requests and crawler concurrency.
- Runtime Acceptance and evidence-driven optimization results.
- Local benchmarking, cacheability checks, telemetry and operation history.
- Performance Autopilot with deterministic safeguards and rollback boundaries.

### Integrations

- **Cloudflare:** optional zone-scoped performance controls and cache synchronization after administrator approval.
- **W3 Total Cache:** migration and explicit handover with provider ownership and rollback checks.
- **Yablonsky AI:** optional, administrator-initiated performance analysis; core functionality works without an AI account.

## How YPerf works

1. **Measure** site performance and available infrastructure capabilities.
2. **Recommend** optimizations grounded in local evidence.
3. **Approve** changes under administrator control.
4. **Apply** a bounded change with ownership checks.
5. **Verify** the result and keep or roll back the change.

**Human-governed operation:** YPerf does not silently take over existing Nginx, CDN, third-party caching, or security rules.

## Localization

The development workset includes 36 language families and regional variants. Translation coverage and binary catalog consistency are distinct from final editorial and visual acceptance. Do not interpret catalog completeness as a claim that all UI layouts have passed production visual testing.

## Installation and release status

Stable ZIP downloads will be made available through [GitHub Releases](https://github.com/yablonsky-io/yablonsky-performance/releases) after the final WordPress compatibility, PHP matrix, WPCS, Plugin Check, security, accessibility, localization, and runtime acceptance gates pass.

The software is not currently represented here as a newly verified public release.

## Security and privacy

- Caching and optimization are local-first.
- Elevated actions are subject to WordPress permissions and nonce protections.
- Filesystem changes and provider handovers require ownership checks and controlled boundaries.
- Optional AI requests are administrator initiated with privacy-minimized evidence.
- Provider credentials remain server-side; never commit real API keys, logs, environment files, or customer data.

For security-related reports, use a private contact route rather than disclosing exploitable details in public issues before a fix is available.

## Related products

- [Yablonsky SEO](https://yablonsky.io/seo/) — SEO, indexing and AI search discovery.
- [Yablonsky Security](https://yablonsky.io/) — authenticity, abuse prevention, and request protection.
- [Yablonsky AI Chatbots](https://yablonsky.io/ai-chatbots/) — AI customer assistance and business automation.

Each product maintains its own responsibilities and is designed to work independently.

## Versioning

YPerf uses **MAJOR.MINOR.PATCH**, with MINOR limited to 0–99. Corrective releases use PATCH; feature batches advance MINOR. The REST API, hooks, database schema, and stored-data contracts retain separate compatibility rules.

## License

GPL-2.0-or-later. See [GNU GPL v2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

---

Developed by **[Yablonsky.io](https://yablonsky.io/)** · Learn more about the [Yablonsky product ecosystem](https://yablonsky.io/).
