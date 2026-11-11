# GitHub repository profile — Yablonsky Performance

This file records the agreed public GitHub repository profile and release-presentation copy. **It does not automatically change GitHub Settings.** Keep the actual GitHub About fields aligned when repository-administration access allows it.

## About fields

**Description** (paste exactly):

> Local-first WordPress performance plugin: page cache, Redis, WooCommerce optimization, origin protection, Cloudflare integration and measurable results.

**Website:**

https://yablonsky.io/performance/

**Topics** (GitHub topic slugs, one per entry):

```text
wordpress
wordpress-plugin
performance
caching
page-cache
redis
object-cache
woocommerce
cloudflare
web-performance
site-speed
optimization
```

These topics describe actual product surfaces. Avoid unrelated tags, unsupported performance promises and vendor-specific buzzwords.

## Public project identity

- **Repository:** `yablonsky-io/yablonsky-performance`
- **Display name:** Yablonsky Performance
- **Short name:** YPerf
- **WordPress text domain:** `yablonsky-performance`
- **License:** GPL-2.0-or-later
- **Version baseline:** 2.56.0 (source only; not an approved GitHub Release)
- **Language for public GitHub prose:** English, except explicit founder-approved exceptions
- **Source development:** native-first and container-independent; no mandatory Docker
- **Product identity:** performance, efficiency, caching and origin capacity—not YSEO indexing or YSec security rules

## Release presentation — not yet published

**Release title (once verified):** `Yablonsky Performance 2.56.0`

**Tag:** `v2.56.0`

**Installable asset:** `yablonsky-performance-2.56.0.zip`

**Release notes:** use [CHANGELOG.md](../CHANGELOG.md) for the source baseline, then publish accurate tested compatibility and proof of the release gate. Do not assert that ZIP or tag exists until actually published.

**Release must not be published** merely to fill the Releases panel. First verify the complete intended distribution (including PHP compatibility, WPCS, official Plugin Check, installer behavior, required translations and accepted runtime regressions). A GitHub automatic source archive is not an installation artifact.

## Repository housekeeping

- README gives a clear purpose, feature scope, links, and honest release status.
- `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, `SUPPORT.md` and `CHANGELOG.md` provide standard project entry points.
- `AGENTS.md` references the advisory WordPress.org guidance and the public repository content policy.
- Keep private internal screenshots, customer data, secrets, detailed handoff archives and local diagnostics out of public history.
- Avoid speculative refactoring or functionality changes solely to polish the GitHub presentation.
