<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;

class DiscountSubtotalProvider implements SubtotalProviderInterface
{
    const NAME = 'oro_promotion.subtotal_discount_cost';

    /**
     * @var PromotionRunner
     */
    private $promotionRunner;

    /**
     * @param PromotionRunner $promotionRunner
     */
    public function __construct(PromotionRunner $promotionRunner)
    {
        $this->promotionRunner = $promotionRunner;
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
        $discountContext = $this->promotionRunner->processor($entity);

    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        // TODO: Implement isSupported() method.
    }
}
