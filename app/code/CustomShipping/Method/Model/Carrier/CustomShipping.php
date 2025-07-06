<?php
/**
 * Custom Shipping Carrier Model
 */
declare(strict_types=1);

namespace CustomShipping\Method\Model\Carrier;

use CustomShipping\Method\Api\ShippingCalculatorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class CustomShipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'customshipping';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var ErrorFactory
     */
    protected $rateErrorFactory;

    /**
     * @var ShippingCalculatorInterface
     */
    private $shippingCalculator;


    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param ShippingCalculatorInterface $shippingCalculator
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        ShippingCalculatorInterface $shippingCalculator,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->rateErrorFactory = $rateErrorFactory;
        $this->shippingCalculator = $shippingCalculator;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Collect and get rates using service layer
     *
     * @param RateRequest $request
     * @return Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        try {
            // Use service layer for validation and calculation
            if (!$this->shippingCalculator->canHandle($request)) {
                return $this->getErrorResult($request);
            }

            /** @var Result $result */
            $result = $this->rateResultFactory->create();

            // Calculate rate using service layer
            $shippingPrice = $this->shippingCalculator->calculateRate($request);
            
            /** @var Method $method */
            $method = $this->rateMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));

            $finalPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);
            $method->setPrice($finalPrice);
            $method->setCost($shippingPrice);

            $result->append($method);

            return $result;

        } catch (\Exception $e) {
            $this->_logger->error('Custom shipping rate calculation failed: ' . $e->getMessage(), [
                'request_data' => [
                    'package_weight' => $request->getPackageWeight(),
                    'package_value' => $request->getPackageValue(),
                    'dest_country' => $request->getDestCountryId(),
                    'dest_region' => $request->getDestRegionId(),
                    'dest_postcode' => $request->getDestPostcode()
                ]
            ]);
            
            return false;
        }
    }


    /**
     * Get error result when shipping is not available
     *
     * @param RateRequest $request
     * @return Result
     */
    protected function getErrorResult(RateRequest $request)
    {
        $result = $this->rateResultFactory->create();
        $error = $this->rateErrorFactory->create();
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        
        // Get specific validation errors from service layer
        $validationErrors = $this->shippingCalculator->getValidationErrors($request);
        $errorMessage = !empty($validationErrors) 
            ? implode('; ', $validationErrors)
            : $this->getConfigData('specificerrmsg');
            
        $error->setErrorMessage($errorMessage);
        $result->append($error);
        
        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return false;
    }

    /**
     * Check if carrier has shipping label option available
     *
     * @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return false;
    }
}