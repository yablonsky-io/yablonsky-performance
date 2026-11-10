# Yablonsky Product Versioning Canon v1.0.0

All Yablonsky.io product lines use MAJOR.MINOR.PATCH.

- MINOR is between 0 and 99.
- Before any external candidate or handoff, normalize major/minor with `major += floor(minor / 100)` and `minor = minor % 100`. PATCH remains unchanged.
- PATCH is for corrective releases on the same feature surface.
- New feature batches advance MINOR.
- A major rollover caused only by MINOR reaching 100 does not necessarily indicate a breaking API change.
- REST /v1 endpoints, versioned hooks, schema contracts and stored-data contracts maintain independent compatibility rules.
- External release candidates fail if plugin headers, Stable tag, filenames, release notes or handoff use MINOR >= 100 or inconsistent normalized versions.

Example: `5.100.0` becomes `6.0.0`; `5.115.2` becomes `6.15.2`.
