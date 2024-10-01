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

    #[\Override]
    public function configure(array $options): array
    {
        return $this->discount->configure($options);
    }

    #[\Override]
    public function getMatchingProducts()
    {
        return $this->discount->getMatchingProducts();
    }

    #[\Override]
    public function setMatchingProducts(array $products)
    {
        $this->discount->setMatchingProducts($products);
    }

    #[\Override]
    public function getDiscountType(): string
    {
        return $this->discount->getDiscountType();
    }

    #[\Override]
    public function getDiscountValue(): float
    {
        return $this->discount->getDiscountValue();
    }

    #[\Override]
    public function getDiscountCurrency()
    {
        return $this->discount->getDiscountCurrency();
    }

    #[\Override]
    public function apply(DiscountContextInterface $discountContext)
    {
        $this->discount->apply(new DisabledDiscountContextDecorator($discountContext));
    }

    #[\Override]
    public function calculate($entity): float
    {
        return 0.0;
    }

    #[\Override]
    public function getPromotion()
    {
        return $this->discount->getPromotion();
    }

    #[\Override]
    public function setPromotion(PromotionDataInterface $promotion)
    {
        $this->discount->setPromotion($promotion);
    }
}
