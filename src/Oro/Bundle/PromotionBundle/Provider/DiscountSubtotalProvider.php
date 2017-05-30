<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class DiscountSubtotalProvider implements SubtotalProviderInterface
{
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
    public function getName()
    {
        // TODO: Implement getName() method.
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
