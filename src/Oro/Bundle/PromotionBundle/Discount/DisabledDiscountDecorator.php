<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;

/**
 * Decorates discount to make it's amount zero (i.e. disable it).
 */
class DisabledDiscountDecorator implements DiscountInterface
{
    /**
     * @var DiscountInterface
     */
    private $discount;

    public function __construct(DiscountInterface $discount)
    {
        $this->discount = $discount;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $options): array
    {
        return $this->discount->configure($options);
    }

    /**
     *{@inheritdoc}
     */
    public function getMatchingProducts()
    {
        return $this->discount->getMatchingProducts();
    }

    /**
     * {@inheritdoc}
     */
    public function setMatchingProducts(array $products)
    {
        $this->discount->setMatchingProducts($products);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountType(): string
    {
        return $this->discount->getDiscountType();
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountValue(): float
    {
        return $this->discount->getDiscountValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountCurrency()
    {
        return $this->discount->getDiscountCurrency();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContextInterface $discountContext)
    {
        $this->discount->apply(new DisabledDiscountContextDecorator($discountContext));
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($entity): float
    {
        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function getPromotion()
    {
        return $this->discount->getPromotion();
    }

    /**
     * {@inheritdoc}
     */
    public function setPromotion(PromotionDataInterface $promotion)
    {
        $this->discount->setPromotion($promotion);
    }
}
