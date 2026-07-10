#!/usr/bin/env python3
"""
junit_to_sonar_generic.py

Converts a PHPUnit JUnit XML report (--log-junit) into SonarQube/SonarCloud's
Generic Test Execution report format, so `sonar.testExecutionReport.reportPaths`
can pick up test results.

Root cause
----------
SonarQube has no native PHPUnit/JUnit importer for PHP projects (unlike Java's
Surefire reports via sonar.junit.reportPaths). Test results must be converted
to Sonar's Generic Test Execution XML format:

    https://docs.sonarsource.com/sonarqube/latest/analyzing-source-code/test-coverage/generic-test-data/

What this script does
----------------------
1. Recursively collects every <testcase> element regardless of nesting depth
   (PHPUnit nests one <testsuite> per test class inside per-suite wrappers).
2. Groups testcases by their source file, made relative to --base (defaults to
   $GITHUB_WORKSPACE, then CWD) so SonarQube can match them against
   `sonar.tests`.
3. Maps <failure>/<error>/<skipped> children to Sonar's <failure>/<error>/
   <skipped>, converting PHPUnit's seconds-as-float `time` attribute into
   whole milliseconds for Sonar's `duration`.

Usage
-----
    python3 junit_to_sonar_generic.py execution.xml sonar-execution.xml
    python3 junit_to_sonar_generic.py execution.xml sonar-execution.xml --base /home/runner/work/pancake/pancake

In GitHub Actions:
    - name: Convert JUnit report for SonarCloud
      run: python3 .github/scripts/junit_to_sonar_generic.py execution.xml sonar-execution.xml
"""

import argparse
import os
import xml.etree.ElementTree as ET


def to_relative_path(file_path: str, base: str) -> str:
    path = file_path.replace("\\", "/")
    base = base.replace("\\", "/").rstrip("/")
    if base and path.startswith(base + "/"):
        path = path[len(base) + 1:]
    return path


def to_duration_ms(time_attr: str) -> str:
    try:
        return str(max(0, round(float(time_attr) * 1000)))
    except (TypeError, ValueError):
        return "0"


def summarize(text: str, type_attr: str) -> str:
    # PHPUnit's `type` attribute holds the exception class name (e.g. "RuntimeException"),
    # which makes a better short summary than the first line of `text` — PHPUnit repeats the
    # full test identifier there before the actual message.
    if type_attr:
        return type_attr
    text = (text or "").strip()
    return text.splitlines()[0][:500] if text else "failed"


def convert(input_path: str, output_path: str, base: str) -> int:
    root = ET.parse(input_path).getroot()

    files = {}
    order = []

    for testcase in root.iter("testcase"):
        file_attr = testcase.get("file")
        if not file_attr:
            continue
        rel_path = to_relative_path(file_attr, base)
        if rel_path not in files:
            files[rel_path] = []
            order.append(rel_path)
        files[rel_path].append(testcase)

    executions = ET.Element("testExecutions", {"version": "1"})
    test_count = 0

    for rel_path in order:
        file_el = ET.SubElement(executions, "file", {"path": rel_path})
        for testcase in files[rel_path]:
            test_count += 1
            case_el = ET.SubElement(file_el, "testCase", {
                "name": testcase.get("name", ""),
                "duration": to_duration_ms(testcase.get("time", "0")),
            })

            error = testcase.find("error")
            failure = testcase.find("failure")
            skipped = testcase.find("skipped")

            if error is not None:
                node = ET.SubElement(case_el, "error", {
                    "message": summarize(error.text, error.get("type")),
                })
                node.text = (error.text or "").strip()
            elif failure is not None:
                node = ET.SubElement(case_el, "failure", {
                    "message": summarize(failure.text, failure.get("type")),
                })
                node.text = (failure.text or "").strip()
            elif skipped is not None:
                ET.SubElement(case_el, "skipped", {"message": "skipped"})

    ET.indent(executions, space="  ")
    ET.ElementTree(executions).write(output_path, encoding="utf-8", xml_declaration=True)
    return test_count


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Convert a PHPUnit JUnit XML report into SonarQube's Generic Test Execution format."
    )
    parser.add_argument("input", help="Path to the PHPUnit JUnit XML report (--log-junit output).")
    parser.add_argument("output", help="Path to write the Sonar generic test execution report to.")
    parser.add_argument(
        "--base",
        default=os.environ.get("GITHUB_WORKSPACE", os.getcwd()),
        help="Absolute base directory stripped from each <testcase file=...> to produce a "
             "project-relative path (defaults to $GITHUB_WORKSPACE, then CWD).",
    )
    args = parser.parse_args()

    print(f"Converting: {args.input} -> {args.output} (base={args.base})")
    count = convert(args.input, args.output, args.base)
    print(f"Done. Converted {count} test case(s).")


if __name__ == "__main__":
    main()
