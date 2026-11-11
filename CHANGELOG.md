# Changelog

This file tracks **published distribution releases** separately from development work. A version string in source code is not itself proof that a distributable artifact has passed acceptance.

## [Unreleased] — release preparation

- Imported the existing, feature-accepted **2.56.0** workset into the GitHub repository.
- Added PHP syntax, JavaScript syntax, WordPress Coding Standards, and Plugin Check workflows for release preparation. Their presence is not proof of a successful release gate.
- Added advisory WordPress.org publication guidance and public-repository content safeguards.
- Completed the public GitHub project overview and support documentation.

**Runtime code is not changed by this documentation update.** No production installation ZIP is declared here as released.

## 2.56.0 — source baseline (not a GitHub release)

The source baseline includes portable page caching, browser-cache policy, optional Redis object caching, frontend delivery and WooCommerce route awareness, Origin Survival, Runtime Acceptance, bounded prewarming, provider handover, local Autopilot, and optional AI assistance.

This entry is a **source baseline record**, not a verified public release announcement. The authoritative behavior and boundaries are described in [readme.txt](readme.txt).

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
