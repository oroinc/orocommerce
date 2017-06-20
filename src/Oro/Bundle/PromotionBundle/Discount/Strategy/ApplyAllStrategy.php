<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;

class ApplyAllStrategy extends AbstractStrategy
{
    /**
     * @var SubtotalProviderInterface
     */
    private $lineItemsSubtotalProvider;

    /**
     * @param SubtotalProviderInterface $lineItemsSubtotalProvider
     */
    public function __construct(SubtotalProviderInterface $lineItemsSubtotalProvider)
    {
        $this->lineItemsSubtotalProvider = $lineItemsSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.promotion.discount.strategy.apply_all.label';
    }

    /**
     * {@inheritdoc}
     */
    public function process(DiscountContext $discountContext, array $discounts): DiscountContext
    {
        foreach ($discounts as $discount) {
            $discount->apply($discountContext);
        }

        $this->processLineItemDiscounts($discountContext);

        $subtotal = $this->lineItemsSubtotalProvider->getSubtotal($discountContext);
        $discountContext->setSubtotal($subtotal->getAmount());

        $this->processTotalDiscounts($discountContext);
        $this->processShippingDiscounts($discountContext);

        return $discountContext;
    }
}
