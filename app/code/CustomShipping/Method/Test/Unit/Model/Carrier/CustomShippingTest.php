<?php
/**
 * Unit tests for Custom Shipping Carrier
 */
declare(strict_types=1);

namespace CustomShipping\Method\Test\Unit\Model\Carrier;

use CustomShipping\Method\Model\Carrier\CustomShipping;
use CustomShipping\Method\Api\ShippingCalculatorInterface;
use CustomShipping\Method\Api\Data\ShippingRateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomShippingTest extends TestCase
{
    /**
     * @var CustomShipping
     */
    private $model;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ErrorFactory|MockObject
     */
    private $rateErrorFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $rateResultFactoryMock;

    /**
     * @var MethodFactory|MockObject
     */
    private $rateMethodFactoryMock;

    /**
     * @var RateRequest|MockObject
     */
    private $rateRequestMock;

    /**
     * @var ShippingCalculatorInterface|MockObject
     */
    private $shippingCalculatorMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->rateErrorFactoryMock = $this->createMock(ErrorFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->rateResultFactoryMock = $this->createMock(ResultFactory::class);
        $this->rateMethodFactoryMock = $this->createMock(MethodFactory::class);
        $this->rateRequestMock = $this->createMock(RateRequest::class);
        $this->shippingCalculatorMock = $this->createMock(ShippingCalculatorInterface::class);

        $this->model = $this->objectManager->getObject(
            CustomShipping::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'rateErrorFactory' => $this->rateErrorFactoryMock,
                'logger' => $this->loggerMock,
                'rateResultFactory' => $this->rateResultFactoryMock,
                'rateMethodFactory' => $this->rateMethodFactoryMock,
                'shippingCalculator' => $this->shippingCalculatorMock
            ]
        );
    }

    /**
     * Test that inactive carrier returns false
     */
    public function testCollectRatesWhenInactive()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('carriers/customshipping/active')
            ->willReturn(false);

        $result = $this->model->collectRates($this->rateRequestMock);
        
        $this->assertFalse($result);
    }

    /**
     * Test basic flat rate calculation
     */
    public function testCollectRatesBasicFlatRate()
    {
        $basePrice = 15.00;
        $handlingFee = 2.00;
        
        $this->setupActiveMocks();

        // Create shipping rate mock
        $shippingRateMock = $this->createMock(ShippingRateInterface::class);
        $shippingRateMock->method('getPrice')->willReturn($basePrice + $handlingFee);
        $shippingRateMock->method('getCost')->willReturn($basePrice);

        // ShippingCalculator should return a rate
        $this->shippingCalculatorMock->expects($this->once())
            ->method('calculateRate')
            ->with($this->rateRequestMock)
            ->willReturn($shippingRateMock);

        $resultMock = $this->createMock(Result::class);
        $methodMock = $this->createMock(Method::class);

        $this->rateResultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultMock);

        $this->rateMethodFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($methodMock);

        // Verify method configuration
        $methodMock->expects($this->once())
            ->method('setCarrier')
            ->with('customshipping');

        $methodMock->expects($this->once())
            ->method('setCarrierTitle');

        $methodMock->expects($this->once())
            ->method('setMethod')
            ->with('customshipping');

        $methodMock->expects($this->once())
            ->method('setMethodTitle');

        $methodMock->expects($this->once())
            ->method('setPrice')
            ->with($basePrice + $handlingFee);

        $methodMock->expects($this->once())
            ->method('setCost')
            ->with($basePrice);

        $resultMock->expects($this->once())
            ->method('append')
            ->with($methodMock);

        $result = $this->model->collectRates($this->rateRequestMock);
        
        $this->assertSame($resultMock, $result);
    }

    /**
     * Test weight-based pricing calculation
     */
    public function testCollectRatesWeightBasedPricing()
    {
        $basePrice = 10.00;
        $pricePerKg = 2.50;
        $packageWeight = 5.0;
        $expectedPrice = $basePrice + ($packageWeight * $pricePerKg); // 10 + (5 * 2.5) = 22.50

        $this->setupActiveMocks();

        // Create shipping rate mock
        $shippingRateMock = $this->createMock(ShippingRateInterface::class);
        $shippingRateMock->method('getPrice')->willReturn($expectedPrice);
        $shippingRateMock->method('getCost')->willReturn($expectedPrice);

        // ShippingCalculator should return a rate
        $this->shippingCalculatorMock->expects($this->once())
            ->method('calculateRate')
            ->with($this->rateRequestMock)
            ->willReturn($shippingRateMock);

        $resultMock = $this->createMock(Result::class);
        $methodMock = $this->createMock(Method::class);

        $this->rateResultFactoryMock->method('create')->willReturn($resultMock);
        $this->rateMethodFactoryMock->method('create')->willReturn($methodMock);

        $methodMock->expects($this->once())
            ->method('setPrice')
            ->with($expectedPrice);

        $methodMock->expects($this->once())
            ->method('setCost')
            ->with($expectedPrice);

        $result = $this->model->collectRates($this->rateRequestMock);
        
        $this->assertSame($resultMock, $result);
    }

    /**
     * Test free shipping threshold
     */
    public function testCollectRatesFreeShippingThreshold()
    {
        $basePrice = 15.00;
        $freeShippingThreshold = 100.00;
        $packageValue = 120.00; // Above threshold

        $this->setupActiveMocks();

        // Create shipping rate mock for free shipping
        $shippingRateMock = $this->createMock(ShippingRateInterface::class);
        $shippingRateMock->method('getPrice')->willReturn(0);
        $shippingRateMock->method('getCost')->willReturn(0);

        // ShippingCalculator should return free shipping
        $this->shippingCalculatorMock->expects($this->once())
            ->method('calculateRate')
            ->with($this->rateRequestMock)
            ->willReturn($shippingRateMock);

        $resultMock = $this->createMock(Result::class);
        $methodMock = $this->createMock(Method::class);

        $this->rateResultFactoryMock->method('create')->willReturn($resultMock);
        $this->rateMethodFactoryMock->method('create')->willReturn($methodMock);

        // Should be free (0 price)
        $methodMock->expects($this->once())
            ->method('setPrice')
            ->with(0);

        $methodMock->expects($this->once())
            ->method('setCost')
            ->with(0);

        $result = $this->model->collectRates($this->rateRequestMock);
        
        $this->assertSame($resultMock, $result);
    }

    /**
     * Test order constraints - maximum weight exceeded
     */
    public function testCollectRatesMaxWeightExceeded()
    {
        $maxWeight = 20.0;
        $packageWeight = 25.0; // Exceeds limit

        $this->setupActiveMocks();

        // ShippingCalculator should return null for invalid constraints
        $this->shippingCalculatorMock->expects($this->once())
            ->method('calculateRate')
            ->with($this->rateRequestMock)
            ->willReturn(null);

        $this->setupErrorResult();

        $result = $this->model->collectRates($this->rateRequestMock);
        
        $this->assertInstanceOf(Result::class, $result);
    }

    /**
     * Test order constraints - minimum order amount not met
     */
    public function testCollectRatesMinOrderAmountNotMet()
    {
        $minOrderAmount = 50.0;
        $packageValue = 30.0; // Below minimum

        $this->setupActiveMocks();

        // ShippingCalculator should return null for invalid constraints
        $this->shippingCalculatorMock->expects($this->once())
            ->method('calculateRate')
            ->with($this->rateRequestMock)
            ->willReturn(null);

        $this->setupErrorResult();

        $result = $this->model->collectRates($this->rateRequestMock);
        
        $this->assertInstanceOf(Result::class, $result);
    }

    /**
     * Test getAllowedMethods returns correct array
     */
    public function testGetAllowedMethods()
    {
        $methodName = 'Custom Shipping Method';
        
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('carriers/customshipping/name')
            ->willReturn($methodName);

        $result = $this->model->getAllowedMethods();
        
        $this->assertEquals(['customshipping' => $methodName], $result);
    }

    /**
     * Test tracking availability
     */
    public function testIsTrackingAvailable()
    {
        $this->assertFalse($this->model->isTrackingAvailable());
    }

    /**
     * Test shipping labels availability
     */
    public function testIsShippingLabelsAvailable()
    {
        $this->assertFalse($this->model->isShippingLabelsAvailable());
    }

    /**
     * Setup active carrier mocks
     */
    private function setupActiveMocks()
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with('carriers/customshipping/active')
            ->willReturn(true);

        // Setup basic configuration values that the carrier needs
        $this->scopeConfigMock->method('getValue')
            ->willReturnCallback(function ($path) {
                $configMap = [
                    'carriers/customshipping/title' => 'Custom Shipping',
                    'carriers/customshipping/name' => 'Custom Method',
                ];
                return $configMap[$path] ?? null;
            });
    }


    /**
     * Setup error result for failed validations
     */
    private function setupErrorResult()
    {
        $resultMock = $this->createMock(Result::class);
        $errorMock = $this->createMock(Error::class);

        $this->rateResultFactoryMock->method('create')->willReturn($resultMock);
        $this->rateErrorFactoryMock->method('create')->willReturn($errorMock);

        $this->scopeConfigMock->method('getValue')
            ->with('carriers/customshipping/specificerrmsg')
            ->willReturn('Shipping not available');

        $this->scopeConfigMock->method('getValue')
            ->with('carriers/customshipping/title')
            ->willReturn('Custom Shipping');

        $errorMock->expects($this->once())->method('setCarrier')->with('customshipping');
        $errorMock->expects($this->once())->method('setCarrierTitle')->with('Custom Shipping');
        $errorMock->expects($this->once())->method('setErrorMessage')->with('Shipping not available');
        
        $resultMock->expects($this->once())->method('append')->with($errorMock);
    }
}