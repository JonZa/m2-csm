<?php
/**
 * Shipping Rate Data Interface
 * Represents a calculated shipping rate with metadata
 */

namespace CustomShipping\Method\Api\Data;

/**
 * Shipping Rate data interface
 * 
 * @api
 * @since 1.0.0
 */
interface ShippingRateInterface
{
    /**
     * Constants for rate data keys
     */
    const CARRIER_CODE = 'carrier_code';
    const METHOD_CODE = 'method_code';
    const CARRIER_TITLE = 'carrier_title';
    const METHOD_TITLE = 'method_title';
    const PRICE = 'price';
    const COST = 'cost';
    const IS_FREE_SHIPPING = 'is_free_shipping';
    const WEIGHT_BASED = 'weight_based';
    const CALCULATION_DETAILS = 'calculation_details';

    /**
     * Get carrier code
     *
     * @return string
     */
    public function getCarrierCode(): string;

    /**
     * Set carrier code
     *
     * @param string $carrierCode
     * @return $this
     */
    public function setCarrierCode(string $carrierCode): self;

    /**
     * Get method code
     *
     * @return string
     */
    public function getMethodCode(): string;

    /**
     * Set method code
     *
     * @param string $methodCode
     * @return $this
     */
    public function setMethodCode(string $methodCode): self;

    /**
     * Get carrier title
     *
     * @return string
     */
    public function getCarrierTitle(): string;

    /**
     * Set carrier title
     *
     * @param string $title
     * @return $this
     */
    public function setCarrierTitle(string $title): self;

    /**
     * Get method title
     *
     * @return string
     */
    public function getMethodTitle(): string;

    /**
     * Set method title
     *
     * @param string $title
     * @return $this
     */
    public function setMethodTitle(string $title): self;

    /**
     * Get shipping price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Set shipping price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price): self;

    /**
     * Get shipping cost
     *
     * @return float
     */
    public function getCost(): float;

    /**
     * Set shipping cost
     *
     * @param float $cost
     * @return $this
     */
    public function setCost(float $cost): self;

    /**
     * Check if this is free shipping
     *
     * @return bool
     */
    public function isFreeShipping(): bool;

    /**
     * Set free shipping flag
     *
     * @param bool $isFree
     * @return $this
     */
    public function setIsFreeShipping(bool $isFree): self;

    /**
     * Check if rate is weight-based
     *
     * @return bool
     */
    public function isWeightBased(): bool;

    /**
     * Set weight-based flag
     *
     * @param bool $isWeightBased
     * @return $this
     */
    public function setIsWeightBased(bool $isWeightBased): self;

    /**
     * Get calculation details
     *
     * @return array
     */
    public function getCalculationDetails(): array;

    /**
     * Set calculation details
     *
     * @param array $details
     * @return $this
     */
    public function setCalculationDetails(array $details): self;
}