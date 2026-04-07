#!/usr/bin/env python3
"""
sanitize_cobertura.py

Fixes PHPUnit-generated Cobertura XML so that qlty's strict parser
(qltysh/qlty-action/coverage@v2) can parse it without errors.

Root cause
----------
PHPUnit emits <classes/> (empty) blocks for packages that contain no
executable lines — interfaces, abstract classes, traits, enums with no
methods, and files excluded from source coverage. qlty's Rust/serde
deserialiser mandates at least one <class> child inside every <classes>
element, so any empty block causes:

    custom: missing field `class`

What this script does
---------------------
1. Removes <package> nodes whose <classes> block is absent or empty.
2. Removes <packages> if it ends up empty after step 1.
3. Preserves the XML declaration, DOCTYPE (if any), and all other
   attributes and nodes unchanged.
4. Writes the sanitized XML back to the same file (or a specified output).

Usage
-----
    python3 sanitize_cobertura.py coverage.xml
    python3 sanitize_cobertura.py coverage.xml --out coverage-clean.xml

In GitHub Actions:
    - name: Sanitize Cobertura XML for qlty
      run: python3 .github/scripts/sanitize_cobertura.py coverage.xml
"""

import argparse
import os
import sys
import tempfile
import xml.etree.ElementTree as ET


def sanitize(input_path: str, output_path: str) -> int:
    """
    Parse, sanitize, and re-write the Cobertura file.

    Writes to a temporary file in the same directory as output_path then
    performs an atomic os.replace() so the operation is safe even when
    input_path == output_path and the file is only user-writable.

    Returns the number of packages removed.
    """
    # Register a blank default namespace so ElementTree does not
    # inject ns0: prefixes on serialisation.
    ET.register_namespace("", "")

    try:
        tree = ET.parse(input_path)
    except ET.ParseError as exc:
        print(f"ERROR: Could not parse {input_path}: {exc}", file=sys.stderr)
        sys.exit(1)

    root = tree.getroot()
    removed = 0

    packages_el = root.find("packages")
    if packages_el is None:
        print("WARNING: <packages> element not found — nothing to sanitize.")
        return 0

    for pkg in list(packages_el):
        classes_el = pkg.find("classes")
        # Remove the package when <classes> is absent or has no <class> children
        if classes_el is None or len(classes_el) == 0:
            packages_el.remove(pkg)
            name = pkg.get("name", "<unnamed>")
            print(f"  Removed empty package: {name}")
            removed += 1

    # If <packages> itself is now empty, remove it to keep the document tidy
    if len(packages_el) == 0:
        root.remove(packages_el)
        print("  Removed empty <packages> element.")

    # Write to a sibling temp file, then atomically replace the target.
    # This avoids the PermissionError that occurs when ET.write() tries to
    # open the destination path directly while it is owned by a prior step,
    # and also avoids the binary-mode bug triggered by encoding="UTF-8"
    # (uppercase) on Python 3.8+.
    out_dir = os.path.dirname(os.path.abspath(output_path))
    tmp_fd, tmp_path = tempfile.mkstemp(dir=out_dir, suffix=".xml.tmp")
    try:
        with os.fdopen(tmp_fd, "w", encoding="utf-8") as fh:
            fh.write('<?xml version="1.0" encoding="utf-8"?>\n')
            fh.write(ET.tostring(root, encoding="unicode", short_empty_elements=True))
            fh.write("\n")
        os.replace(tmp_path, output_path)
    except Exception:
        # Clean up the temp file if anything went wrong after creation
        try:
            os.unlink(tmp_path)
        except OSError:
            pass
        raise

    return removed


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Sanitize a PHPUnit Cobertura XML file for qlty compatibility."
    )
    parser.add_argument("input", help="Path to the Cobertura XML file to sanitize.")
    parser.add_argument(
        "--out",
        default=None,
        help="Output path (defaults to overwriting the input file).",
    )
    args = parser.parse_args()

    output = args.out if args.out else args.input

    print(f"Sanitizing: {args.input}")
    removed = sanitize(args.input, output)
    print(f"Done. Removed {removed} empty package(s). Output: {output}")


if __name__ == "__main__":
    main()
