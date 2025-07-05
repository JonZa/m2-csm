#!/bin/bash

# Custom Shipping Method Test Runner
# Run this from the Magento root directory

echo "Running CustomShipping_Method Unit Tests..."
echo "=========================================="

# Check if PHPUnit is available
if ! command -v phpunit &> /dev/null; then
    echo "PHPUnit not found. Please install PHPUnit or use Magento's vendor/bin/phpunit"
    exit 1
fi

# Set the test directory
TEST_DIR="app/code/CustomShipping/Method/Test/Unit"

# Run the tests with coverage
echo "Running tests with coverage report..."
phpunit -c $TEST_DIR/phpunit.xml $TEST_DIR/

echo ""
echo "Tests completed!"
echo "Coverage report generated in var/phpunit/coverage/CustomShipping_Method/"
echo "JUnit XML report generated in var/phpunit/CustomShipping_Method_junit.xml"