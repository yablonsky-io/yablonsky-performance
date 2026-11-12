# Changelog

This file tracks actual GitHub distribution packages separately from WordPress.org publication approval. A source version and a GitHub prerelease are not themselves WordPress.org acceptance.

## [Unreleased] — WordPress.org publication preparation

- Imported the existing, feature-accepted **2.56.0** workset into the GitHub repository.
- Added PHP syntax, JavaScript syntax, WordPress Coding Standards, and Plugin Check workflows for release preparation. Their presence is not proof of a successful release gate.
- Added advisory WordPress.org publication guidance and public-repository content safeguards.
- Completed the public GitHub project overview and support documentation.

**Runtime code is not changed by this documentation update.** The existing functional baseline is distributed as a GitHub prerelease, not represented as WordPress.org-approved.

## [2.56.0] — GitHub Release, 2026-11-12

The source baseline includes portable page caching, browser-cache policy, optional Redis object caching, frontend delivery and WooCommerce route awareness, Origin Survival, Runtime Acceptance, bounded prewarming, provider handover, local Autopilot, and optional AI assistance.

The [GitHub Release](https://github.com/yablonsky-io/yablonsky-performance/releases/tag/v2.56.0) now distributes the accepted 2.56.0 baseline as the WordPress-installable **yablonsky-performance-2.56.0.zip** (8,242,713 bytes; SHA-256: `cd3af61a96797934573a3f43c4ea860e9ad2a81c2a21d07b604dc514af9dd259`). The isolated package build, integrity checks, PHP syntax and official Plugin Check completed successfully. WPCS and WordPress.org publication acceptance are tracked separately. Functionality and boundaries remain defined by [readme.txt](readme.txt).

## Release-record policy

For every future approved external release, record:

- normalized product version, date, and published GitHub tag;
- exactly named installation ZIP, file integrity checksum and release asset;
- tested WordPress/PHP compatibility;
- evidence of WPCS, Plugin Check, security and runtime/regression acceptance;
- new features and user-visible fixes since the previous published release;
- migration/compatibility notices only if applicable.

Do not fabricate prior release history, claim a completed gate without evidence, or advertise unshipped roadmap capabilities.

See [release status](README.md#release-status) and [product versioning canon](YABLONSKY_PRODUCT_VERSIONING_CANON_v1.0.0_2026-09-21.md).
