#!/usr/bin/env bash
# Bulk-file the review issue drafts in this directory as GitHub issues.
#
# Usage: ./file-issues.sh <owner/repo>
# Example: ./file-issues.sh mage-os-lab/module-seo
#
# Requires the GitHub CLI (gh) authenticated with write access to the target repo.

set -euo pipefail

repo="${1:?usage: $0 <owner/repo>}"
dir="$(cd "$(dirname "$0")" && pwd)"

for f in "$dir"/[0-9]*.md; do
    title="$(sed -n '1s/^Title: //p' "$f")"
    if [ -z "$title" ]; then
        echo "SKIP (no Title line): $f" >&2
        continue
    fi
    body="$(tail -n +3 "$f")"
    echo "Filing: $title"
    gh issue create --repo "$repo" --title "$title" --body "$body"
done
