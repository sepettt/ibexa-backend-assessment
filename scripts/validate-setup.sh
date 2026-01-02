#!/usr/bin/env bash

# DDEV Setup Validation Script
# This script validates that DDEV and all services are properly configured

set -e

echo "======================================"
echo "DDEV Setup Validation"
echo "======================================"
echo ""

# Check if DDEV is installed
echo "✓ Checking DDEV installation..."
if ! command -v ddev &> /dev/null; then
    echo "✗ DDEV is not installed. Please install DDEV first."
    echo "  Visit: https://ddev.readthedocs.io/en/stable/#installation"
    exit 1
fi
echo "  DDEV version: $(ddev version | head -n 1)"
echo ""

# Check if we're in a DDEV project
echo "✓ Checking DDEV project..."
if [ ! -f .ddev/config.yaml ]; then
    echo "✗ Not in a DDEV project directory"
    exit 1
fi
echo "  DDEV project detected"
echo ""

# Check DDEV status
echo "✓ Checking DDEV services..."
if ! ddev describe > /dev/null 2>&1; then
    echo "✗ DDEV services not running. Run 'ddev start' first."
    exit 1
fi
echo "  DDEV services are running"
echo ""

# Check PHP version
echo "✓ Checking PHP version..."
PHP_VERSION=$(ddev exec php -v | head -n 1)
echo "  $PHP_VERSION"
echo ""

# Check Composer
echo "✓ Checking Composer..."
COMPOSER_VERSION=$(ddev exec composer --version)
echo "  $COMPOSER_VERSION"
echo ""

# Check database
echo "✓ Checking database connection..."
if ddev exec mysql -u db -pdb db -e "SELECT 1" > /dev/null 2>&1; then
    echo "  Database connection successful"
else
    echo "✗ Database connection failed"
    exit 1
fi
echo ""

# Check Solr
echo "✓ Checking Solr..."
if ddev exec curl -s http://solr:8983/solr/admin/ping > /dev/null 2>&1; then
    echo "  Solr is running and responsive"
else
    echo "⚠  Solr may not be running properly"
fi
echo ""

# Check Mailhog
echo "✓ Checking Mailhog..."
if ddev exec curl -s http://mailhog:8025 > /dev/null 2>&1; then
    echo "  Mailhog is running"
else
    echo "⚠  Mailhog may not be running properly"
fi
echo ""

# Check vendor directory
echo "✓ Checking dependencies..."
if [ -d vendor ]; then
    echo "  Vendor directory exists"
else
    echo "⚠  Vendor directory not found. Run 'ddev composer install'"
fi
echo ""

# Display service URLs
echo "======================================"
echo "Service URLs"
echo "======================================"
ddev describe | grep -A 20 "URLs"
echo ""

echo "======================================"
echo "✓ Validation Complete!"
echo "======================================"
echo ""
echo "Next steps:"
echo "  1. ddev composer install    # Install dependencies"
echo "  2. ddev composer bootstrap  # Set up database and fixtures"
echo "  3. Visit the URLs above to access your site"
echo ""
