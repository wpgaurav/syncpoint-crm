#!/bin/bash
#
# SyncPoint CRM Build Script
# Generates a production-ready ZIP file for distribution
#
# Usage: ./build.sh [version]
# Example: ./build.sh 1.1.0
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Get version from argument or extract from plugin file
if [ -n "$1" ]; then
    VERSION="$1"
else
    VERSION=$(grep -m 1 "Version:" syncpoint-crm.php | awk '{print $3}')
fi

echo -e "${YELLOW}Building SyncPoint CRM v${VERSION}...${NC}"

# Create build directory
BUILD_DIR="$SCRIPT_DIR/build"
PLUGIN_DIR="$BUILD_DIR/syncpoint-crm"
ZIP_FILE="$SCRIPT_DIR/syncpoint-crm-${VERSION}.zip"

# Clean up previous build
rm -rf "$BUILD_DIR"
rm -f "$ZIP_FILE"

# Create fresh build directory
mkdir -p "$PLUGIN_DIR"

echo -e "${GREEN}✓${NC} Created build directory"

# Copy plugin files (excluding dev files)
rsync -av --progress . "$PLUGIN_DIR/" \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.gitignore' \
    --exclude='.gitattributes' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='tests' \
    --exclude='phpunit.xml' \
    --exclude='phpunit.xml.dist' \
    --exclude='phpcs.xml' \
    --exclude='.phpcs.xml' \
    --exclude='phpstan.neon' \
    --exclude='phpstan.neon.dist' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='.DS_Store' \
    --exclude='*.md' \
    --exclude='build' \
    --exclude='build.sh' \
    --exclude='.editorconfig' \
    --exclude='.eslintrc*' \
    --exclude='.prettierrc*' \
    --exclude='.stylelintrc*' \
    --exclude='Thumbs.db' \
    --exclude='*.log' \
    --exclude='*.sql' \
    --exclude='*.zip' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='docker-compose.yml' \
    --exclude='Dockerfile' \
    --exclude='Makefile' \
    --exclude='.vscode' \
    --exclude='.idea' \
    --quiet

echo -e "${GREEN}✓${NC} Copied plugin files"

# Keep readme.txt (WordPress plugin readme)
if [ -f "readme.txt" ]; then
    cp readme.txt "$PLUGIN_DIR/"
    echo -e "${GREEN}✓${NC} Included readme.txt"
fi

# Install composer dependencies (production only) if composer.json exists
if [ -f "composer.json" ]; then
    echo "Installing Composer dependencies..."
    cd "$PLUGIN_DIR"
    composer install --no-dev --optimize-autoloader --prefer-dist --quiet 2>/dev/null || true
    rm -f composer.json composer.lock
    cd "$SCRIPT_DIR"
    echo -e "${GREEN}✓${NC} Installed production dependencies"
fi

# Create ZIP archive
cd "$BUILD_DIR"
zip -r "$ZIP_FILE" syncpoint-crm -q

echo -e "${GREEN}✓${NC} Created ZIP archive"

# Clean up build directory
rm -rf "$BUILD_DIR"

# Show result
ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}Build complete!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  Version:  ${YELLOW}${VERSION}${NC}"
echo -e "  File:     ${YELLOW}syncpoint-crm-${VERSION}.zip${NC}"
echo -e "  Size:     ${YELLOW}${ZIP_SIZE}${NC}"
echo -e "  Location: ${YELLOW}${ZIP_FILE}${NC}"
echo ""
