<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class DiscountsProvider
{
    /**
     * @var AppliedDiscountsProvider
     */
    protected $provider;

    /**
     * @var PromotionExecutor
     */
    protected $executor;

    /**
     * @var bool
     */
    protected $recalculationEnabled = false;

    public function __construct(AppliedDiscountsProvider $provider, PromotionExecutor $executor)
    {
        $this->provider = $provider;
        $this->executor = $executor;
    }

    /**
     * @param Order $order
     * @return float
     */
    public function getDiscountsAmountByOrder(Order $order): float
    {
        return $this->recalculationEnabled || !$order->getId() ?
            $this->executor->execute($order)->getTotalDiscountAmount() :
            $this->provider->getDiscountsAmountByOrder($order);
    }

    /**
     * @param OrderLineItem $lineItem
     * @return float
     */
    public function getDiscountsAmountByLineItem(OrderLineItem $lineItem): float
    {
        return $this->recalculationEnabled || !$lineItem->getId() ?
            $this->executor->execute($lineItem->getOrder())->getDiscountByLineItem($lineItem) :
            $this->provider->getDiscountsAmountByLineItem($lineItem);
    }

    /**
     * @return $this
     */
    public function enableRecalculation()
    {
        $this->recalculationEnabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableRecalculation()
    {
        $this->recalculationEnabled = false;

        return $this;
    }
}
