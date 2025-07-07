<?php
/**
 * Unit tests for ShippingRateCache
 */
declare(strict_types=1);

namespace CustomShipping\Method\Test\Unit\Model\Cache;

use CustomShipping\Method\Model\Cache\ShippingRateCache;
use CustomShipping\Method\Api\Data\ShippingRateInterface;
use CustomShipping\Method\Api\Data\ShippingRateInterfaceFactory;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShippingRateCacheTest extends TestCase
{
    /**
     * @var ShippingRateCache
     */
    private $cache;

    /**
     * @var Pool|MockObject
     */
    private $cacheFrontendPoolMock;

    /**
     * @var FrontendInterface|MockObject
     */
    private $cacheFrontendMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var RateRequest|MockObject
     */
    private $rateRequestMock;

    /**
     * @var ShippingRateInterface|MockObject
     */
    private $shippingRateMock;

    protected function setUp(): void
    {
        $this->cacheFrontendPoolMock = $this->createMock(Pool::class);
        $this->cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->rateRequestMock = $this->createMock(RateRequest::class);
        $this->shippingRateMock = $this->createMock(ShippingRateInterface::class);

        // Setup cache frontend pool to return our cache frontend mock
        $this->cacheFrontendPoolMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn($this->cacheFrontendMock);

        $this->cache = new ShippingRateCache(
            $this->cacheFrontendPoolMock,
            $this->serializerMock,
            $this->loggerMock,
            $this->scopeConfigMock
        );
    }

    /**
     * Test loading rate from cache - cache hit
     */
    public function testGetCachedRateCacheHit()
    {
        $cacheKey = $this->setupCacheKey();
        $cachedData = [
            'rate' => 15.50,
            'calculation_details' => ['base_price' => 10.00, 'handling' => 5.50]
        ];
        $serializedData = json_encode($cachedData);

        $this->cacheFrontendMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($cachedData);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                'Cache hit for shipping rate',
                [
                    'cache_key' => $cacheKey,
                    'rate' => $cachedData['rate']
                ]
            );

        $result = $this->cache->getCachedRate($this->rateRequestMock);

        $this->assertEquals(15.50, $result);
    }

    /**
     * Test loading rate from cache - cache miss
     */
    public function testGetCachedRateCacheMiss()
    {
        $cacheKey = $this->setupCacheKey();

        $this->cacheFrontendMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn(false);

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $result = $this->cache->getCachedRate($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test loading with invalid cached data
     */
    public function testGetCachedRateInvalidData()
    {
        $cacheKey = $this->setupCacheKey();
        $invalidData = 'invalid_json_data';

        $this->cacheFrontendMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn($invalidData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($invalidData)
            ->willThrowException(new \InvalidArgumentException('Unable to unserialize'));

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to retrieve cached shipping rate',
                [
                    'cache_key' => $cacheKey,
                    'error' => 'Unable to unserialize'
                ]
            );

        $result = $this->cache->getCachedRate($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test saving rate to cache
     */
    public function testSaveRate()
    {
        $cacheKey = $this->setupCacheKey();
        $rate = 25.00;
        $calculationDetails = ['base_price' => 20.00, 'handling' => 5.00];
        $lifetime = 3600; // 1 hour

        $expectedData = [
            'rate' => $rate,
            'calculation_details' => $calculationDetails,
            'timestamp' => time()
        ];

        $serializedData = json_encode($expectedData);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedData)
            ->willReturn($serializedData);

        $this->cacheFrontendMock->expects($this->once())
            ->method('save')
            ->with(
                $serializedData,
                $cacheKey,
                ['custom_shipping_rates'],
                $lifetime
            );

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Saved to cache'));

        $this->cache->save($this->rateRequestMock, $this->shippingRateMock);
    }

    /**
     * Test save with serialization failure
     */
    public function testSaveSerializationFailure()
    {
        $this->setupCacheKey();

        $this->shippingRateMock->method('getPrice')->willReturn(10.00);
        $this->shippingRateMock->method('getCost')->willReturn(8.00);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->willThrowException(new \InvalidArgumentException('Cannot serialize'));

        $this->cacheFrontendMock->expects($this->never())
            ->method('save');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to serialize'));

        $this->cache->save($this->rateRequestMock, $this->shippingRateMock);
    }

    /**
     * Test cache with different scope config values
     */
    public function testCacheLifetimeConfiguration()
    {
        $cacheKey = $this->setupCacheKey();
        $rate = 15.00;
        $customLifetime = 7200; // 2 hours

        // Test with custom lifetime from config
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'carriers/customshipping/cache_lifetime',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn($customLifetime);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->willReturn('serialized_data');

        $this->cacheFrontendMock->expects($this->once())
            ->method('save')
            ->with(
                'serialized_data',
                $cacheKey,
                $this->anything(),
                $customLifetime
            )
            ->willReturn(true);

        $result = $this->cache->saveRate($this->rateRequestMock, $rate);
        $this->assertTrue($result);
    }

    /**
     * Test clearing all cache
     */
    public function testClearAllCache()
    {
        $this->cacheFrontendMock->expects($this->once())
            ->method('clean')
            ->with()
            ->willReturn(true);

        $result = $this->cache->clearCache();
        $this->assertTrue($result);
    }

    /**
     * Test clearing cache for specific request
     */
    public function testClearCacheForSpecificRequest()
    {
        $this->setupCacheKey();
        
        // Set up expectations for getCacheTags
        $this->rateRequestMock->method('getDestCountryId')->willReturn('US');
        $this->rateRequestMock->method('getDestRegionId')->willReturn('CA');
        $this->rateRequestMock->method('getStoreId')->willReturn(1);

        $expectedTags = [
            'custom_shipping_rates',
            'custom_shipping_country_US',
            'custom_shipping_region_CA',
            'custom_shipping_store_1'
        ];

        $this->cacheFrontendMock->expects($this->once())
            ->method('clean')
            ->with('matchingAnyTag', $expectedTags)
            ->willReturn(true);

        $result = $this->cache->clearCache($this->rateRequestMock);
        $this->assertTrue($result);
    }

    /**
     * Setup cache key generation
     */
    private function setupCacheKey(): string
    {
        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);
        $this->rateRequestMock->method('getDestCountryId')->willReturn('US');
        $this->rateRequestMock->method('getDestRegionId')->willReturn('CA');
        $this->rateRequestMock->method('getDestPostcode')->willReturn('12345');
        $this->rateRequestMock->method('getStoreId')->willReturn(1);
        $this->rateRequestMock->method('getWebsiteId')->willReturn(1);
        $this->rateRequestMock->method('getCustomerGroupId')
            ->willReturn(1);

        $keyData = [
            'package_weight' => 5.0,
            'package_value' => 50.0,
            'dest_country' => 'US',
            'dest_region' => 'CA',
            'dest_postcode' => '12345',
            'store_id' => 1,
            'website_id' => 1,
            'customer_group' => 1
        ];

        $serializedData = json_encode($keyData);
        $hash = hash('sha256', $serializedData);

        $this->serializerMock->method('serialize')
            ->with($keyData)
            ->willReturn($serializedData);

        return 'custom_shipping_rate_' . $hash;
    }
}