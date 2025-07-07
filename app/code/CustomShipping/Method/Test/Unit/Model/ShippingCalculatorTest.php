<?php
/**
 * Unit tests for ShippingCalculator service
 */
declare(strict_types=1);

namespace CustomShipping\Method\Test\Unit\Model;

use CustomShipping\Method\Model\ShippingCalculator;
use CustomShipping\Method\Api\Data\ShippingRateInterfaceFactory;
use CustomShipping\Method\Api\Data\ShippingRateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShippingCalculatorTest extends TestCase
{
    /**
     * @var ShippingCalculator
     */
    private $calculator;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

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
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->shippingRateFactoryMock = $this->createMock(ShippingRateInterfaceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->rateRequestMock = $this->createMock(RateRequest::class);
        $this->shippingRateMock = $this->createMock(ShippingRateInterface::class);

        $this->shippingRateFactoryMock->method('create')
            ->willReturn($this->shippingRateMock);

        $this->calculator = new ShippingCalculator(
            $this->scopeConfigMock,
            $this->shippingRateFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test flat rate calculation
     */
    public function testCalculateRateFlatRate()
    {
        $basePrice = 15.00;
        $handlingFee = 2.00;
        $expectedPrice = $basePrice + $handlingFee;

        $this->setupBasicMocks($basePrice, $handlingFee, false);
        $this->setupValidConstraints();

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

        $this->shippingRateMock->expects($this->once())
            ->method('setPrice')
            ->with($expectedPrice)
            ->willReturnSelf();

        $this->shippingRateMock->expects($this->once())
            ->method('setCost')
            ->with($basePrice)
            ->willReturnSelf();

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Test weight-based calculation
     */
    public function testCalculateRateWeightBased()
    {
        $basePrice = 10.00;
        $pricePerKg = 2.50;
        $weight = 5.0;
        $expectedPrice = $basePrice + ($weight * $pricePerKg);

        $this->setupWeightBasedMocks($basePrice, $pricePerKg);
        $this->setupValidConstraints();

        $this->rateRequestMock->method('getPackageWeight')->willReturn($weight);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

        $this->shippingRateMock->expects($this->once())
            ->method('setPrice')
            ->with($expectedPrice)
            ->willReturnSelf();

        $this->shippingRateMock->expects($this->once())
            ->method('setCost')
            ->with($expectedPrice)
            ->willReturnSelf();

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Test free shipping threshold
     */
    public function testCalculateRateFreeShipping()
    {
        $basePrice = 20.00;
        $threshold = 100.00;
        $orderValue = 150.00; // Above threshold

        $this->setupBasicMocks($basePrice, 0, false);
        $this->setupValidConstraints();
        $this->setupFreeShippingThreshold($threshold);

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn($orderValue);

        $this->shippingRateMock->expects($this->once())
            ->method('setPrice')
            ->with(0)
            ->willReturnSelf();

        $this->shippingRateMock->expects($this->once())
            ->method('setCost')
            ->with(0)
            ->willReturnSelf();

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Test maximum weight constraint
     */
    public function testCalculateRateMaxWeightExceeded()
    {
        $maxWeight = 20.0;
        $packageWeight = 25.0; // Exceeds limit

        $this->setupBasicMocks(10.00, 0, false);
        $this->setupWeightConstraint($maxWeight);

        $this->rateRequestMock->method('getPackageWeight')->willReturn($packageWeight);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('exceeds maximum weight'));

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test minimum order amount constraint
     */
    public function testCalculateRateMinOrderAmountNotMet()
    {
        $minAmount = 50.0;
        $orderValue = 30.0; // Below minimum

        $this->setupBasicMocks(10.00, 0, false);
        $this->setupOrderAmountConstraints($minAmount, 0);

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn($orderValue);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('below minimum'));

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test maximum order amount constraint
     */
    public function testCalculateRateMaxOrderAmountExceeded()
    {
        $maxAmount = 1000.0;
        $orderValue = 1500.0; // Above maximum

        $this->setupBasicMocks(10.00, 0, false);
        $this->setupOrderAmountConstraints(0, $maxAmount);

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn($orderValue);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('exceeds maximum'));

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertNull($result);
    }

    /**
     * Test handling fee percentage calculation
     */
    public function testCalculateRateHandlingFeePercentage()
    {
        $basePrice = 100.00;
        $handlingFee = 10; // 10%
        $expectedPrice = $basePrice + ($basePrice * 0.10);

        $this->setupBasicMocks($basePrice, $handlingFee, true); // Percentage type
        $this->setupValidConstraints();

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

        $this->shippingRateMock->expects($this->once())
            ->method('setPrice')
            ->with($expectedPrice)
            ->willReturnSelf();

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Test zero weight products
     */
    public function testCalculateRateZeroWeight()
    {
        $basePrice = 10.00;

        $this->setupBasicMocks($basePrice, 0, false);
        $this->setupValidConstraints();

        $this->rateRequestMock->method('getPackageWeight')->willReturn(0);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

        $this->shippingRateMock->expects($this->once())
            ->method('setPrice')
            ->with($basePrice);

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Test null package value handling
     */
    public function testCalculateRateNullPackageValue()
    {
        $basePrice = 10.00;

        $this->setupBasicMocks($basePrice, 0, false);
        $this->setupValidConstraints();

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn(null);

        $result = $this->calculator->calculateRate($this->rateRequestMock);

        $this->assertSame($this->shippingRateMock, $result);
    }

    /**
     * Setup basic pricing mocks
     */
    private function setupBasicMocks($price, $handlingFee, $isPercentage)
    {
        $handlingType = $isPercentage ? 'P' : 'F';
        
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function($path) use ($price, $handlingFee, $handlingType) {
                $map = [
                    'carriers/customshipping/price' => $price,
                    'carriers/customshipping/handling_fee' => $handlingFee,
                    'carriers/customshipping/handling_type' => $handlingType,
                    'carriers/customshipping/free_shipping_threshold' => 0,
                    'carriers/customshipping/max_weight' => 0,
                    'carriers/customshipping/min_order_amount' => 0,
                    'carriers/customshipping/max_order_amount' => 0,
                ];
                return $map[$path] ?? null;
            });

        $this->scopeConfigMock->method('isSetFlag')
            ->with('carriers/customshipping/weight_based')
            ->willReturn(false);
    }

    /**
     * Setup weight-based pricing mocks
     */
    private function setupWeightBasedMocks($basePrice, $pricePerKg)
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function($path) use ($basePrice, $pricePerKg) {
                $map = [
                    'carriers/customshipping/price' => $basePrice,
                    'carriers/customshipping/price_per_kg' => $pricePerKg,
                    'carriers/customshipping/handling_fee' => 0,
                    'carriers/customshipping/handling_type' => 'F',
                    'carriers/customshipping/free_shipping_threshold' => 0,
                    'carriers/customshipping/max_weight' => 0,
                    'carriers/customshipping/min_order_amount' => 0,
                    'carriers/customshipping/max_order_amount' => 0,
                ];
                return $map[$path] ?? null;
            });

        $this->scopeConfigMock->method('isSetFlag')
            ->with('carriers/customshipping/weight_based')
            ->willReturn(true);
    }

    /**
     * Setup valid constraints
     */
    private function setupValidConstraints()
    {
        // Already set in basic mocks with 0 values (no constraints)
    }

    /**
     * Setup weight constraint
     */
    private function setupWeightConstraint($maxWeight)
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function($path) use ($maxWeight) {
                $map = [
                    'carriers/customshipping/max_weight' => $maxWeight,
                    'carriers/customshipping/price' => 10,
                    'carriers/customshipping/handling_fee' => 0,
                    'carriers/customshipping/handling_type' => 'F',
                    'carriers/customshipping/free_shipping_threshold' => 0,
                    'carriers/customshipping/min_order_amount' => 0,
                    'carriers/customshipping/max_order_amount' => 0,
                ];
                return $map[$path] ?? null;
            });
    }

    /**
     * Setup order amount constraints
     */
    private function setupOrderAmountConstraints($minAmount, $maxAmount)
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function($path) use ($minAmount, $maxAmount) {
                $map = [
                    'carriers/customshipping/price' => 10,
                    'carriers/customshipping/handling_fee' => 0,
                    'carriers/customshipping/handling_type' => 'F',
                    'carriers/customshipping/free_shipping_threshold' => 0,
                    'carriers/customshipping/max_weight' => 0,
                    'carriers/customshipping/min_order_amount' => $minAmount,
                    'carriers/customshipping/max_order_amount' => $maxAmount,
                ];
                return $map[$path] ?? null;
            });
    }

    /**
     * Setup free shipping threshold
     */
    private function setupFreeShippingThreshold($threshold)
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function($path) use ($threshold) {
                if ($path === 'carriers/customshipping/free_shipping_threshold') {
                    return $threshold;
                }
                return $this->returnCallback(function($path) {
                    $map = [
                        'carriers/customshipping/price' => 20,
                        'carriers/customshipping/handling_fee' => 0,
                        'carriers/customshipping/handling_type' => 'F',
                        'carriers/customshipping/max_weight' => 0,
                        'carriers/customshipping/min_order_amount' => 0,
                        'carriers/customshipping/max_order_amount' => 0,
                    ];
                    return $map[$path] ?? null;
                })->invoke($path);
            });
    }
}