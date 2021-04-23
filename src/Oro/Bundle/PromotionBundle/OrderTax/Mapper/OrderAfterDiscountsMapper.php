<?php

namespace Oro\Bundle\PromotionBundle\OrderTax\Mapper;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Update Taxable amount and shipping cost in case when option Calculate Taxes After Promotions is enabled
 */
class OrderAfterDiscountsMapper implements TaxMapperInterface
{
    private TaxMapperInterface $innerMapper;
    private TaxationSettingsProvider $taxationSettingsProvider;
    private PromotionExecutor $promotionExecutor;

    public function __construct(
        TaxMapperInterface $innerMapper,
        TaxationSettingsProvider $taxationSettingsProvider,
        PromotionExecutor $promotionExecutor
    ) {
        $this->innerMapper = $innerMapper;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->promotionExecutor = $promotionExecutor;
    }

    /**
     * {@inheritdoc}
     * @param Order $order
     */
    public function map($order)
    {
        $taxable = $this->innerMapper->map($order);

        if ($this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled() &&
            $this->promotionExecutor->supports($order)
        ) {
            $discountContext = $this->promotionExecutor->execute($order);

            $taxable->setShippingCost($discountContext->getShippingCost());
        }

        return $taxable;
    }
}
