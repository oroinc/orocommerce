<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class ShippingAwareDiscount extends AbstractDiscount
{
    const SHIPPING_DISCOUNT = 'shipping_discount';

    /**
     * @var DiscountInterface
     */
    protected $shippingDiscount;

    /**
     * @var null|string
     */
    protected $shippingDiscountApplyTo;

    /**
     * @var bool
     */
    protected $hasShippingDiscount = false;

    /**
     * @param DiscountInterface $shippingDiscount
     */
    public function __construct(DiscountInterface $shippingDiscount)
    {
        $this->shippingDiscount = $shippingDiscount;
    }

    /**
     * {@inheritdoc}
     */
    public function setMatchingProducts(array $products)
    {
        parent::setMatchingProducts($products);
        $this->shippingDiscount->setMatchingProducts($products);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $options): array
    {
        $resolvedOptions = parent::configure($options);

        $this->hasShippingDiscount = !empty($resolvedOptions[self::SHIPPING_DISCOUNT]);
        if ($this->hasShippingDiscount) {
            $this->shippingDiscount->configure(
                [
                    self::DISCOUNT_VALUE => 1,
                    ShippingDiscount::APPLY_TO => $resolvedOptions[self::SHIPPING_DISCOUNT]
                ]
            );
        }

        return $resolvedOptions;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = parent::getOptionsResolver();
        $resolver->setDefault(self::SHIPPING_DISCOUNT, null);
        $resolver->setAllowedTypes(self::SHIPPING_DISCOUNT, ['null', 'string']);
        $resolver->setAllowedValues(
            self::SHIPPING_DISCOUNT,
            [null, ShippingDiscount::APPLY_TO_ITEMS, ShippingDiscount::APPLY_TO_ORDER]
        );

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContext $discountContext)
    {
        if ($this->hasShippingDiscount) {
            $discountContext->addShippingDiscount($this->shippingDiscount);
        }
    }
}
