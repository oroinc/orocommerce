<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;

/**
 * Discount services MUST BE registered with shared: false
 */
interface DiscountInterface
{
    const TYPE_AMOUNT = 'amount';
    const TYPE_PERCENT = 'percent';

    public function configure(array $options): array;

    /**
     * @return array|Product[]
     */
    public function getMatchingProducts();

    /**
     * @param array|Product[] $products
     * @return $this
     */
    public function setMatchingProducts(array $products);

    /**
     * Get type of discount: TYPE_AMOUNT or TYPE_PERCENT
     */
    public function getDiscountType(): string;

    public function getDiscountValue(): float;

    /**
     * Currency ISO 4217 code
     *
     * @return string|null
     */
    public function getDiscountCurrency();

    /**
     * Add information about discount to context
     */
    public function apply(DiscountContextInterface $discountContext);

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
     * @return PromotionDataInterface|null
     */
    public function getPromotion();

    /**
     * Set related promotion
     *
     * @param PromotionDataInterface $promotion
     * @return $this
     */
    public function setPromotion(PromotionDataInterface $promotion);
}
