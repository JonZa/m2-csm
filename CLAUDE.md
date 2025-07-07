# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 7 Claude Rules for Collaboration

1. **First think through the problem**, read the codebase for relevant files, and write a plan in todo.md.
2. **The plan should have a list of todo items** that you can check off as you complete them
3. **Before you begin working, check in with me** and I will verify the plan.
4. **Then, begin working on the todo items**, marking them as complete as you go.
5. **Please provide a high-level explanation at each step** of what changes you made
6. **Keep each change minimal** to reduce risk and maintain simplicity.
7. **Finally, add a review section to the todo.md file** with a summary of the changes you made and any other relevant information.

## Repository Overview

This is a **Magento 2 shipping extension** called CustomShipping Method that provides intelligent, configurable shipping calculations beyond basic flat rates. It's designed as a production-ready shipping solution with enterprise-level features.

## Development Commands

### Testing
```bash
# Run unit tests
cd app/code/CustomShipping/Method/Test/Unit && phpunit --configuration phpunit.xml

# Run tests with coverage
cd app/code/CustomShipping/Method/Test/Unit && phpunit --configuration phpunit.xml --coverage-text

# Quick test runner script
./run-tests.sh
```

### Magento Development
```bash
# Clear cache (required after configuration changes)
php bin/magento cache:flush

# Recompile dependency injection (required after adding new services)
php bin/magento setup:di:compile

# Deploy static content (for production)
php bin/magento setup:static-content:deploy

# Enable module (if disabled)
php bin/magento module:enable CustomShipping_Method

# Check module status
php bin/magento module:status CustomShipping_Method
```

## Architecture

### Core Components

1. **Main Carrier Class**: `Model/Carrier/CustomShipping.php`
   - Implements `\Magento\Shipping\Model\Carrier\AbstractCarrier`
   - Entry point for all shipping calculations
   - Handles rate collection and validation

2. **Service Contracts**: `Api/` directory
   - `ShippingCalculatorInterface.php` - Core calculation contract
   - `Data/ShippingRateInterface.php` - Rate data model contract
   - Defines public API for extensibility

3. **Business Logic**: `Model/ShippingCalculator.php`
   - Implements pricing models (flat rate, weight-based, free shipping)
   - Validates business rules (order limits, weight restrictions)
   - Handles all shipping calculation logic

4. **Caching Layer**: `Model/Cache/ShippingRateCache.php`
   - Optimizes performance for repeated calculations
   - Uses Magento's cache framework

### Configuration System

- **Admin Configuration**: `etc/adminhtml/system.xml`
- **Default Values**: `etc/config.xml`
- **Dependency Injection**: `etc/di.xml`
- **Module Declaration**: `etc/module.xml`

### Key Design Patterns

- **Service-Oriented Architecture**: Business logic separated into services
- **Dependency Injection**: All dependencies injected via constructor
- **Interface Segregation**: Service contracts define clear APIs
- **Caching Strategy**: Rate caching to improve performance

## File Structure

```
app/code/CustomShipping/Method/
├── Api/                     # Service contracts
├── Model/                   # Business logic
│   ├── Cache/              # Caching implementation
│   ├── Carrier/            # Main carrier class
│   └── Data/               # Data models
├── Test/Unit/              # Unit tests
├── etc/                    # Configuration
├── i18n/                   # Translations (EN, ES, FR)
└── Setup/Patch/Data/       # Installation patches
```

## Development Guidelines

### Adding New Features

1. **Service Contracts First**: Define interfaces in `Api/` directory
2. **Implementation**: Add business logic in `Model/` directory
3. **Dependency Injection**: Register services in `etc/di.xml`
4. **Testing**: Add unit tests in `Test/Unit/`
5. **Configuration**: Update `etc/adminhtml/system.xml` for admin options

### Important Testing Notes

- **Cache Service Tests**: The `ShippingRateCache` class now uses constructor injection with:
  - `Magento\Framework\App\Cache\Frontend\Pool`
  - `Magento\Framework\Serialize\SerializerInterface`
  - `Psr\Log\LoggerInterface`
  - `Magento\Framework\App\Config\ScopeConfigInterface`
- **Method Names**: Cache service uses `getCachedRate()` and `saveRate()` methods (not `load()` and `save()`)
- **Return Types**: `getCachedRate()` returns `?float`, not a ShippingRateInterface object

### Configuration Changes

After modifying configuration files:
```bash
php bin/magento cache:flush
php bin/magento setup:di:compile
```

### Testing Requirements

- All new business logic must have unit tests
- Test configuration: `Test/Unit/phpunit.xml`
- Use Magento's testing framework and mocking utilities
- Focus on testing business rules and edge cases

### Error Handling

- All errors logged to `/var/log/custom_shipping.log`
- Use Magento's logger interface: `\Psr\Log\LoggerInterface`
- Provide meaningful error messages for administrators
- Handle exceptions gracefully in rate calculations

### Module Configuration Notes

- **Default State**: Module is disabled by default (`active` = 0 in `etc/config.xml`)
- **ACL Resources**: Properly defined in `etc/acl.xml` as `CustomShipping_Method::config`
- **Cache Lifetime**: Configurable via admin panel (default: 3600 seconds/1 hour)

## Common Development Tasks

### Modifying Shipping Logic
- Edit `Model/ShippingCalculator.php`
- Update corresponding unit tests
- Clear cache after changes

### Adding Configuration Options
- Update `etc/adminhtml/system.xml` for admin UI
- Add default values in `etc/config.xml`
- Access via `\Magento\Framework\App\Config\ScopeConfigInterface`

### Performance Optimization
- Leverage `ShippingRateCache` for expensive calculations
- Use lazy loading for heavy dependencies
- Profile with Magento's built-in profiler

## Platform Requirements

- **Magento**: 2.4.0+
- **PHP**: 8.1, 8.2, or 8.3
- **Framework**: Standard Magento 2 module structure
- **Testing**: PHPUnit for unit tests