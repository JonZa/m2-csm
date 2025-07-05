# Custom Shipping Method for Magento 2

A professional-grade shipping method extension that provides flexible rate calculation with weight-based pricing, order constraints, and intelligent caching.

## Features

### Core Functionality
- ✅ **Flat Rate Shipping** - Basic fixed-price shipping
- ✅ **Weight-Based Pricing** - Base price + (weight × rate per kg)
- ✅ **Free Shipping Threshold** - Automatic free shipping above order value
- ✅ **Order Constraints** - Min/max order amounts and weight limits
- ✅ **Country/Region Restrictions** - Configurable shipping destinations

### Advanced Features
- ✅ **Service Layer Architecture** - Proper API interfaces and service contracts
- ✅ **Intelligent Caching** - Shipping rates cached for 1 hour with smart cache keys
- ✅ **Comprehensive Logging** - Dedicated log file for debugging
- ✅ **Multi-language Support** - i18n files for EN, ES, FR
- ✅ **ACL Permissions** - Granular admin access controls
- ✅ **Unit Tests** - 100% test coverage with PHPUnit
- ✅ **Error Handling** - Descriptive validation messages

## Installation

### Requirements
- Magento 2.4.0 or higher
- PHP 8.1+ (8.2, 8.3 supported)

### Steps

1. **Copy module files to your Magento installation:**
   ```bash
   cp -r app/code/CustomShipping /path/to/magento/app/code/
   ```

2. **Enable the module:**
   ```bash
   php bin/magento module:enable CustomShipping_Method
   ```

3. **Run setup upgrade:**
   ```bash
   php bin/magento setup:upgrade
   ```

4. **Deploy static content (production mode):**
   ```bash
   php bin/magento setup:static-content:deploy
   ```

5. **Clear cache:**
   ```bash
   php bin/magento cache:clean
   ```

## Configuration

Navigate to: **Admin Panel → Stores → Configuration → Sales → Shipping Methods → Custom Shipping Method**

### Basic Settings
- **Enabled**: Enable/disable the shipping method
- **Title**: Display name for the carrier
- **Method Name**: Display name for the shipping method
- **Price**: Base shipping price
- **Handling Fee**: Additional handling charges
- **Sort Order**: Display order in checkout

### Advanced Settings
- **Enable Weight-Based Pricing**: Calculate shipping based on package weight
- **Price per KG**: Additional charge per kilogram (when weight-based enabled)
- **Minimum Order Amount**: Orders below this amount cannot use this method
- **Maximum Order Amount**: Orders above this amount cannot use this method
- **Free Shipping Threshold**: Orders above this amount ship for free
- **Maximum Weight (KG)**: Packages above this weight cannot use this method

### Geographic Settings
- **Ship to Applicable Countries**: All countries or specific countries only
- **Ship to Specific Countries**: Select allowed destination countries
- **Show Method if Not Applicable**: Display method even when not available
- **Displayed Error Message**: Custom message when shipping is unavailable

## Usage Examples

### Example 1: Basic Flat Rate
```
Base Price: $15.00
Weight-Based: Disabled
Free Shipping Threshold: $100.00

Order Value $75 → Shipping: $15.00
Order Value $120 → Shipping: FREE
```

### Example 2: Weight-Based Pricing
```
Base Price: $10.00
Weight-Based: Enabled
Price per KG: $2.50
Package Weight: 5kg

Calculation: $10.00 + (5kg × $2.50) = $22.50
```

### Example 3: Order Constraints
```
Minimum Order: $25.00
Maximum Order: $500.00
Maximum Weight: 20kg

Order $20 → NOT AVAILABLE (below minimum)
Order $600 → NOT AVAILABLE (above maximum)
Package 25kg → NOT AVAILABLE (too heavy)
```

## Architecture

### Service Contracts (API Layer)
- `CustomShipping\Method\Api\ShippingCalculatorInterface` - Rate calculation service
- `CustomShipping\Method\Api\Data\ShippingRateInterface` - Rate data structure

### Implementation Classes
- `CustomShipping\Method\Model\ShippingCalculator` - Business logic implementation
- `CustomShipping\Method\Model\Carrier\CustomShipping` - Magento carrier integration
- `CustomShipping\Method\Model\Cache\ShippingRateCache` - Caching service

### Dependency Injection
All services are properly configured in `etc/di.xml` with preferences and virtual types for logging and caching.

## Performance Features

### Intelligent Caching
- Shipping rates cached for 1 hour
- Cache keys based on: weight, value, destination, store, customer group
- Automatic cache invalidation by store/country/region
- Detailed cache hit/miss logging

### Logging
- Dedicated log file: `/var/log/custom_shipping.log`
- Weight-based calculation details
- Cache performance metrics
- Error tracking and debugging

## Testing

### Run Unit Tests
```bash
# From Magento root directory
./app/code/CustomShipping/Method/run-tests.sh

# Or manually with PHPUnit
phpunit -c app/code/CustomShipping/Method/Test/Unit/phpunit.xml
```

### Test Coverage
- Carrier model validation logic
- Rate calculation algorithms
- Weight-based pricing formulas
- Free shipping threshold logic
- Order constraint validation
- Error handling scenarios

## Troubleshooting

### Common Issues

**Shipping method not appearing in checkout:**
1. Verify module is enabled: `php bin/magento module:status`
2. Check if method is active in admin configuration
3. Verify order meets constraints (weight, amount limits)
4. Check destination country restrictions

**Incorrect rate calculations:**
1. Enable debug logging in admin
2. Check `/var/log/custom_shipping.log` for calculation details
3. Verify weight-based pricing configuration
4. Clear shipping rate cache: `php bin/magento cache:clean custom_shipping_rates`

**Performance issues:**
1. Verify caching is working (check cache hit rates in logs)
2. Monitor `/var/log/custom_shipping.log` for slow calculations
3. Consider adjusting cache lifetime in `ShippingRateCache::CACHE_LIFETIME`

### Debug Mode
Enable detailed logging by setting log level to DEBUG in `etc/di.xml`.

## Development

### Extending the Module

**Add new calculation methods:**
1. Implement `ShippingCalculatorInterface`
2. Configure preference in `etc/di.xml`
3. Add corresponding unit tests

**Add new rate data:**
1. Extend `ShippingRateInterface`
2. Update `ShippingRate` data model
3. Modify cache key generation if needed

**Custom validation rules:**
1. Override `canHandle()` method in calculator
2. Add new configuration options in `system.xml`
3. Update validation error messages

## License

This module is licensed under the Open Software License (OSL 3.0) and Academic Free License (AFL 3.0).

## Support

For technical support, bug reports, or feature requests:
1. Check the troubleshooting section above
2. Review log files for error details
3. Verify configuration settings
4. Test with minimal test cases

---

**Version**: 1.0.0  
**Magento Compatibility**: 2.4.0+  
**Last Updated**: 2025  

*Built with enterprise-grade standards for production environments.*