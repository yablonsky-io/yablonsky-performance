#!/usr/bin/env python3
"""Build and verify the WordPress installation ZIP without modifying sources.

Usage: python3 scripts/build-release-package.py
The output is dist/yablonsky-performance-<version>.zip.
"""
from __future__ import annotations

import hashlib
import pathlib
import re
import sys
import zipfile

ROOT = pathlib.Path(__file__).resolve().parents[1]
ENTRY = ROOT / "yablonsky-performance.php"
README = ROOT / "readme.txt"
OUTPUT = ROOT / "dist"
PLUGIN = "yablonsky-performance"

# Explicit release allowlist, not a checkout-wide archive. Keep native runtime
# components, localization binaries and the host inspector that is offered
# directly by the plugin. Development docs, internal reports, GitHub files,
# test fixtures, and inherited source archives never enter the ZIP.
RELEASE_FILES = ("yablonsky-performance.php", "readme.txt", "changelog.txt", "LICENSE")
RELEASE_DIRS = ("assets", "includes", "runtime", "dropins", "host-adapters", "host-inspector", "languages")


def required_match(pattern: str, content: str, description: str) -> str:
    found = re.search(pattern, content, re.MULTILINE)
    if not found:
        raise RuntimeError(f"Missing {description}")
    return found.group(1)


def build() -> pathlib.Path:
    entry_text = ENTRY.read_text(encoding="utf-8")
    readme_text = README.read_text(encoding="utf-8")
    version = required_match(r"(?m)^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$", entry_text, "plugin header version")
    runtime = required_match(r"define\(\s*'YPERF_VERSION'\s*,\s*'(\d+\.\d+\.\d+)'\s*\)", entry_text, "runtime version")
    stable = required_match(r"^Stable tag:\s*(\d+\.\d+\.\d+)\s*$", readme_text, "WordPress stable tag")
    if not (version == runtime == stable):
        raise RuntimeError("Plugin header, runtime version and readme Stable tag disagree")
    major, minor, patch = map(int, version.split("."))
    if minor >= 100:
        raise RuntimeError("Product version must use MINOR 0–99")
    if any(n < 0 for n in (major, minor, patch)):
        raise RuntimeError("Invalid version")

    files = []
    for filename in RELEASE_FILES:
        path = ROOT / filename
        if not path.is_file() or path.is_symlink():
            raise RuntimeError(f"Required release file missing or invalid: {filename}")
        files.append(path)
    for dirname in RELEASE_DIRS:
        directory = ROOT / dirname
        if not directory.is_dir() or directory.is_symlink():
            raise RuntimeError(f"Required runtime directory missing: {dirname}")
        for path in sorted(directory.rglob("*")):
            if path.is_symlink():
                raise RuntimeError(f"Symlinks are not accepted in release package: {path}")
            if path.is_file():
                if path.name.startswith(".") or "__pycache__" in path.parts:
                    raise RuntimeError(f"Unexpected development file within release directory: {path}")
                files.append(path)
    if len(files) != len(set(files)):
        raise RuntimeError("Duplicate release files")
    if not any("languages" in f.parts and f.suffix == ".mo" for f in files):
        raise RuntimeError("No WordPress localization binaries were staged")
    OUTPUT.mkdir(exist_ok=True)
    destination = OUTPUT / f"{PLUGIN}-{version}.zip"
    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(files, key=lambda x: x.as_posix()):
            member = f"{PLUGIN}/{path.relative_to(ROOT).as_posix()}"
            archive.write(path, member)

    with zipfile.ZipFile(destination) as archive:
        names = archive.namelist()
        if archive.testzip() is not None:
            raise RuntimeError("ZIP integrity test failed")
        if len(names) != len(files) or len(names) != len(set(names)):
            raise RuntimeError("ZIP members are incomplete or duplicated")
        if f"{PLUGIN}/yablonsky-performance.php" not in names:
            raise RuntimeError("ZIP plugin entrypoint missing")
        for name in names:
            parts = pathlib.PurePosixPath(name).parts
            if len(parts) < 2 or parts[0] != PLUGIN or ".." in parts:
                raise RuntimeError(f"Invalid installation ZIP member: {name}")
        for disallowed in ("AGENTS.md", "README.md", "CONTRIBUTING.md", "SECURITY.md", "SUPPORT.md", "SHA256SUMS.txt"):
            if f"{PLUGIN}/{disallowed}" in names:
                raise RuntimeError(f"Development-only artifact included: {disallowed}")
    digest = hashlib.sha256(destination.read_bytes()).hexdigest()
    print(f"PACKAGE={destination}")
    print(f"VERSION={version}")
    print(f"FILES={len(files)}")
    print(f"BYTES={destination.stat().st_size}")
    print(f"SHA256={digest}")
    return destination


if __name__ == "__main__":
    try:
        build()
    except Exception as error:
        print(f"RELEASE PACKAGE FAIL: {error}", file=sys.stderr)
        sys.exit(1)
