<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Discount services MUST BE registered with shared: false
 */
interface DiscountInterface
{
    const TYPE_AMOUNT = 'amount';
    const TYPE_PERCENT = 'percent';

    /**
     * @param array $options
     * @return array
     */
    public function configure(array $options): array;

    /**
     * @return array|Product[]
     */
    public function getMatchingProducts(): array;

    /**
     * @param array|Product[] $products
     */
    public function setMatchingProducts(array $products);

    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * Get type of discount: TYPE_AMOUNT or TYPE_PERCENT
     *
     * @return string
     */
    public function getDiscountType(): string;

    /**
     * @return float
     */
    public function getDiscountValue(): float;

    /**
     * Currency ISO 4217 code
     *
     * @return string|null
     */
    public function getDiscountCurrency();

    /**
     * Add information about discount to context
     *
     * @param DiscountContext $discountContext
     */
    public function apply(DiscountContext $discountContext);

    /**
     * Calculate discount value for given entity
     *
     * @param object $entity
     * @return float
     */
    public function calculate($entity): float;

    /**
     * Get related promotion
     *
     * @return Promotion|null
     */
    public function getPromotion();

    /**
     * Set related promotion
     *
     * @param Promotion $promotion
     */
    public function setPromotion(Promotion $promotion);
}
