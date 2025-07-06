<?php
/**
 * Unit tests for ShippingRateCache
 */
declare(strict_types=1);

namespace CustomShipping\Method\Test\Unit\Model\Cache;

use CustomShipping\Method\Model\Cache\ShippingRateCache;
use CustomShipping\Method\Api\Data\ShippingRateInterface;
use CustomShipping\Method\Api\Data\ShippingRateInterfaceFactory;
use Magento\Framework\App\CacheInterface;
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
     * @var CacheInterface|MockObject
     */
    private $cacheInterfaceMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var ShippingRateInterfaceFactory|MockObject
     */
    private $shippingRateFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

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
        $this->cacheInterfaceMock = $this->createMock(CacheInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->shippingRateFactoryMock = $this->createMock(ShippingRateInterfaceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->rateRequestMock = $this->createMock(RateRequest::class);
        $this->shippingRateMock = $this->createMock(ShippingRateInterface::class);

        $this->cache = new ShippingRateCache(
            $this->cacheInterfaceMock,
            $this->serializerMock,
            $this->shippingRateFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test loading rate from cache - cache hit
     */
    public function testLoadCacheHit()
    {
        $cacheKey = $this->setupCacheKey();
        $cachedData = [
            'price' => 15.50,
            'cost' => 12.00
        ];
        $serializedData = json_encode($cachedData);

        $this->cacheInterfaceMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn($serializedData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($cachedData);

        $this->shippingRateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->shippingRateMock);

        $this->shippingRateMock->expects($this->once())
            ->method('setPrice')
            ->with($cachedData['price'])
            ->willReturnSelf();

        $this->shippingRateMock->expects($this->once())
            ->method('setCost')
            ->with($cachedData['cost'])
            ->willReturnSelf();

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Cache hit'));

        $result = $this->cache->load($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Test loading rate from cache - cache miss
     */
    public function testLoadCacheMiss()
    {
        $cacheKey = $this->setupCacheKey();

        $this->cacheInterfaceMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn(false);

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $this->shippingRateFactoryMock->expects($this->never())
            ->method('create');

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Cache miss'));

        $result = $this->cache->load($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test loading with invalid cached data
     */
    public function testLoadInvalidCachedData()
    {
        $cacheKey = $this->setupCacheKey();
        $invalidData = 'invalid_json_data';

        $this->cacheInterfaceMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn($invalidData);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($invalidData)
            ->willThrowException(new \InvalidArgumentException('Unable to unserialize'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to unserialize'));

        $result = $this->cache->load($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test saving rate to cache
     */
    public function testSave()
    {
        $cacheKey = $this->setupCacheKey();
        $price = 25.00;
        $cost = 20.00;
        $lifetime = 3600; // 1 hour

        $this->shippingRateMock->method('getPrice')->willReturn($price);
        $this->shippingRateMock->method('getCost')->willReturn($cost);

        $expectedData = [
            'price' => $price,
            'cost' => $cost
        ];

        $serializedData = json_encode($expectedData);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedData)
            ->willReturn($serializedData);

        $this->cacheInterfaceMock->expects($this->once())
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

        $this->cacheInterfaceMock->expects($this->never())
            ->method('save');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to serialize'));

        $this->cache->save($this->rateRequestMock, $this->shippingRateMock);
    }

    /**
     * Test cache key generation
     */
    public function testGenerateCacheKey()
    {
        $weight = 5.5;
        $value = 100.00;
        $destCountry = 'US';
        $destRegion = 'CA';
        $destPostcode = '90210';
        $storeId = 1;
        $websiteId = 1;
        $customerGroupId = 1;
        $currencyCode = 'USD';

        $this->rateRequestMock->method('getPackageWeight')->willReturn($weight);
        $this->rateRequestMock->method('getPackageValue')->willReturn($value);
        $this->rateRequestMock->method('getDestCountryId')->willReturn($destCountry);
        $this->rateRequestMock->method('getDestRegionId')->willReturn($destRegion);
        $this->rateRequestMock->method('getDestPostcode')->willReturn($destPostcode);
        $this->rateRequestMock->method('getStoreId')->willReturn($storeId);
        $this->rateRequestMock->method('getWebsiteId')->willReturn($websiteId);
        
        // Mock customer group ID through store
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getCurrentCurrencyCode')->willReturn($currencyCode);
        $this->rateRequestMock->method('getData')
            ->with('customer_group_id')
            ->willReturn($customerGroupId);

        $keyData = [
            'package_weight' => $weight,
            'package_value' => $value,
            'dest_country' => $destCountry,
            'dest_region' => $destRegion,
            'dest_postcode' => $destPostcode,
            'store_id' => $storeId,
            'website_id' => $websiteId,
            'customer_group_id' => $customerGroupId
        ];

        $serializedKeyData = json_encode($keyData);
        $expectedHash = hash('sha256', $serializedKeyData);
        $expectedKey = 'custom_shipping_rate_' . $expectedHash;

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($keyData)
            ->willReturn($serializedKeyData);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, $this->rateRequestMock);

        $this->assertEquals($expectedKey, $result);
    }

    /**
     * Test clearing cache
     */
    public function testClearCache()
    {
        $this->cacheInterfaceMock->expects($this->once())
            ->method('clean')
            ->with(['custom_shipping_rates']);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Custom shipping rates cache cleared');

        $this->cache->clearCache();
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
        $this->rateRequestMock->method('getData')
            ->with('customer_group_id')
            ->willReturn(1);

        $keyData = [
            'package_weight' => 5.0,
            'package_value' => 50.0,
            'dest_country' => 'US',
            'dest_region' => 'CA',
            'dest_postcode' => '12345',
            'store_id' => 1,
            'website_id' => 1,
            'customer_group_id' => 1
        ];

        $serializedData = json_encode($keyData);
        $hash = hash('sha256', $serializedData);

        $this->serializerMock->method('serialize')
            ->with($keyData)
            ->willReturn($serializedData);

        return 'custom_shipping_rate_' . $hash;
    }
}