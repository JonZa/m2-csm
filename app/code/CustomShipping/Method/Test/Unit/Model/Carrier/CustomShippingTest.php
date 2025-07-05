<?php
/**
 * Unit tests for Custom Shipping Carrier
 */

namespace CustomShipping\Method\Test\Unit\Model\Carrier;

use CustomShipping\Method\Model\Carrier\CustomShipping;
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

        $this->model = $this->objectManager->getObject(
            CustomShipping::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'rateErrorFactory' => $this->rateErrorFactoryMock,
                'logger' => $this->loggerMock,
                'rateResultFactory' => $this->rateResultFactoryMock,
                'rateMethodFactory' => $this->rateMethodFactoryMock
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
        $this->setupValidOrderConstraints();
        $this->setupBasicPricingMocks($basePrice, $handlingFee);

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

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
            ->method('setCarrierTitle')
            ->with('Custom Shipping');

        $methodMock->expects($this->once())
            ->method('setMethod')
            ->with('customshipping');

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
        $this->setupValidOrderConstraints();
        $this->setupWeightBasedPricingMocks($basePrice, $pricePerKg);

        $this->rateRequestMock->method('getPackageWeight')->willReturn($packageWeight);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

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
        $this->setupValidOrderConstraints();
        $this->setupFreeShippingMocks($basePrice, $freeShippingThreshold);

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn($packageValue);

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
        $this->setupConstraintMocks($maxWeight, 0, 0);

        $this->rateRequestMock->method('getPackageWeight')->willReturn($packageWeight);
        $this->rateRequestMock->method('getPackageValue')->willReturn(50.0);

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
        $this->setupConstraintMocks(0, $minOrderAmount, 0);

        $this->rateRequestMock->method('getPackageWeight')->willReturn(5.0);
        $this->rateRequestMock->method('getPackageValue')->willReturn($packageValue);

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
    }

    /**
     * Setup valid order constraints (no limits)
     */
    private function setupValidOrderConstraints()
    {
        $this->setupConstraintMocks(0, 0, 0); // No limits
    }

    /**
     * Setup constraint mocks
     */
    private function setupConstraintMocks($maxWeight, $minAmount, $maxAmount)
    {
        $valueMap = [
            ['carriers/customshipping/max_weight', null, null, $maxWeight],
            ['carriers/customshipping/min_order_amount', null, null, $minAmount],
            ['carriers/customshipping/max_order_amount', null, null, $maxAmount],
        ];

        $this->scopeConfigMock->method('getValue')->willReturnMap($valueMap);
    }

    /**
     * Setup basic pricing mocks
     */
    private function setupBasicPricingMocks($basePrice, $handlingFee = 0)
    {
        $valueMap = [
            ['carriers/customshipping/price', null, null, $basePrice],
            ['carriers/customshipping/handling_fee', null, null, $handlingFee],
            ['carriers/customshipping/handling_type', null, null, 'F'],
            ['carriers/customshipping/title', null, null, 'Custom Shipping'],
            ['carriers/customshipping/name', null, null, 'Custom Method'],
            ['carriers/customshipping/free_shipping_threshold', null, null, 0],
            ['carriers/customshipping/max_weight', null, null, 0],
            ['carriers/customshipping/min_order_amount', null, null, 0],
            ['carriers/customshipping/max_order_amount', null, null, 0],
        ];

        $flagMap = [
            ['carriers/customshipping/weight_based', null, null, false],
        ];

        $this->scopeConfigMock->method('getValue')->willReturnMap($valueMap);
        $this->scopeConfigMock->method('isSetFlag')->willReturnMap($flagMap);
    }

    /**
     * Setup weight-based pricing mocks
     */
    private function setupWeightBasedPricingMocks($basePrice, $pricePerKg)
    {
        $valueMap = [
            ['carriers/customshipping/price', null, null, $basePrice],
            ['carriers/customshipping/price_per_kg', null, null, $pricePerKg],
            ['carriers/customshipping/handling_fee', null, null, 0],
            ['carriers/customshipping/handling_type', null, null, 'F'],
            ['carriers/customshipping/title', null, null, 'Custom Shipping'],
            ['carriers/customshipping/name', null, null, 'Custom Method'],
            ['carriers/customshipping/free_shipping_threshold', null, null, 0],
            ['carriers/customshipping/max_weight', null, null, 0],
            ['carriers/customshipping/min_order_amount', null, null, 0],
            ['carriers/customshipping/max_order_amount', null, null, 0],
        ];

        $flagMap = [
            ['carriers/customshipping/active', null, null, true],
            ['carriers/customshipping/weight_based', null, null, true],
        ];

        $this->scopeConfigMock->method('getValue')->willReturnMap($valueMap);
        $this->scopeConfigMock->method('isSetFlag')->willReturnMap($flagMap);
    }

    /**
     * Setup free shipping mocks
     */
    private function setupFreeShippingMocks($basePrice, $threshold)
    {
        $valueMap = [
            ['carriers/customshipping/price', null, null, $basePrice],
            ['carriers/customshipping/handling_fee', null, null, 0],
            ['carriers/customshipping/handling_type', null, null, 'F'],
            ['carriers/customshipping/title', null, null, 'Custom Shipping'],
            ['carriers/customshipping/name', null, null, 'Custom Method'],
            ['carriers/customshipping/free_shipping_threshold', null, null, $threshold],
            ['carriers/customshipping/max_weight', null, null, 0],
            ['carriers/customshipping/min_order_amount', null, null, 0],
            ['carriers/customshipping/max_order_amount', null, null, 0],
        ];

        $flagMap = [
            ['carriers/customshipping/active', null, null, true],
            ['carriers/customshipping/weight_based', null, null, false],
        ];

        $this->scopeConfigMock->method('getValue')->willReturnMap($valueMap);
        $this->scopeConfigMock->method('isSetFlag')->willReturnMap($flagMap);
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