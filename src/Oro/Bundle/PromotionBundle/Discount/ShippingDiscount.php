<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingDiscount extends AbstractDiscount
{
    const APPLY_TO_ITEMS = 'items';
    const APPLY_TO_ORDER = 'order';
    const APPLY_TO = 'apply_to';
    const SHIPPING_OPTIONS = 'shipping_options';
    const SHIPPING_METHOD = 'shipping_method';
    const SHIPPING_METHOD_TYPE = 'shipping_method_type';

    /**
     * @var string
     */
    protected $applyTo;

    /**
     * @var string
     */
    protected $shippingOptions;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options): array
    {
        $resolvedOptions = parent::configure($options);

        $this->applyTo = $resolvedOptions[self::APPLY_TO];
        $this->shippingOptions = $resolvedOptions[self::SHIPPING_OPTIONS];

        return $resolvedOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContext $discountContext)
    {
        // TODO: Implement apply() method.

        $discountContext->addShippingDiscount($this);
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($entity): float
    {
        // TODO: Implement calculate() method.

        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = parent::getOptionsResolver();
        $resolver->setDefault(self::APPLY_TO, self::APPLY_TO_ORDER);
        $resolver->setAllowedTypes(self::APPLY_TO, ['string']);
        $resolver->setAllowedValues(
            self::APPLY_TO,
            [ShippingDiscount::APPLY_TO_ITEMS, ShippingDiscount::APPLY_TO_ORDER]
        );

        $resolver->setAllowedTypes(self::SHIPPING_OPTIONS, ['array']);

        return $resolver;
    }
}
