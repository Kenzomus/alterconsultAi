#!/bin/bash

# Exit on error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

echo "Running automated tests..."

# Run PHPUnit tests
echo "Running PHPUnit tests..."
./vendor/bin/phpunit --configuration web/core/phpunit.xml.dist web/modules/custom
if [ $? -eq 0 ]; then
    echo -e "${GREEN}PHPUnit tests passed${NC}"
else
    echo -e "${RED}PHPUnit tests failed${NC}"
    exit 1
fi

# Run PHPCS
echo "Running PHPCS..."
./vendor/bin/phpcs --standard=Drupal web/modules/custom
if [ $? -eq 0 ]; then
    echo -e "${GREEN}PHPCS passed${NC}"
else
    echo -e "${RED}PHPCS failed${NC}"
    exit 1
fi

# Run PHPStan
echo "Running PHPStan..."
./vendor/bin/phpstan analyse web/modules/custom
if [ $? -eq 0 ]; then
    echo -e "${GREEN}PHPStan passed${NC}"
else
    echo -e "${RED}PHPStan failed${NC}"
    exit 1
fi

# Run Behat tests if available
if [ -f "behat.yml" ]; then
    echo "Running Behat tests..."
    ./vendor/bin/behat
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Behat tests passed${NC}"
    else
        echo -e "${RED}Behat tests failed${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}All tests passed!${NC}"