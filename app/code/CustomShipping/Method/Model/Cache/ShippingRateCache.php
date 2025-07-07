<?php
/**
 * Shipping Rate Cache Service
 * Handles caching of calculated shipping rates
 */
declare(strict_types=1);

namespace CustomShipping\Method\Model\Cache;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;

/**
 * Shipping rate cache implementation
 */
class ShippingRateCache
{
    /**
     * Cache type identifier
     */
    const TYPE_IDENTIFIER = 'custom_shipping_rates';

    /**
     * Default cache lifetime in seconds (1 hour)
     */
    const DEFAULT_CACHE_LIFETIME = 3600;

    /**
     * @var FrontendInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Pool $cacheFrontendPool
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Pool $cacheFrontendPool,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cache = $cacheFrontendPool->get(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get cached shipping rate
     *
     * @param RateRequest $request
     * @return float|null
     */
    public function getCachedRate(RateRequest $request): ?float
    {
        $cacheKey = $this->generateCacheKey($request);
        
        try {
            $cachedData = $this->cache->load($cacheKey);
            
            if ($cachedData !== false) {
                $data = $this->serializer->unserialize($cachedData);
                
                $this->logger->debug('Cache hit for shipping rate', [
                    'cache_key' => $cacheKey,
                    'rate' => $data['rate']
                ]);
                
                return (float)$data['rate'];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to retrieve cached shipping rate', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Save shipping rate to cache
     *
     * @param RateRequest $request
     * @param float $rate
     * @param array $calculationDetails
     * @return bool
     */
    public function saveRate(RateRequest $request, float $rate, array $calculationDetails = []): bool
    {
        $cacheKey = $this->generateCacheKey($request);
        
        try {
            $data = [
                'rate' => $rate,
                'timestamp' => time(),
                'calculation_details' => $calculationDetails,
                'request_data' => $this->getRequestData($request)
            ];

            $serializedData = $this->serializer->serialize($data);
            $tags = $this->getCacheTags($request);

            $result = $this->cache->save(
                $serializedData,
                $cacheKey,
                $tags,
                $this->getCacheLifetime()
            );

            if ($result) {
                $this->logger->debug('Shipping rate cached successfully', [
                    'cache_key' => $cacheKey,
                    'rate' => $rate,
                    'tags' => $tags
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to cache shipping rate', [
                'cache_key' => $cacheKey,
                'rate' => $rate,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Clear cache for specific request parameters
     *
     * @param RateRequest|null $request
     * @return bool
     */
    public function clearCache(?RateRequest $request = null): bool
    {
        try {
            if ($request === null) {
                // Clear all shipping rate cache
                return $this->cache->clean();
            }

            // Clear cache for specific request
            $tags = $this->getCacheTags($request);
            return $this->cache->clean('matchingAnyTag', $tags);

        } catch (\Exception $e) {
            $this->logger->error('Failed to clear shipping rate cache', [
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Generate cache key based on request parameters
     *
     * @param RateRequest $request
     * @return string
     */
    private function generateCacheKey(RateRequest $request): string
    {
        $keyData = [
            'package_weight' => $request->getPackageWeight(),
            'package_value' => $request->getPackageValue(),
            'dest_country' => $request->getDestCountryId(),
            'dest_region' => $request->getDestRegionId(),
            'dest_postcode' => $request->getDestPostcode(),
            'store_id' => $request->getStoreId(),
            'website_id' => $request->getWebsiteId(),
            'customer_group' => $request->getCustomerGroupId()
        ];

        return self::TYPE_IDENTIFIER . '_' . hash('sha256', $this->serializer->serialize($keyData));
    }

    /**
     * Get cache tags for the request
     *
     * @param RateRequest $request
     * @return array
     */
    private function getCacheTags(RateRequest $request): array
    {
        $tags = [
            self::TYPE_IDENTIFIER,
            self::TYPE_IDENTIFIER . '_store_' . $request->getStoreId(),
            self::TYPE_IDENTIFIER . '_website_' . $request->getWebsiteId(),
            self::TYPE_IDENTIFIER . '_country_' . $request->getDestCountryId()
        ];

        if ($request->getDestRegionId()) {
            $tags[] = self::TYPE_IDENTIFIER . '_region_' . $request->getDestRegionId();
        }

        return $tags;
    }

    /**
     * Extract relevant request data for caching metadata
     *
     * @param RateRequest $request
     * @return array
     */
    private function getRequestData(RateRequest $request): array
    {
        return [
            'package_weight' => $request->getPackageWeight(),
            'package_value' => $request->getPackageValue(),
            'dest_country' => $request->getDestCountryId(),
            'dest_region' => $request->getDestRegionId(),
            'dest_postcode' => $request->getDestPostcode(),
            'store_id' => $request->getStoreId()
        ];
    }

    /**
     * Get cache lifetime from configuration
     *
     * @return int
     */
    private function getCacheLifetime(): int
    {
        $configuredLifetime = $this->scopeConfig->getValue(
            'carriers/customshipping/cache_lifetime',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        return (int)($configuredLifetime ?: self::DEFAULT_CACHE_LIFETIME);
    }
}