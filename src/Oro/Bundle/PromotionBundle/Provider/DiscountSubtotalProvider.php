<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;

class DiscountSubtotalProvider implements SubtotalProviderInterface
{
    const NAME = 'oro_promotion.subtotal_discount_cost';

    /**
     * @var PromotionProvider
     */
    private $promotionProvider;

    /**
     * @param PromotionProvider $promotionProvider
     */
    public function __construct(PromotionProvider $promotionProvider)
    {
        $this->promotionProvider = $promotionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal($entity)
    {
        // TODO: Implement getSubtotal() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        // TODO: Implement isSupported() method.
    }
}
