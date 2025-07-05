# CustomShipping Method - Professional Shipping Solution

A robust, production-ready shipping method for Magento 2 that goes beyond basic flat rates to provide intelligent, configurable shipping calculations.

## What This Module Does

Stop using basic shipping methods that charge everyone the same rate regardless of weight, destination, or order value. This module provides:

### Smart Pricing Options
- **Flat Rate**: Traditional fixed shipping cost
- **Weight-Based**: Base price + additional charge per kilogram  
- **Free Shipping Threshold**: Automatic free shipping above specified order values
- **Order Constraints**: Set minimum/maximum order amounts for eligibility

### Business Rules You Can Actually Use
- Block heavy packages that exceed your handling capacity
- Require minimum order amounts for this shipping method
- Offer free shipping incentives to increase average order value
- Set maximum order limits for special handling requirements

### Professional Features
- Geographic restrictions (specific countries/regions)
- Custom error messages for better customer communication
- Admin permission controls
- Multi-language support (English, Spanish, French)
- Performance optimization with intelligent caching

## Real-World Examples

### Example 1: E-commerce Store with Weight-Based Pricing
**Configuration:**
- Base shipping: $8.00
- Weight-based pricing: Enabled
- Additional cost: $1.50 per kg
- Free shipping: Orders over $75

**Results:**
- 2kg package, $45 order → $8.00 + (2 × $1.50) = $11.00 shipping
- 5kg package, $80 order → FREE shipping (over threshold)

### Example 2: Specialty Store with Constraints
**Configuration:**
- Base shipping: $15.00
- Minimum order: $50
- Maximum weight: 25kg
- Free shipping: $150+

**Results:**
- $30 order → Shipping method not available (below minimum)
- $75 order, 10kg → $15.00 shipping
- $200 order → FREE shipping

## Installation Requirements

- **Magento Version**: 2.4.0 or higher
- **PHP Version**: 8.1, 8.2, or 8.3
- **Admin Access**: Required for configuration

## Quick Setup

1. **Install the module files**
2. **Enable via command line**: `php bin/magento module:enable CustomShipping_Method`
3. **Run setup**: `php bin/magento setup:upgrade`
4. **Configure in Admin**: Stores → Configuration → Sales → Shipping Methods

## Configuration Guide

### Basic Settings
Navigate to: **Admin → Stores → Configuration → Sales → Shipping Methods → Custom Shipping Method**

| Setting | Description | Example |
|---------|-------------|---------|
| **Enabled** | Turn the shipping method on/off | Yes |
| **Title** | Name shown to customers | "Express Delivery" |
| **Method Name** | Specific service name | "2-3 Business Days" |
| **Price** | Base shipping cost | $12.00 |
| **Sort Order** | Display order in checkout | 10 |

### Advanced Settings

| Setting | Description | When to Use |
|---------|-------------|-------------|
| **Weight-Based Pricing** | Add cost per kilogram | Heavy products, variable shipping costs |
| **Price per KG** | Additional charge per kg | $2.50 per kg |
| **Min Order Amount** | Minimum order to qualify | Encourage larger orders |
| **Max Order Amount** | Maximum order allowed | Special handling limits |
| **Free Shipping Threshold** | Order value for free shipping | Customer incentives |
| **Max Weight** | Weight limit for this method | Handling capacity limits |

### Geographic Controls
- **All Countries**: Available worldwide
- **Specific Countries**: Choose allowed destinations
- **Error Messages**: Custom text when unavailable

## Common Use Cases

### Scenario 1: Encourage Larger Orders
Set free shipping threshold at $99 to increase average order value. Customers will add items to reach free shipping.

### Scenario 2: Handle Heavy Products
Enable weight-based pricing for products like furniture or machinery where shipping costs scale with weight.

### Scenario 3: Premium Service Tier
Set minimum order amounts for express shipping to position it as a premium service.

### Scenario 4: Geographic Limitations
Restrict to specific countries where you have reliable shipping partnerships.

## Troubleshooting

### "Shipping method not appearing"
1. Check if module is enabled in Admin
2. Verify order meets minimum amount requirements
3. Check weight doesn't exceed maximum
4. Confirm customer's country is allowed

### "Incorrect shipping rates"
1. Verify weight-based pricing settings
2. Check free shipping threshold
3. Review handling fee configuration
4. Clear cache: `php bin/magento cache:clean`

### "Admin configuration not saving"
1. Check admin user permissions
2. Verify form validation (all required fields)
3. Ensure numeric fields contain valid numbers

## Performance Notes

This module includes intelligent caching to prevent recalculating shipping rates on every page load. Rates are cached for 1 hour and automatically invalidated when configuration changes.

## What Makes This Different

Unlike basic flat-rate modules, this provides:
- **Conditional Logic**: Rules that actually reflect real business needs
- **Performance Optimization**: Won't slow down your checkout
- **Professional Error Handling**: Clear messages when shipping isn't available
- **Extensible Architecture**: Can be customized for specific business requirements

## Support

This module includes comprehensive logging for troubleshooting. Check `/var/log/custom_shipping.log` for detailed information about rate calculations and any errors.

For configuration assistance, refer to the examples above or the detailed technical documentation.

---

**Version**: 1.0.0  
**Compatibility**: Magento 2.4.0+  
**License**: OSL 3.0 / AFL 3.0

*Built for merchants who need more than "one size fits all" shipping.*