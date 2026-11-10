#!/usr/bin/env python3
"""Read-only release metadata and localization inventory gate, stdlib only."""
import json
import pathlib
import re
import sys

root = pathlib.Path(__file__).resolve().parents[1]
entry = (root / "yablonsky-performance.php").read_text(encoding="utf-8")
readme = (root / "readme.txt").read_text(encoding="utf-8")
manifest = json.loads((root / "languages" / "manifest.json").read_text(encoding="utf-8"))

def required(pattern, document, name):
    found = re.search(pattern, document, re.MULTILINE)
    if not found:
        raise AssertionError(f"Missing {name}")
    return found.group(1)

version = required(r"^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$", entry, "plugin version")
constant = required(r"define\('YPERF_VERSION',\s*'(\d+\.\d+\.\d+)'\)", entry, "runtime version")
stable = required(r"^Stable tag:\s*(\d+\.\d+\.\d+)\s*$", readme, "stable tag")
if len({version, constant, stable, str(manifest.get("version", ""))}) != 1:
    raise AssertionError("Entrypoint, runtime, readme and localization manifest versions differ")
major, minor, patch = map(int, version.split("."))
if minor >= 100 or major < 0 or patch < 0:
    raise AssertionError("Unnormalized product version")

scope = manifest.get("language_scope", {})
packs = manifest.get("packs", [])
if scope.get("languages") != 36 or scope.get("locale_variants") != 41 or len(packs) != 36:
    raise AssertionError("36 language families / 41 locale variants contract failed")
expected_keys = int(manifest.get("current_gettext_keys", 0))
if expected_keys != 2206:
    raise AssertionError("Unexpected gettext key inventory: review before accepting changes")

seen = set()
for pack in packs:
    for audit in pack.get("locale_audit", []):
        locale = audit.get("locale", "")
        if locale in seen:
            raise AssertionError(f"Duplicate locale: {locale}")
        seen.add(locale)
        if int(audit.get("total", 0)) != expected_keys:
            raise AssertionError(f"Stale translation inventory: {locale}")
        if int(audit.get("filled", 0)) != expected_keys or int(audit.get("fuzzy", 0)) != 0:
            raise AssertionError(f"Manifest claims untranslated or fuzzy entries: {locale}")
        if audit.get("catalog_exists", False):
            for ext in ("po", "mo"):
                name = root / "languages" / f"yablonsky-performance-{locale}.{ext}"
                if not name.is_file() or name.stat().st_size == 0:
                    raise AssertionError(f"Missing or empty localization catalog: {name.name}")
        elif not audit.get("source_reference", False):
            raise AssertionError(f"Missing catalog without source reference: {locale}")

if len(seen) != 41:
    raise AssertionError(f"Locale inventory differs: {len(seen)}")
pending = sum(pack.get("editorial_acceptance") == "PENDING" for pack in packs)
print(f"YPerf release metadata inventory: PASS version={version}; "
      f"families=36; locales=41; keys={expected_keys}; editorial_pending={pending}")
if pending:
    print("NOTICE: Full source fill is not editorial/visual localization acceptance.", file=sys.stderr)
if "--release" in sys.argv:
    incomplete = [
        pack.get("language", "?") for pack in packs
        if pack.get("status") != "source"
        and (
            pack.get("editorial_acceptance") != "ACCEPTED"
            or pack.get("wordpress_visual_acceptance") != "ACCEPTED"
            or pack.get("rtl_visual_acceptance", "ACCEPTED") != "ACCEPTED"
        )
    ]
    if incomplete:
        raise AssertionError(
            "External release blocked: editorial/WordPress/RTL acceptance pending for "
            + ", ".join(incomplete)
        )
