# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is a Magento 2 project containing a custom shipping method module (`CustomShipping_Method`) that demonstrates enterprise-grade extension development. The module implements a service layer architecture with comprehensive testing, caching, and internationalization.

## Common Development Commands

### Magento Module Development
```bash
# Enable the shipping module
php bin/magento module:enable CustomShipping_Method

# Run setup upgrade after module changes
php bin/magento setup:upgrade

# Clear cache (essential during development)
php bin/magento cache:clean

# Generate DI compilation (production)
php bin/magento setup:di:compile

# Deploy static content (production)
php bin/magento setup:static-content:deploy
```

### Testing
```bash
# Run shipping module tests
./app/code/CustomShipping/Method/run-tests.sh

# Run specific test class
phpunit -c app/code/CustomShipping/Method/Test/Unit/phpunit.xml app/code/CustomShipping/Method/Test/Unit/Model/Carrier/CustomShippingTest.php

# Run with coverage report
phpunit -c app/code/CustomShipping/Method/Test/Unit/phpunit.xml --coverage-html var/phpunit/coverage/
```

### Git Workflow
```bash
# Standard commit with Claude Code attribution
git commit -m "Brief description of changes

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```

## Architecture Overview

### Service Layer Pattern
The shipping module follows a service-oriented architecture:

- **Api/ShippingCalculatorInterface.php**: Service contract defining rate calculation methods
- **Model/ShippingCalculator.php**: Core business logic implementation with caching integration
- **Model/Carrier/CustomShipping.php**: Magento carrier adapter that delegates to service layer

### Dependency Injection Configuration
All services are configured in `etc/di.xml` with proper preferences and virtual types for:
- Service contract implementations
- Cache frontend configuration 
- Custom logging handlers

### Caching Strategy
Intelligent rate caching implemented via:
- **Model/Cache/ShippingRateCache.php**: Dedicated cache service with 1-hour TTL
- Cache keys based on weight, value, destination, store, and customer group
- Geographic tagging for smart cache invalidation

### Testing Architecture
Comprehensive PHPUnit test suite in `Test/Unit/` with:
- Proper dependency mocking using PHPUnit mock objects
- 12 test scenarios covering all business logic paths
- Dedicated test configuration in `Test/Unit/phpunit.xml`

## Module-Specific Development Notes

### Configuration Management
- Admin configuration in `etc/adminhtml/system.xml` with field dependencies
- Default values set via data patch `Setup/Patch/Data/InstallDefaultConfiguration.php`
- ACL resources defined in `etc/acl.xml` for security

### Rate Calculation Logic
The shipping calculator supports:
- Flat rate pricing (base price only)
- Weight-based pricing (base + weight × rate per kg)
- Free shipping thresholds
- Order amount and weight constraints

### Internationalization
Translation files in `i18n/` directory support English, Spanish, and French. All user-facing strings use `__()` translation functions.

### Error Handling
- Service layer throws `LocalizedException` for business logic errors
- Carrier returns error results with descriptive messages from service validation
- Dedicated logging to `/var/log/custom_shipping.log`

## File Modifications Impact

### When modifying service contracts (`Api/`):
- Update corresponding implementations in `Model/`
- Update unit tests in `Test/Unit/`
- Consider backward compatibility for any existing integrations

### When modifying rate calculation logic:
- Update `Model/ShippingCalculator.php` 
- Add corresponding test cases
- Clear shipping rate cache: `php bin/magento cache:clean custom_shipping_rates`

### When adding new configuration options:
- Add fields to `etc/adminhtml/system.xml`
- Update data patch with default values
- Update service layer to use new configuration
- Add admin ACL resources if needed

## Quality Standards

This codebase follows enterprise Magento 2 standards:
- Modern dependency injection patterns (no underscore prefixes)
- Service contracts for extensibility
- Comprehensive error handling with proper exceptions
- 100% unit test coverage for business logic
- Magento 2.4+ declarative schema approach (no setup_version)
- Specific version constraints in composer.json