<?php
/**
 * Shipping Rate Data Model
 * Implementation of ShippingRateInterface
 */

namespace CustomShipping\Method\Model\Data;

use CustomShipping\Method\Api\Data\ShippingRateInterface;
use Magento\Framework\DataObject;

/**
 * Shipping Rate data model implementation
 */
class ShippingRate extends DataObject implements ShippingRateInterface
{
    /**
     * @inheritdoc
     */
    public function getCarrierCode(): string
    {
        return (string)$this->getData(self::CARRIER_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setCarrierCode(string $carrierCode): ShippingRateInterface
    {
        return $this->setData(self::CARRIER_CODE, $carrierCode);
    }

    /**
     * @inheritdoc
     */
    public function getMethodCode(): string
    {
        return (string)$this->getData(self::METHOD_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setMethodCode(string $methodCode): ShippingRateInterface
    {
        return $this->setData(self::METHOD_CODE, $methodCode);
    }

    /**
     * @inheritdoc
     */
    public function getCarrierTitle(): string
    {
        return (string)$this->getData(self::CARRIER_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setCarrierTitle(string $title): ShippingRateInterface
    {
        return $this->setData(self::CARRIER_TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getMethodTitle(): string
    {
        return (string)$this->getData(self::METHOD_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setMethodTitle(string $title): ShippingRateInterface
    {
        return $this->setData(self::METHOD_TITLE, $title);
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return (float)$this->getData(self::PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setPrice(float $price): ShippingRateInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritdoc
     */
    public function getCost(): float
    {
        return (float)$this->getData(self::COST);
    }

    /**
     * @inheritdoc
     */
    public function setCost(float $cost): ShippingRateInterface
    {
        return $this->setData(self::COST, $cost);
    }

    /**
     * @inheritdoc
     */
    public function isFreeShipping(): bool
    {
        return (bool)$this->getData(self::IS_FREE_SHIPPING);
    }

    /**
     * @inheritdoc
     */
    public function setIsFreeShipping(bool $isFree): ShippingRateInterface
    {
        return $this->setData(self::IS_FREE_SHIPPING, $isFree);
    }

    /**
     * @inheritdoc
     */
    public function isWeightBased(): bool
    {
        return (bool)$this->getData(self::WEIGHT_BASED);
    }

    /**
     * @inheritdoc
     */
    public function setIsWeightBased(bool $isWeightBased): ShippingRateInterface
    {
        return $this->setData(self::WEIGHT_BASED, $isWeightBased);
    }

    /**
     * @inheritdoc
     */
    public function getCalculationDetails(): array
    {
        return (array)$this->getData(self::CALCULATION_DETAILS);
    }

    /**
     * @inheritdoc
     */
    public function setCalculationDetails(array $details): ShippingRateInterface
    {
        return $this->setData(self::CALCULATION_DETAILS, $details);
    }
}