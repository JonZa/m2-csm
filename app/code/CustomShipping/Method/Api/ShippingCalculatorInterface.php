<?php
/**
 * Shipping Calculator Service Contract
 * Provides abstraction for shipping rate calculations
 */

namespace CustomShipping\Method\Api;

use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Interface for shipping rate calculation services
 * 
 * @api
 * @since 1.0.0
 */
interface ShippingCalculatorInterface
{
    /**
     * Calculate shipping rate for given request
     *
     * @param RateRequest $request
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function calculateRate(RateRequest $request): float;

    /**
     * Check if free shipping applies to the request
     *
     * @param RateRequest $request
     * @return bool
     */
    public function isFreeShippingApplicable(RateRequest $request): bool;

    /**
     * Validate if shipping method can handle the request
     *
     * @param RateRequest $request
     * @return bool
     */
    public function canHandle(RateRequest $request): bool;

    /**
     * Get validation error messages if any
     *
     * @param RateRequest $request
     * @return string[]
     */
    public function getValidationErrors(RateRequest $request): array;
}