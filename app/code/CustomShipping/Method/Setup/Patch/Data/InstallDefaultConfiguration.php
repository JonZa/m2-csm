<?php
/**
 * Data patch for installing default shipping method configuration
 */

namespace CustomShipping\Method\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Install default configuration for custom shipping method
 */
class InstallDefaultConfiguration implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    /**
     * Apply patch
     *
     * @return InstallDefaultConfiguration
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Default configuration values
        $defaultConfig = [
            'carriers/customshipping/active' => '0', // Disabled by default - admin must enable
            'carriers/customshipping/title' => 'Custom Shipping',
            'carriers/customshipping/name' => 'Standard Delivery',
            'carriers/customshipping/price' => '10.00',
            'carriers/customshipping/type' => 'I',
            'carriers/customshipping/handling_type' => 'F',
            'carriers/customshipping/handling_fee' => '0',
            'carriers/customshipping/sort_order' => '10',
            'carriers/customshipping/sallowspecific' => '0',
            'carriers/customshipping/showmethod' => '1',
            'carriers/customshipping/specificerrmsg' => 'This shipping method is currently unavailable. Please contact us for assistance.',
            
            // Enhanced configuration
            'carriers/customshipping/weight_based' => '0',
            'carriers/customshipping/price_per_kg' => '2.50',
            'carriers/customshipping/min_order_amount' => '0',
            'carriers/customshipping/max_order_amount' => '0',
            'carriers/customshipping/free_shipping_threshold' => '100.00',
            'carriers/customshipping/max_weight' => '50.00'
        ];

        // Write configuration to core_config_data table
        foreach ($defaultConfig as $path => $value) {
            $this->configWriter->save(
                $path,
                $value,
                ScopeInterface::SCOPE_DEFAULT,
                0
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}