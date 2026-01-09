<?php

namespace Oro\Bundle\PromotionBundle\OrderTax\Mapper;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Update Taxable amount and shipping cost in case when option Calculate Taxes After Promotions is enabled
 */
class OrderAfterDiscountsMapper implements TaxMapperInterface
{
    public function __construct(
        private TaxMapperInterface $innerMapper,
        private TaxationSettingsProvider $taxationSettingsProvider,
        private PromotionExecutor $promotionExecutor
    ) {
    }

    /**
     * @param object|Order $order
     */
    #[\Override]
    public function map(object $order): Taxable
    {
        $taxable = $this->innerMapper->map($order);

        if (
            $this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled() &&
            $this->promotionExecutor->supports($order)
        ) {
            $discountContext = $this->promotionExecutor->execute($order);

            $taxable->setShippingCost($discountContext->getShippingCost());
        }

        return $taxable;
    }
}
