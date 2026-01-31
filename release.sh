#!/bin/bash
set -e

# Release script for Enhanced Error Handler TYPO3 extension
# Usage: ./release.sh <version> [--push]
# Example: ./release.sh 1.1.0
#          ./release.sh 1.1.0 --push

VERSION=$1
PUSH=false

if [ -z "$VERSION" ]; then
    echo "Usage: ./release.sh <version> [--push]"
    echo "Example: ./release.sh 1.1.0"
    exit 1
fi

# Validate version format (semver)
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: Version must be in semver format (e.g., 1.0.0)"
    exit 1
fi

# Check for --push flag
if [ "$2" = "--push" ]; then
    PUSH=true
fi

# Check for uncommitted changes
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Error: You have uncommitted changes. Please commit or stash them first."
    exit 1
fi

# Check if tag already exists
if git rev-parse "v$VERSION" >/dev/null 2>&1; then
    echo "Error: Tag v$VERSION already exists"
    exit 1
fi

echo "Releasing version $VERSION..."

# Update version in ext_emconf.php
sed -i '' "s/'version' => '[^']*'/'version' => '$VERSION'/" ext_emconf.php

# Show the change
echo "Updated ext_emconf.php:"
grep "version" ext_emconf.php

# Commit the version bump
git add ext_emconf.php
git commit -m "Release version $VERSION"

# Create annotated tag
git tag -a "v$VERSION" -m "Release version $VERSION"

echo ""
echo "Release v$VERSION created successfully!"
echo ""

if [ "$PUSH" = true ]; then
    echo "Pushing to remote..."
    git push origin main
    git push origin "v$VERSION"
    echo "Pushed to remote."
else
    echo "To push the release, run:"
    echo "  git push origin main"
    echo "  git push origin v$VERSION"
    echo ""
    echo "Or use: ./release.sh $VERSION --push"
fi
