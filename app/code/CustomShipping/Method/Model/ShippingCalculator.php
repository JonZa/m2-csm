<?php
/**
 * Shipping Calculator Service Implementation
 * Handles all shipping rate calculation logic
 */
declare(strict_types=1);

namespace CustomShipping\Method\Model;

use CustomShipping\Method\Api\ShippingCalculatorInterface;
use CustomShipping\Method\Model\Cache\ShippingRateCache;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Shipping calculator service implementation
 */
class ShippingCalculator implements ShippingCalculatorInterface
{
    /**
     * Configuration path constants
     */
    const CONFIG_PATH_ACTIVE = 'carriers/customshipping/active';
    const CONFIG_PATH_PRICE = 'carriers/customshipping/price';
    const CONFIG_PATH_WEIGHT_BASED = 'carriers/customshipping/weight_based';
    const CONFIG_PATH_PRICE_PER_KG = 'carriers/customshipping/price_per_kg';
    const CONFIG_PATH_FREE_SHIPPING_THRESHOLD = 'carriers/customshipping/free_shipping_threshold';
    const CONFIG_PATH_MIN_ORDER_AMOUNT = 'carriers/customshipping/min_order_amount';
    const CONFIG_PATH_MAX_ORDER_AMOUNT = 'carriers/customshipping/max_order_amount';
    const CONFIG_PATH_MAX_WEIGHT = 'carriers/customshipping/max_weight';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShippingRateCache
     */
    private $rateCache;

    /**
     * @var array
     */
    private $validationErrors = [];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ShippingRateCache $rateCache
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ShippingRateCache $rateCache
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->rateCache = $rateCache;
    }

    /**
     * @inheritdoc
     */
    public function calculateRate(RateRequest $request): float
    {
        if (!$this->canHandle($request)) {
            throw new LocalizedException(__('Cannot calculate shipping rate for this request'));
        }

        // Check cache first
        $cachedRate = $this->rateCache->getCachedRate($request);
        if ($cachedRate !== null) {
            $this->logger->debug('Using cached shipping rate', [
                'rate' => $cachedRate,
                'package_weight' => $request->getPackageWeight(),
                'package_value' => $request->getPackageValue()
            ]);
            return $cachedRate;
        }

        // Calculate rate
        if ($this->isFreeShippingApplicable($request)) {
            $rate = 0.0;
            $calculationDetails = ['type' => 'free_shipping', 'threshold_met' => true];
        } else {
            $basePrice = (float)$this->getConfigValue(self::CONFIG_PATH_PRICE, $request->getStoreId());

            if ($this->isWeightBasedPricingEnabled($request->getStoreId())) {
                $rate = $this->calculateWeightBasedRate($request, $basePrice);
                $calculationDetails = [
                    'type' => 'weight_based',
                    'base_price' => $basePrice,
                    'weight' => $request->getPackageWeight(),
                    'price_per_kg' => $this->getConfigValue(self::CONFIG_PATH_PRICE_PER_KG, $request->getStoreId())
                ];
            } else {
                $rate = $basePrice;
                $calculationDetails = ['type' => 'flat_rate', 'base_price' => $basePrice];
            }
        }

        // Cache the calculated rate
        $this->rateCache->saveRate($request, $rate, $calculationDetails);

        return $rate;
    }

    /**
     * @inheritdoc
     */
    public function isFreeShippingApplicable(RateRequest $request): bool
    {
        $threshold = (float)$this->getConfigValue(
            self::CONFIG_PATH_FREE_SHIPPING_THRESHOLD,
            $request->getStoreId()
        );

        if ($threshold <= 0) {
            return false;
        }

        return $request->getPackageValue() >= $threshold;
    }

    /**
     * @inheritdoc
     */
    public function canHandle(RateRequest $request): bool
    {
        $this->validationErrors = [];

        if (!$this->isActive($request->getStoreId())) {
            $this->validationErrors[] = 'Shipping method is not active';
            return false;
        }

        if (!$this->validateWeight($request)) {
            $this->validationErrors[] = 'Package weight exceeds maximum allowed weight';
            return false;
        }

        if (!$this->validateOrderAmount($request)) {
            $this->validationErrors[] = 'Order amount is outside allowed range';
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getValidationErrors(RateRequest $request): array
    {
        $this->canHandle($request); // Populate validation errors
        return $this->validationErrors;
    }

    /**
     * Calculate weight-based shipping rate
     *
     * @param RateRequest $request
     * @param float $basePrice
     * @return float
     */
    private function calculateWeightBasedRate(RateRequest $request, float $basePrice): float
    {
        $pricePerKg = (float)$this->getConfigValue(
            self::CONFIG_PATH_PRICE_PER_KG,
            $request->getStoreId()
        );

        $weightCharge = $request->getPackageWeight() * $pricePerKg;

        $this->logger->debug('Weight-based shipping calculation', [
            'base_price' => $basePrice,
            'package_weight' => $request->getPackageWeight(),
            'price_per_kg' => $pricePerKg,
            'weight_charge' => $weightCharge,
            'total' => $basePrice + $weightCharge
        ]);

        return $basePrice + $weightCharge;
    }

    /**
     * Check if shipping method is active
     *
     * @param int|null $storeId
     * @return bool
     */
    private function isActive(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if weight-based pricing is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    private function isWeightBasedPricingEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_WEIGHT_BASED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Validate package weight against limits
     *
     * @param RateRequest $request
     * @return bool
     */
    private function validateWeight(RateRequest $request): bool
    {
        $maxWeight = (float)$this->getConfigValue(
            self::CONFIG_PATH_MAX_WEIGHT,
            $request->getStoreId()
        );

        if ($maxWeight <= 0) {
            return true; // No weight limit configured
        }

        return $request->getPackageWeight() <= $maxWeight;
    }

    /**
     * Validate order amount against min/max limits
     *
     * @param RateRequest $request
     * @return bool
     */
    private function validateOrderAmount(RateRequest $request): bool
    {
        $packageValue = $request->getPackageValue();

        $minAmount = (float)$this->getConfigValue(
            self::CONFIG_PATH_MIN_ORDER_AMOUNT,
            $request->getStoreId()
        );

        $maxAmount = (float)$this->getConfigValue(
            self::CONFIG_PATH_MAX_ORDER_AMOUNT,
            $request->getStoreId()
        );

        if ($minAmount > 0 && $packageValue < $minAmount) {
            return false;
        }

        if ($maxAmount > 0 && $packageValue > $maxAmount) {
            return false;
        }

        return true;
    }

    /**
     * Get configuration value
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    private function getConfigValue(string $path, ?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}