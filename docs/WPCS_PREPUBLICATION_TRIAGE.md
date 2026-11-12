# YPerf 2.56.0 — WPCS publication triage

**Purpose:** Separate WordPress.org publication findings from cosmetic coding-style observations without reopening feature-accepted functionality.

## Verified baseline

- Product: Yablonsky Performance 2.56.0.
- Site Runtime Acceptance (2026-11-11): **25 PASS / 0 FAIL / 3 SKIP / 11 ATTENTION**. This supports functional acceptance on the tested installation; it is not a claim that all environments or external providers were exercised.
- GitHub Actions PHP syntax: **7.4, 8.1, 8.3, 8.4 PASS**.
- JavaScript syntax: **PASS**.
- Official Plugin Check: previous checkout-root run completed with warnings about development files and manual textdomain loading. The new installation-package workflow separately checks the clean WordPress distribution tree.
- The previous WPCS job failed, but the displayed examples included **spaces instead of tabs, brace placement, missing documentation comments and class-file naming**. These findings alone do not demonstrate a broken plugin.

Evidence: [GitHub Actions run 43042449080](https://github.com/yablonsky-io/yablonsky-performance/actions/runs/43042449080).

## How to classify the WPCS report

| Finding | Recommended response |
| --- | --- |
| Confirmed vulnerability, missing authorization or unescaped unsafe output | Reproduce and tie to a specific WordPress.org rule. Propose a minimal patch with regression evidence and obtain approval if behavior can change |
| Distribution/package problem | Fix only package assembly or metadata, preserving runtime source |
| Missing i18n, incorrect text domain or materially incompatible WordPress API | Verify on the shipped release package and actual supported versions; propose narrow remediation |
| Tabs, spaces, alignment, brace placement, comment style | Document as coding-style debt. Avoid sweeping refactoring just to make the entire existing codebase pass a strict style preset |
| Class-file naming and namespace conventions | Confirm WordPress.org applicability to this namespaced, custom-loaded product before proposing renames; renames can break loading contracts |
| External services disclosure | Prefer accurate readme/terms/privacy documentation when the runtime integration itself is accepted |
| Ambiguous or tool-generated warning | Mark NEEDS_CONFIRMATION and check the actual behavior; do not auto-fix |

## Accepted scope

**Do not run phpcbf or another automatic formatting/migration tool across the existing plugin.** Do not change cache ownership, route policy, Redis, Cloudflare, WooCommerce, AI endpoints, migrations, localization routing or frontend scheduling to silence unrelated static-check messages.

A complete WPCS PASS for the legacy source requires either a separately approved code-style modernization or a carefully selected risk-focused sniff profile. **Do not claim PASS by hiding failures or silently weakening the official WordPress.org requirements.**

The actual **distribution archive** remains the authoritative artifact for the final WordPress.org review. A GitHub prerelease with a tested ZIP can be used for controlled distribution while outstanding publication-compliance issues remain visible.

See [WordPress.org pre-publication guidance](WORDPRESS_ORG_PREPUBLICATION_GUIDANCE.md).
